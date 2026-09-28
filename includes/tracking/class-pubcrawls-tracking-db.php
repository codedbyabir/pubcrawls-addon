<?php
/**
 * PubCrawls Tracking Database & Idempotency Layer
 *
 * Manages the custom database table for Eventbrite orders, enforcing
 * atomic locking, unique constraints, separate platform status tracking,
 * browser attribution association, and selective retries.
 *
 * @package Pubcrawl_Addon
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pubcrawls_Tracking_DB {

	/**
	 * Table name without prefix.
	 */
	const TABLE_NAME = 'pubcrawls_eventbrite_orders';

	/**
	 * Database schema version.
	 */
	const DB_VERSION = '2.0.0';
	const DB_VERSION_OPTION = 'pubcrawls_eb_orders_db_version';

	/**
	 * Maximum delivery retry attempts per platform.
	 */
	const MAX_ATTEMPTS = 5;

	/**
	 * Initialize DB manager.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'check_install_tables' ) );
		add_action( 'init', array( __CLASS__, 'check_install_tables' ) );
		add_action( 'init', array( __CLASS__, 'cleanup_test_orders' ) );
	}

	/**
	 * Get full table name with WordPress prefix.
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Check if tables need to be created or upgraded.
	 */
	public static function check_install_tables(): void {
		$installed_ver = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		if ( version_compare( (string) $installed_ver, self::DB_VERSION, '<' ) ) {
			self::create_tables();
		}
	}

	/**
	 * Create or upgrade database schema using dbDelta.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id varchar(64) NOT NULL,
			eventbrite_event_id varchar(64) DEFAULT '' NOT NULL,
			value decimal(10,2) DEFAULT '0.00' NOT NULL,
			currency varchar(10) DEFAULT 'USD' NOT NULL,
			status varchar(32) DEFAULT 'pending' NOT NULL,
			ga4_status varchar(32) DEFAULT 'pending' NOT NULL,
			meta_status varchar(32) DEFAULT 'pending' NOT NULL,
			tiktok_status varchar(32) DEFAULT 'pending' NOT NULL,
			ga4_code int(11) DEFAULT NULL,
			meta_code int(11) DEFAULT NULL,
			tiktok_code int(11) DEFAULT NULL,
			ga4_attempts int(11) DEFAULT 0 NOT NULL,
			meta_attempts int(11) DEFAULT 0 NOT NULL,
			tiktok_attempts int(11) DEFAULT 0 NOT NULL,
			client_id varchar(128) DEFAULT NULL,
			session_id varchar(128) DEFAULT NULL,
			fbp varchar(128) DEFAULT NULL,
			fbc varchar(255) DEFAULT NULL,
			ttp varchar(128) DEFAULT NULL,
			ttclid varchar(255) DEFAULT NULL,
			ip varchar(64) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			last_error text,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			processed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_id (order_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		if ( function_exists( 'dbDelta' ) ) {
			dbDelta( $sql );
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * Atomically lock an incoming order to prevent duplicate or concurrent execution.
	 *
	 * Uses an atomic INSERT into the unique table. If the row already exists,
	 * MySQL throws a unique constraint error (1062) and no new row is created.
	 *
	 * @param string $order_id            Eventbrite Order ID.
	 * @param string $eventbrite_event_id Optional Eventbrite Event ID.
	 * @return array{
	 *     locked: bool,
	 *     is_new: bool,
	 *     id?: int,
	 *     row?: object,
	 * }
	 */
	public static function atomic_lock_order( string $order_id, string $eventbrite_event_id = '' ): array {
		global $wpdb;

		$table_name = self::get_table_name();
		$order_id   = sanitize_text_field( trim( $order_id ) );
		$now        = current_time( 'mysql' );

		// Attempt atomic insert
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table_name}
				(order_id, eventbrite_event_id, value, currency, status, ga4_status, meta_status, tiktok_status, created_at, updated_at)
				VALUES (%s, %s, 0.00, 'USD', 'pending', 'pending', 'pending', 'pending', %s, %s)",
				$order_id,
				$eventbrite_event_id,
				$now,
				$now
			)
		);

		// If 1 row affected, we locked this order exclusively!
		if ( 1 === $inserted ) {
			return array(
				'locked' => true,
				'is_new' => true,
				'id'     => (int) $wpdb->insert_id,
			);
		}

		// Row already exists - fetch existing status
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE order_id = %s LIMIT 1",
				$order_id
			)
		);

		return array(
			'locked' => false,
			'is_new' => false,
			'row'    => $existing,
		);
	}

	/**
	 * Retrieve a single tracked order record by Order ID.
	 *
	 * @param string $order_id Eventbrite Order ID.
	 * @return object|null Database row or null.
	 */
	public static function get_order( string $order_id ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$order_id   = sanitize_text_field( trim( $order_id ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE order_id = %s LIMIT 1",
				$order_id
			)
		);

		return $row ?: null;
	}

	/**
	 * Store browser attribution data keyed by Eventbrite Order ID.
	 *
	 * @param string $order_id    Eventbrite Order ID.
	 * @param array  $attribution Browser attribution parameters.
	 * @return bool
	 */
	public static function store_attribution( string $order_id, array $attribution ): bool {
		global $wpdb;
		$table_name = self::get_table_name();
		$order_id   = sanitize_text_field( trim( $order_id ) );
		if ( empty( $order_id ) ) {
			return false;
		}

		// Ensure order row exists
		self::atomic_lock_order( $order_id, sanitize_text_field( (string) ( $attribution['event_id'] ?? '' ) ) );

		$fields = array(
			'updated_at' => current_time( 'mysql' ),
		);

		$allowed_fields = array(
			'client_id'  => 'sanitize_text_field',
			'session_id' => 'sanitize_text_field',
			'fbp'        => 'sanitize_text_field',
			'fbc'        => 'sanitize_text_field',
			'ttp'        => 'sanitize_text_field',
			'ttclid'     => 'sanitize_text_field',
			'ip'         => 'sanitize_text_field',
			'user_agent' => 'sanitize_textarea_field',
		);

		foreach ( $allowed_fields as $key => $sanitizer ) {
			if ( ! empty( $attribution[ $key ] ) ) {
				$fields[ $key ] = call_user_func( $sanitizer, (string) $attribution[ $key ] );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->update(
			$table_name,
			$fields,
			array( 'order_id' => $order_id )
		);

		return false !== $res;
	}

	/**
	 * Get stored browser attribution data for an order.
	 *
	 * @param string $order_id Eventbrite Order ID.
	 * @return array
	 */
	public static function get_attribution( string $order_id ): array {
		$order = self::get_order( $order_id );
		if ( ! $order ) {
			return array();
		}

		return array(
			'client_id'  => $order->client_id ?? '',
			'session_id' => $order->session_id ?? '',
			'fbp'        => $order->fbp ?? '',
			'fbc'        => $order->fbc ?? '',
			'ttp'        => $order->ttp ?? '',
			'ttclid'     => $order->ttclid ?? '',
			'ip'         => $order->ip ?? '',
			'user_agent' => $order->user_agent ?? '',
		);
	}

	/**
	 * Update general order data (revenue, currency, event ID).
	 *
	 * @param string $order_id Order ID.
	 * @param array  $data     Data to update.
	 * @return bool
	 */
	public static function update_order_data( string $order_id, array $data ): bool {
		global $wpdb;
		$table_name = self::get_table_name();

		$fields = array(
			'updated_at' => current_time( 'mysql' ),
		);

		if ( isset( $data['value'] ) ) {
			$fields['value'] = (float) $data['value'];
		}
		if ( ! empty( $data['currency'] ) ) {
			$fields['currency'] = sanitize_text_field( (string) $data['currency'] );
		}
		if ( ! empty( $data['eventbrite_event_id'] ) ) {
			$fields['eventbrite_event_id'] = sanitize_text_field( (string) $data['eventbrite_event_id'] );
		}
		if ( ! empty( $data['status'] ) ) {
			$fields['status'] = sanitize_text_field( (string) $data['status'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->update(
			$table_name,
			$fields,
			array( 'order_id' => $order_id )
		);

		return false !== $res;
	}

	/**
	 * Update delivery status, HTTP code, and attempts for a specific platform.
	 *
	 * @param string      $order_id  Order ID.
	 * @param string      $platform  Platform slug ('ga4', 'meta', 'tiktok').
	 * @param string      $status    Status ('success', 'failed', 'skipped', 'pending').
	 * @param string|null $error     Optional error message.
	 * @param int|null    $http_code Optional HTTP response code.
	 * @return bool
	 */
	public static function update_platform_status( string $order_id, string $platform, string $status, ?string $error = null, ?int $http_code = null ): bool {
		global $wpdb;
		$table_name = self::get_table_name();

		$valid_platforms = array( 'ga4', 'meta', 'tiktok' );
		if ( ! in_array( $platform, $valid_platforms, true ) ) {
			return false;
		}

		$status_col   = "{$platform}_status";
		$attempts_col = "{$platform}_attempts";
		$code_col     = "{$platform}_code";
		$now          = current_time( 'mysql' );

		if ( null !== $error ) {
			$clean_error = sanitize_text_field( substr( $error, 0, 1000 ) );
			if ( null !== $http_code ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$res = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table_name} SET {$status_col} = %s, {$code_col} = %d, {$attempts_col} = {$attempts_col} + 1, updated_at = %s, last_error = %s WHERE order_id = %s",
						$status,
						$http_code,
						$now,
						$clean_error,
						$order_id
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$res = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table_name} SET {$status_col} = %s, {$attempts_col} = {$attempts_col} + 1, updated_at = %s, last_error = %s WHERE order_id = %s",
						$status,
						$now,
						$clean_error,
						$order_id
					)
				);
			}
		} else {
			if ( null !== $http_code ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$res = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table_name} SET {$status_col} = %s, {$code_col} = %d, {$attempts_col} = {$attempts_col} + 1, updated_at = %s WHERE order_id = %s",
						$status,
						$http_code,
						$now,
						$order_id
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$res = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table_name} SET {$status_col} = %s, {$attempts_col} = {$attempts_col} + 1, updated_at = %s WHERE order_id = %s",
						$status,
						$now,
						$order_id
					)
				);
			}
		}

		return false !== $res;
	}

	/**
	 * Check if an order has exceeded maximum retry attempts on all failed platforms.
	 *
	 * @param string $order_id Order ID.
	 * @return bool True if retry limit reached.
	 */
	public static function has_exceeded_max_retries( string $order_id ): bool {
		$order = self::get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		$max = self::MAX_ATTEMPTS;
		$ga4_done    = ( 'success' === $order->ga4_status || 'skipped' === $order->ga4_status || (int) $order->ga4_attempts >= $max );
		$meta_done   = ( 'success' === $order->meta_status || 'skipped' === $order->meta_status || (int) $order->meta_attempts >= $max );
		$tiktok_done = ( 'success' === $order->tiktok_status || 'skipped' === $order->tiktok_status || (int) $order->tiktok_attempts >= $max );

		return ( $ga4_done && $meta_done && $tiktok_done );
	}

	/**
	 * Finalize the overall order status based on active platform results.
	 *
	 * @param string $order_id Order ID.
	 * @param array  $active_platforms Array of active platform slugs (e.g. ['ga4', 'meta', 'tiktok']).
	 * @return string Resulting overall status ('completed', 'partial', 'failed', 'rejected').
	 */
	public static function finalize_order_status( string $order_id, array $active_platforms ): string {
		global $wpdb;
		$order = self::get_order( $order_id );
		if ( ! $order ) {
			return 'failed';
		}

		if ( 'rejected' === $order->status ) {
			return 'rejected';
		}

		$all_success = true;
		$any_success = false;

		foreach ( $active_platforms as $platform ) {
			$col = "{$platform}_status";
			$st  = $order->$col ?? 'pending';

			if ( 'success' === $st ) {
				$any_success = true;
			} else {
				$all_success = false;
			}
		}

		$new_status = 'pending';
		if ( $all_success && ! empty( $active_platforms ) ) {
			$new_status = 'completed';
		} elseif ( $any_success ) {
			$new_status = 'partial';
		} else {
			$new_status = 'failed';
		}

		$fields = array(
			'status'       => $new_status,
			'updated_at'   => current_time( 'mysql' ),
			'processed_at' => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			self::get_table_name(),
			$fields,
			array( 'order_id' => $order_id )
		);

		return $new_status;
	}

	/**
	 * Clean up test orders (1, 2, 3) from database, queue, and diagnostic logs.
	 *
	 * @return int Number of rows deleted from the database table.
	 */
	public static function cleanup_test_orders(): int {
		global $wpdb;
		$table_name = self::get_table_name();

		// Clean up from database table
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$deleted = $wpdb->query( "DELETE FROM {$table_name} WHERE order_id IN ('1', '2', '3')" );

		// Clean up logs in options
		$logs = get_option( 'pubcrawls_eb_webhook_logs', array() );
		if ( is_array( $logs ) ) {
			$filtered = array_values(
				array_filter(
					$logs,
					function( $entry ) {
						$oid = (string) ( $entry['order_id'] ?? '' );
						return ! in_array( $oid, array( '1', '2', '3' ), true );
					}
				)
			);
			update_option( 'pubcrawls_eb_webhook_logs', $filtered );
		}

		// Clear scheduled cron events
		foreach ( array( '1', '2', '3' ) as $oid ) {
			wp_clear_scheduled_hook( 'pubcrawls_process_eventbrite_order', array( $oid ) );
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( 'pubcrawls_process_eventbrite_order', array( 'order_id' => $oid ) );
			}
		}

		return (int) $deleted;
	}

	/**
	 * Get recent tracked orders for admin dashboard.
	 *
	 * @param int $limit Maximum number of orders to retrieve.
	 * @return array List of order row objects.
	 */
	public static function get_recent_orders( int $limit = 20 ): array {
		global $wpdb;
		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY id DESC LIMIT %d",
				$limit
			)
		);

		return $rows ?: array();
	}
}
