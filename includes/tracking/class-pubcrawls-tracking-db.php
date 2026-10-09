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
	const DB_VERSION = '2.2.0';
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
			openai_status varchar(32) DEFAULT 'pending' NOT NULL,
			ga4_code int(11) DEFAULT NULL,
			meta_code int(11) DEFAULT NULL,
			tiktok_code int(11) DEFAULT NULL,
			openai_code int(11) DEFAULT NULL,
			ga4_attempts int(11) DEFAULT 0 NOT NULL,
			meta_attempts int(11) DEFAULT 0 NOT NULL,
			tiktok_attempts int(11) DEFAULT 0 NOT NULL,
			openai_attempts int(11) DEFAULT 0 NOT NULL,
			client_id varchar(128) DEFAULT NULL,
			session_id varchar(128) DEFAULT NULL,
			fbp varchar(128) DEFAULT NULL,
			fbc varchar(255) DEFAULT NULL,
			ttp varchar(128) DEFAULT NULL,
			ttclid varchar(255) DEFAULT NULL,
			oai_obref varchar(255) DEFAULT NULL,
			oai_oppref varchar(255) DEFAULT NULL,
			ip varchar(64) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			last_error text,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			processed_at datetime DEFAULT NULL,
			locked_at datetime DEFAULT NULL,
			worker_id varchar(64) DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_id (order_id),
			KEY status (status),
			KEY created_at (created_at),
			KEY locked_at (locked_at),
			KEY status_locked (status, locked_at)
		) {$charset_collate};";

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		if ( function_exists( 'dbDelta' ) ) {
			dbDelta( $sql );
		}

		// Ensure columns exist on upgraded installations where dbDelta might skip ALTER
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$columns = $wpdb->get_col( "DESCRIBE {$table_name}" );
		if ( is_array( $columns ) && ! empty( $columns ) ) {
			if ( ! in_array( 'locked_at', $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN locked_at datetime DEFAULT NULL AFTER processed_at" );
			}
			if ( ! in_array( 'worker_id', $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN worker_id varchar(64) DEFAULT NULL AFTER locked_at" );
			}
			if ( ! in_array( 'openai_status', $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN openai_status varchar(32) DEFAULT 'pending' NOT NULL AFTER tiktok_status" );
			}
			if ( ! in_array( 'openai_code', $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN openai_code int(11) DEFAULT NULL AFTER tiktok_code" );
			}
			if ( ! in_array( 'openai_attempts', $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN openai_attempts int(11) DEFAULT 0 NOT NULL AFTER tiktok_attempts" );
			}
			if ( ! in_array( 'oai_obref', $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN oai_obref varchar(255) DEFAULT NULL AFTER ttclid" );
			}
			if ( ! in_array( 'oai_oppref', $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN oai_oppref varchar(255) DEFAULT NULL AFTER oai_obref" );
			}
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
				(order_id, eventbrite_event_id, value, currency, status, ga4_status, meta_status, tiktok_status, openai_status, created_at, updated_at)
				VALUES (%s, %s, 0.00, 'USD', 'pending', 'pending', 'pending', 'pending', 'pending', %s, %s)",
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
			'oai_obref'  => 'sanitize_text_field',
			'oai_oppref' => 'sanitize_text_field',
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
			'oai_obref'  => $order->oai_obref ?? '',
			'oai_oppref' => $order->oai_oppref ?? '',
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

		$valid_platforms = array( 'ga4', 'meta', 'tiktok', 'openai' );
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
		$openai_done = ( 'success' === ( $order->openai_status ?? 'pending' ) || 'skipped' === ( $order->openai_status ?? 'pending' ) || (int) ( $order->openai_attempts ?? 0 ) >= $max );

		return ( $ga4_done && $meta_done && $tiktok_done && $openai_done );
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
			'worker_id'    => null,
			'locked_at'    => null,
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
	 * Atomically acquire a processing lock on an order.
	 *
	 * Uses compare-and-swap SQL UPDATE so only one worker can process the order.
	 * Reclaims stale locks older than $stale_timeout_seconds (default 600s = 10 min).
	 *
	 * @param string $order_id              Eventbrite Order ID.
	 * @param string $worker_id             Unique worker identifier.
	 * @param int    $stale_timeout_seconds Lock expiration in seconds (default 600).
	 * @return bool True if lock acquired, false if held by another active worker or finalized.
	 */
	public static function acquire_processing_lock( string $order_id, string $worker_id, int $stale_timeout_seconds = 600 ): bool {
		global $wpdb;
		$table_name = self::get_table_name();
		$order_id   = sanitize_text_field( trim( $order_id ) );
		$worker_id  = sanitize_text_field( trim( $worker_id ) );
		$now        = current_time( 'mysql' );
		$threshold  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $stale_timeout_seconds );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$affected = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name}
				SET status = 'processing',
					worker_id = %s,
					locked_at = %s,
					updated_at = %s
				WHERE order_id = %s
				  AND (
					  status IN ('pending', 'failed', 'partial', 'queued')
					  OR (
						  status = 'processing'
						  AND (
							  (locked_at IS NOT NULL AND locked_at < %s)
							  OR (locked_at IS NULL AND updated_at < %s)
						  )
					  )
				  )",
				$worker_id,
				$now,
				$now,
				$order_id,
				$threshold,
				$threshold
			)
		);

		return 1 === $affected;
	}

	/**
	 * Safely release an order's lock without changing its failed/partial status.
	 *
	 * @param string      $order_id  Eventbrite Order ID.
	 * @param string|null $worker_id Optional worker ID to verify ownership.
	 * @param string      $status    Status to reset to ('pending', 'failed', 'partial').
	 * @return bool
	 */
	public static function release_order_lock( string $order_id, ?string $worker_id = null, string $status = 'pending' ): bool {
		global $wpdb;
		$table_name = self::get_table_name();
		$order_id   = sanitize_text_field( trim( $order_id ) );
		$now        = current_time( 'mysql' );

		if ( $worker_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$res = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name}
					SET status = %s, worker_id = NULL, locked_at = NULL, updated_at = %s
					WHERE order_id = %s AND worker_id = %s",
					$status,
					$now,
					$order_id,
					$worker_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$res = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name}
					SET status = %s, worker_id = NULL, locked_at = NULL, updated_at = %s
					WHERE order_id = %s",
					$status,
					$now,
					$order_id
				)
			);
		}

		return false !== $res;
	}

	/**
	 * Find and safely release stale processing locks.
	 *
	 * Resets orders locked longer than $stale_timeout_seconds back to 'pending'.
	 *
	 * @param int  $stale_timeout_seconds Maximum allowed lock duration in seconds (default 600 = 10 min).
	 * @param bool $force_all             If true, unconditionally releases all orders marked processing.
	 * @return int Number of stale locks released.
	 */
	public static function release_stale_locks( int $stale_timeout_seconds = 600, bool $force_all = false ): int {
		global $wpdb;
		$table_name = self::get_table_name();
		$now        = current_time( 'mysql' );
		$threshold  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $stale_timeout_seconds );

		if ( $force_all ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$affected = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name}
					SET status = 'pending',
						worker_id = NULL,
						locked_at = NULL,
						updated_at = %s
					WHERE status = 'processing'",
					$now
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$affected = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name}
					SET status = 'pending',
						worker_id = NULL,
						locked_at = NULL,
						updated_at = %s
					WHERE status = 'processing'
					  AND (
						  (locked_at IS NOT NULL AND locked_at < %s)
						  OR (locked_at IS NULL AND updated_at < %s)
					  )",
					$now,
					$threshold,
					$threshold
				)
			);
		}

		return (int) $affected;
	}

	/**
	 * Retrieve queued, pending, or retryable orders for background processing.
	 *
	 * @param int $limit Maximum orders to retrieve.
	 * @param int $stale_timeout_seconds Stale lock threshold in seconds.
	 * @return array List of order row objects.
	 */
	public static function get_queued_orders( int $limit = 20, int $stale_timeout_seconds = 600 ): array {
		global $wpdb;
		$table_name = self::get_table_name();
		$threshold  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $stale_timeout_seconds );
		$max        = self::MAX_ATTEMPTS;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name}
				WHERE (
					status IN ('pending', 'queued')
					OR (
						status IN ('failed', 'partial')
						AND (ga4_attempts < %d OR meta_attempts < %d OR tiktok_attempts < %d OR openai_attempts < %d)
					)
					OR (
						status = 'processing'
						AND (
							(locked_at IS NOT NULL AND locked_at < %s)
							OR (locked_at IS NULL AND updated_at < %s)
						)
					)
				)
				ORDER BY id ASC
				LIMIT %d",
				$max,
				$max,
				$max,
				$max,
				$threshold,
				$threshold,
				$limit
			)
		);

		return $rows ?: array();
	}

	/**
	 * Get aggregated queue and health metrics.
	 *
	 * @param int $stale_timeout_seconds Stale lock threshold in seconds.
	 * @return array Aggregated counts.
	 */
	public static function get_queue_counts( int $stale_timeout_seconds = 600 ): array {
		global $wpdb;
		$table_name = self::get_table_name();
		$threshold  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $stale_timeout_seconds );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN status IN ('pending', 'queued') THEN 1 ELSE 0 END) AS pending,
					SUM(CASE WHEN status = 'processing' AND ((locked_at IS NOT NULL AND locked_at >= %s) OR (locked_at IS NULL AND updated_at >= %s)) THEN 1 ELSE 0 END) AS processing_active,
					SUM(CASE WHEN status = 'processing' AND ((locked_at IS NOT NULL AND locked_at < %s) OR (locked_at IS NULL AND updated_at < %s)) THEN 1 ELSE 0 END) AS stale_locks,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
					SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) AS partial,
					SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
					SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
					SUM(CASE WHEN ga4_status = 'success' THEN 1 ELSE 0 END) AS ga4_success,
					SUM(CASE WHEN meta_status = 'success' THEN 1 ELSE 0 END) AS meta_success,
					SUM(CASE WHEN tiktok_status = 'success' THEN 1 ELSE 0 END) AS tiktok_success,
					SUM(CASE WHEN openai_status = 'success' THEN 1 ELSE 0 END) AS openai_success
				FROM {$table_name}",
				$threshold,
				$threshold,
				$threshold,
				$threshold
			),
			ARRAY_A
		);

		return array_map( 'intval', $row ?: array(
			'total'             => 0,
			'pending'           => 0,
			'processing_active' => 0,
			'stale_locks'       => 0,
			'completed'         => 0,
			'partial'           => 0,
			'failed'            => 0,
			'rejected'          => 0,
			'ga4_success'       => 0,
			'meta_success'      => 0,
			'tiktok_success'    => 0,
			'openai_success'    => 0,
		) );
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

	/**
	 * Helper for test suites to clean up test order IDs.
	 *
	 * @param array $order_ids List of order IDs to remove.
	 * @return int Number of deleted rows.
	 */
	public static function cleanup_test_orders( array $order_ids = array() ): int {
		global $wpdb;
		$table_name = self::get_table_name();
		if ( empty( $order_ids ) ) {
			$order_ids = array( '1', '2', '3' );
		}

		$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE order_id IN ($placeholders)",
				...$order_ids
			)
		);

		// Also clean corresponding test entries from webhook logs
		$logs_key = 'pubcrawls_eb_webhook_logs';
		$logs     = get_option( $logs_key, array() );
		if ( is_array( $logs ) && ! empty( $logs ) ) {
			$filtered = array_values(
				array_filter(
					$logs,
					function( $item ) use ( $order_ids ) {
						$oid = (string) ( $item['order_id'] ?? '' );
						return ! in_array( $oid, $order_ids, true );
					}
				)
			);
			update_option( $logs_key, $filtered, false );
		}

		return (int) $deleted;
	}
}
