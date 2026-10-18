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
	const DB_VERSION = '2.4.0';
	const DB_VERSION_OPTION = 'pubcrawls_eb_orders_db_version';

	/**
	 * Maximum delivery retry attempts per platform.
	 */
	const MAX_ATTEMPTS = 5;

	/**
	 * Column cache per request.
	 *
	 * @var array<string, array<int, string>>
	 */
	private static array $columns_cache = array();

	/**
	 * Initialize DB manager.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'check_install_tables' ) );
		add_action( 'init', array( __CLASS__, 'check_install_tables' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'check_install_tables' ) );
	}

	/**
	 * Get full table name with WordPress prefix, dynamically discovering existing tables.
	 */
	public static function get_table_name(): string {
		global $wpdb;
		static $resolved_table = null;
		if ( null !== $resolved_table ) {
			return $resolved_table;
		}

		$primary        = $wpdb->prefix . self::TABLE_NAME;
		$resolved_table = $primary;

		// Check if primary table exists; if not, check alternatives like wp_pubcrawls_eventbrite_orders or wp_pc_
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $primary ) );
		if ( $exists !== $primary ) {
			$alt1 = 'wp_' . self::TABLE_NAME;
			$alt2 = 'wp_pc_' . self::TABLE_NAME;
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $alt1 ) ) === $alt1 ) {
				$resolved_table = $alt1;
			} elseif ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $alt2 ) ) === $alt2 ) {
				$resolved_table = $alt2;
			}
		}

		return $resolved_table;
	}

	/**
	 * Check whether a column exists in the orders table.
	 *
	 * @param string $column_name Column name.
	 * @return bool
	 */
	public static function has_column( string $column_name ): bool {
		global $wpdb;
		$table_name = self::get_table_name();
		if ( ! isset( self::$columns_cache[ $table_name ] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$cols = $wpdb->get_col( "DESCRIBE {$table_name}" );
			self::$columns_cache[ $table_name ] = is_array( $cols ) ? $cols : array();
		}
		return in_array( $column_name, self::$columns_cache[ $table_name ], true );
	}

	/**
	 * Check if tables need to be created or upgraded.
	 */
	public static function check_install_tables(): void {
		$installed_ver = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		if ( version_compare( (string) $installed_ver, self::DB_VERSION, '<' ) ) {
			self::create_tables();
		} else {
			// Self-heal: ensure critical columns always exist even if version was previously updated
			self::ensure_schema();
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
			retry_count int(11) DEFAULT 0 NOT NULL,
			client_id varchar(128) DEFAULT NULL,
			session_id varchar(128) DEFAULT NULL,
			session_number varchar(64) DEFAULT NULL,
			fbp varchar(128) DEFAULT NULL,
			fbc varchar(255) DEFAULT NULL,
			ttp varchar(128) DEFAULT NULL,
			ttclid varchar(255) DEFAULT NULL,
			oai_obref varchar(255) DEFAULT NULL,
			oai_oppref varchar(255) DEFAULT NULL,
			ip varchar(64) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			page_url varchar(500) DEFAULT NULL,
			last_error text,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			processed_at datetime DEFAULT NULL,
			locked_at datetime DEFAULT NULL,
			next_retry_at datetime DEFAULT NULL,
			worker_id varchar(64) DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_id (order_id),
			KEY status (status),
			KEY created_at (created_at),
			KEY locked_at (locked_at),
			KEY next_retry_at (next_retry_at),
			KEY status_locked (status, locked_at),
			KEY status_retry (status, next_retry_at)
		) {$charset_collate};";

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		if ( function_exists( 'dbDelta' ) ) {
			dbDelta( $sql );
		}

		self::ensure_schema();

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * Ensure all required columns and indexes exist without failing on positional AFTER dependencies.
	 */
	public static function ensure_schema(): void {
		global $wpdb;
		static $ensured = false;
		if ( $ensured ) {
			return;
		}

		$table_name = self::get_table_name();

		// Check if table exists first
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $exists !== $table_name ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$columns = $wpdb->get_col( "DESCRIBE {$table_name}" );
		if ( ! is_array( $columns ) || empty( $columns ) ) {
			return;
		}

		$column_defs = array(
			'page_url'        => "ALTER TABLE {$table_name} ADD COLUMN page_url varchar(500) DEFAULT NULL",
			'locked_at'       => "ALTER TABLE {$table_name} ADD COLUMN locked_at datetime DEFAULT NULL",
			'next_retry_at'   => "ALTER TABLE {$table_name} ADD COLUMN next_retry_at datetime DEFAULT NULL",
			'worker_id'       => "ALTER TABLE {$table_name} ADD COLUMN worker_id varchar(64) DEFAULT NULL",
			'openai_status'   => "ALTER TABLE {$table_name} ADD COLUMN openai_status varchar(32) DEFAULT 'pending' NOT NULL",
			'openai_code'     => "ALTER TABLE {$table_name} ADD COLUMN openai_code int(11) DEFAULT NULL",
			'openai_attempts' => "ALTER TABLE {$table_name} ADD COLUMN openai_attempts int(11) DEFAULT 0 NOT NULL",
			'retry_count'     => "ALTER TABLE {$table_name} ADD COLUMN retry_count int(11) DEFAULT 0 NOT NULL",
			'oai_obref'       => "ALTER TABLE {$table_name} ADD COLUMN oai_obref varchar(255) DEFAULT NULL",
			'oai_oppref'      => "ALTER TABLE {$table_name} ADD COLUMN oai_oppref varchar(255) DEFAULT NULL",
			'session_number'  => "ALTER TABLE {$table_name} ADD COLUMN session_number varchar(64) DEFAULT NULL",
		);

		$altered = false;
		foreach ( $column_defs as $col => $alter_sql ) {
			if ( ! in_array( $col, $columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( $alter_sql );
				$altered = true;
			}
		}

		if ( $altered ) {
			self::$columns_cache = array();
		}

		// Check and add indexes if missing
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$indexes   = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
		$key_names = is_array( $indexes ) ? array_unique( array_column( $indexes, 'Key_name' ) ) : array();

		if ( ! in_array( 'next_retry_at', $key_names, true ) && in_array( 'next_retry_at', $columns, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "ALTER TABLE {$table_name} ADD KEY next_retry_at (next_retry_at)" );
		}
		if ( ! in_array( 'status_locked', $key_names, true ) && in_array( 'status', $columns, true ) && in_array( 'locked_at', $columns, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "ALTER TABLE {$table_name} ADD KEY status_locked (status, locked_at)" );
		}
		if ( ! in_array( 'status_retry', $key_names, true ) && in_array( 'status', $columns, true ) && in_array( 'next_retry_at', $columns, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "ALTER TABLE {$table_name} ADD KEY status_retry (status, next_retry_at)" );
		}

		$ensured = true;
	}

	/**
	 * Calculate exponential backoff timestamp for next retry attempt.
	 *
	 * Attempt 1: +2 minutes (120s)
	 * Attempt 2: +5 minutes (300s)
	 * Attempt 3: +15 minutes (900s)
	 * Attempt 4: +30 minutes (1800s)
	 * Attempt 5+: +60 minutes (3600s)
	 *
	 * @param int $attempts Attempt number (1-based).
	 * @return string MySQL datetime string for scheduled retry.
	 */
	public static function calculate_next_retry( int $attempts ): string {
		$delays = array(
			1 => 120,
			2 => 300,
			3 => 900,
			4 => 1800,
			5 => 3600,
		);
		$delay = $delays[ $attempts ] ?? 3600;
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $delay );
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

		// If query failed due to missing table/columns on older schema, upgrade and retry once
		if ( false === $inserted ) {
			self::create_tables();
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
		}

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
			'client_id'      => 'sanitize_text_field',
			'session_id'     => 'sanitize_text_field',
			'session_number' => 'sanitize_text_field',
			'fbp'            => 'sanitize_text_field',
			'fbc'            => 'sanitize_text_field',
			'ttp'            => 'sanitize_text_field',
			'ttclid'         => 'sanitize_text_field',
			'oai_obref'      => 'sanitize_text_field',
			'oai_oppref'     => 'sanitize_text_field',
			'ip'             => 'sanitize_text_field',
			'user_agent'     => 'sanitize_textarea_field',
			'page_url'       => array( __CLASS__, 'sanitize_page_url' ),
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
	 * Sanitize page URL for attribution storage, stripping sensitive parameters.
	 *
	 * @param mixed $url Raw URL string.
	 * @return string
	 */
	public static function sanitize_page_url( $url ): string {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return '';
		}
		if ( class_exists( 'Pubcrawls_Tracking_Dispatcher' ) && method_exists( 'Pubcrawls_Tracking_Dispatcher', 'sanitize_event_url' ) ) {
			return Pubcrawls_Tracking_Dispatcher::sanitize_event_url( $url );
		}
		return esc_url_raw( $url );
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
			'client_id'      => $order->client_id ?? '',
			'session_id'     => $order->session_id ?? '',
			'session_number' => $order->session_number ?? '',
			'fbp'            => $order->fbp ?? '',
			'fbc'            => $order->fbc ?? '',
			'ttp'            => $order->ttp ?? '',
			'ttclid'         => $order->ttclid ?? '',
			'oai_obref'      => $order->oai_obref ?? '',
			'oai_oppref'     => $order->oai_oppref ?? '',
			'ip'             => $order->ip ?? '',
			'user_agent'     => $order->user_agent ?? '',
			'page_url'       => $order->page_url ?? '',
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
		if ( isset( $data['last_error'] ) ) {
			$fields['last_error'] = ! empty( $data['last_error'] ) ? sanitize_text_field( substr( (string) $data['last_error'], 0, 1000 ) ) : null;
		}
		if ( isset( $data['retry_count'] ) ) {
			$fields['retry_count'] = (int) $data['retry_count'];
		}
		if ( array_key_exists( 'next_retry_at', $data ) ) {
			$fields['next_retry_at'] = ! empty( $data['next_retry_at'] ) ? sanitize_text_field( (string) $data['next_retry_at'] ) : null;
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

		// If order-level retry attempts have reached max limit
		if ( isset( $order->retry_count ) && (int) $order->retry_count >= $max ) {
			return true;
		}

		$ga4_enabled    = class_exists( 'Pubcrawls_Tracking_Dispatcher' ) ? Pubcrawls_Tracking_Dispatcher::is_platform_enabled( 'ga4' ) : true;
		$meta_enabled   = class_exists( 'Pubcrawls_Tracking_Dispatcher' ) ? Pubcrawls_Tracking_Dispatcher::is_platform_enabled( 'meta' ) : true;
		$tiktok_enabled = class_exists( 'Pubcrawls_Tracking_Dispatcher' ) ? Pubcrawls_Tracking_Dispatcher::is_platform_enabled( 'tiktok' ) : true;
		$openai_enabled = class_exists( 'Pubcrawls_Tracking_Dispatcher' ) ? Pubcrawls_Tracking_Dispatcher::is_platform_enabled( 'openai' ) : false;

		$ga4_done    = ( ! $ga4_enabled || 'success' === $order->ga4_status || 'skipped' === $order->ga4_status || (int) $order->ga4_attempts >= $max );
		$meta_done   = ( ! $meta_enabled || 'success' === $order->meta_status || 'skipped' === $order->meta_status || (int) $order->meta_attempts >= $max );
		$tiktok_done = ( ! $tiktok_enabled || 'success' === $order->tiktok_status || 'skipped' === $order->tiktok_status || (int) $order->tiktok_attempts >= $max );
		$openai_done = ( ! $openai_enabled || 'success' === ( $order->openai_status ?? 'pending' ) || 'skipped' === ( $order->openai_status ?? 'pending' ) || (int) ( $order->openai_attempts ?? 0 ) >= $max );

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

		$all_success        = true;
		$any_success        = false;
		$max_attempts_found = 0;

		foreach ( $active_platforms as $platform ) {
			$status_col   = "{$platform}_status";
			$attempts_col = "{$platform}_attempts";
			$st           = $order->$status_col ?? 'pending';
			$att          = (int) ( $order->$attempts_col ?? 0 );

			if ( 'success' === $st ) {
				$any_success = true;
			} else {
				$all_success = false;
				if ( $att > $max_attempts_found ) {
					$max_attempts_found = $att;
				}
			}
		}

		$is_max_reached = self::has_exceeded_max_retries( $order_id );

		$new_status    = 'pending';
		$next_retry_at = null;

		if ( $all_success && ! empty( $active_platforms ) ) {
			$new_status    = 'completed';
			$next_retry_at = null;
		} elseif ( $is_max_reached ) {
			$new_status    = 'failed';
			$next_retry_at = null;
		} elseif ( $any_success ) {
			$new_status    = 'partial';
			$next_retry_at = self::calculate_next_retry( max( 1, $max_attempts_found ) );
		} else {
			$new_status    = 'pending';
			$next_retry_at = self::calculate_next_retry( max( 1, $max_attempts_found ) );
		}

		$fields = array(
			'status'        => $new_status,
			'worker_id'     => null,
			'locked_at'     => null,
			'next_retry_at' => $next_retry_at,
			'updated_at'    => current_time( 'mysql' ),
			'processed_at'  => current_time( 'mysql' ),
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
	 * @param string      $order_id      Eventbrite Order ID.
	 * @param string|null $worker_id     Optional worker ID to verify ownership.
	 * @param string      $status        Status to reset to ('pending', 'failed', 'partial').
	 * @param string|null $next_retry_at Optional scheduled datetime for next retry.
	 * @return bool
	 */
	public static function release_order_lock( string $order_id, ?string $worker_id = null, string $status = 'pending', ?string $next_retry_at = null ): bool {
		global $wpdb;
		$table_name = self::get_table_name();
		$order_id   = sanitize_text_field( trim( $order_id ) );
		$now        = current_time( 'mysql' );

		if ( null !== $next_retry_at ) {
			if ( $worker_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$res = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table_name}
						SET status = %s, worker_id = NULL, locked_at = NULL, next_retry_at = %s, updated_at = %s
						WHERE order_id = %s AND worker_id = %s",
						$status,
						$next_retry_at,
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
						SET status = %s, worker_id = NULL, locked_at = NULL, next_retry_at = %s, updated_at = %s
						WHERE order_id = %s",
						$status,
						$next_retry_at,
						$now,
						$order_id
					)
				);
			}
		} else {
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
		}

		return false !== $res;
	}

	/**
	 * Find and safely release stale processing locks.
	 *
	 * Resets orders locked longer than $stale_timeout_seconds back to 'pending'.
	 * If an order has already exceeded max retries, transitions to 'failed'.
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
		$max        = self::MAX_ATTEMPTS;

		$where_clause = $force_all
			? "status = 'processing'"
			: $wpdb->prepare(
				"status = 'processing' AND ((locked_at IS NOT NULL AND locked_at < %s) OR (locked_at IS NULL AND updated_at < %s))",
				$threshold,
				$threshold
			);

		// First, for any stale processing orders that have already reached max attempts, reset to 'failed'
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name}
				SET status = 'failed',
					worker_id = NULL,
					locked_at = NULL,
					next_retry_at = NULL,
					updated_at = %s
				WHERE {$where_clause}
				  AND (retry_count >= %d OR (ga4_attempts >= %d AND meta_attempts >= %d AND tiktok_attempts >= %d AND openai_attempts >= %d))",
				$now,
				$max,
				$max,
				$max,
				$max,
				$max
			)
		);

		// Second, release remaining eligible stale processing orders back to 'pending'
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$affected = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name}
				SET status = 'pending',
					worker_id = NULL,
					locked_at = NULL,
					next_retry_at = NULL,
					updated_at = %s
				WHERE {$where_clause}",
				$now
			)
		);

		return (int) $affected;
	}

	/**
	 * Retrieve queued, pending, or retryable orders for background processing.
	 *
	 * Enforces:
	 * 1. Fresh pending orders prioritized first (retry_count = 0) so new orders are processed immediately.
	 * 2. Orders scheduled for future retry (next_retry_at > NOW()) are excluded until their time arrives.
	 * 3. Orders exceeding MAX_ATTEMPTS are strictly excluded.
	 *
	 * @param int $limit Maximum orders to retrieve.
	 * @param int $stale_timeout_seconds Stale lock threshold in seconds.
	 * @return array List of order row objects.
	 */
	public static function get_queued_orders( int $limit = 20, int $stale_timeout_seconds = 600 ): array {
		global $wpdb;
		$table_name = self::get_table_name();
		$now        = current_time( 'mysql' );
		$threshold  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $stale_timeout_seconds );
		$max        = self::MAX_ATTEMPTS;

		self::ensure_schema();

		$rows = null;

		if ( self::has_column( 'retry_count' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name}
					WHERE (
						-- 1. Fresh pending orders (next_retry_at has arrived or NULL, retry_count < MAX_ATTEMPTS)
						(
							status IN ('pending', 'queued')
							AND (retry_count < %d)
							AND (next_retry_at IS NULL OR next_retry_at <= %s)
							AND (ga4_attempts < %d OR meta_attempts < %d OR tiktok_attempts < %d OR openai_attempts < %d)
						)
						-- 2. Partial orders with retries remaining (next_retry_at has arrived or NULL)
						OR (
							status = 'partial'
							AND (retry_count < %d)
							AND (next_retry_at IS NULL OR next_retry_at <= %s)
							AND (
								(ga4_status NOT IN ('success', 'skipped') AND ga4_attempts < %d)
								OR (meta_status NOT IN ('success', 'skipped') AND meta_attempts < %d)
								OR (tiktok_status NOT IN ('success', 'skipped') AND tiktok_attempts < %d)
								OR (openai_status NOT IN ('success', 'skipped') AND openai_attempts < %d)
							)
						)
						-- 3. Stale locks needing recovery
						OR (
							status = 'processing'
							AND (
								(locked_at IS NOT NULL AND locked_at < %s)
								OR (locked_at IS NULL AND updated_at < %s)
							)
						)
					)
					ORDER BY
						-- Prioritize fresh pending orders first so new customers are tracked immediately without delay
						CASE WHEN status IN ('pending', 'queued') AND retry_count = 0 THEN 0 ELSE 1 END ASC,
						id ASC
					LIMIT %d",
					// 1. Pending:
					$max,
					$now,
					$max,
					$max,
					$max,
					$max,
					// 2. Partial:
					$max,
					$now,
					$max,
					$max,
					$max,
					$max,
					// 3. Stale locks:
					$threshold,
					$threshold,
					// Limit:
					$limit
				)
			);
		}

		// Fallback for older database schemas where retry_count or next_retry_at column might not exist yet
		if ( empty( $rows ) && ! is_array( $rows ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name}
					WHERE (
						status IN ('pending', 'queued')
						OR (
							status IN ('failed', 'partial')
							AND (ga4_attempts < %d OR meta_attempts < %d OR tiktok_attempts < %d)
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
					$threshold,
					$threshold,
					$limit
				)
			);
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Get aggregated queue and health metrics with strict mutual exclusivity and resilient fallbacks.
	 *
	 * Categorizes orders into mutually exclusive buckets:
	 * - Pending / Backlog: Unprocessed or newly queued orders eligible for initial processing.
	 * - Active / Processing: Orders currently locked and actively being executed by a live worker.
	 * - Stale Locks: Orders locked for longer than 10 minutes without completion.
	 * - Completed: All active tracking platforms delivered successfully.
	 * - Partial (Retrying): At least one platform succeeded, remaining platforms have retries left.
	 * - Failed (Max): Exceeded retry limit on all failed platforms or rejected by ownership verification.
	 *
	 * @param int $stale_timeout_seconds Stale lock threshold in seconds.
	 * @return array Aggregated counts.
	 */
	public static function get_queue_counts( int $stale_timeout_seconds = 600 ): array {
		global $wpdb;
		$table_name = self::get_table_name();
		$now        = current_time( 'mysql' );
		$threshold  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $stale_timeout_seconds );
		$max        = self::MAX_ATTEMPTS;

		self::ensure_schema();

		$row = null;

		// Tier 1: Refined query with retry_count and next_retry_at support
		if ( self::has_column( 'retry_count' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) AS total,
						-- 1. Pending / Backlog: genuinely pending/queued orders that have not exhausted retries
						SUM(CASE
							WHEN status IN ('pending', 'queued')
							     AND (retry_count < %d)
							     AND (ga4_attempts < %d OR meta_attempts < %d OR tiktok_attempts < %d OR openai_attempts < %d)
							THEN 1 ELSE 0
						END) AS pending,

						-- Diagnostic: pending ready to process right now
						SUM(CASE
							WHEN status IN ('pending', 'queued')
							     AND (retry_count < %d)
							     AND (next_retry_at IS NULL OR next_retry_at <= %s)
							     AND (ga4_attempts < %d OR meta_attempts < %d OR tiktok_attempts < %d OR openai_attempts < %d)
							THEN 1 ELSE 0
						END) AS pending_immediate,

						-- Diagnostic: scheduled for future retry time
						SUM(CASE
							WHEN status IN ('pending', 'queued', 'partial')
							     AND next_retry_at IS NOT NULL AND next_retry_at > %s
							THEN 1 ELSE 0
						END) AS retry_scheduled,

						-- 2. Active workers: processing with live lock
						SUM(CASE
							WHEN status = 'processing'
							     AND ((locked_at IS NOT NULL AND locked_at >= %s) OR (locked_at IS NULL AND updated_at >= %s))
							THEN 1 ELSE 0
						END) AS processing_active,

						-- 3. Stale locks: processing with expired lock
						SUM(CASE
							WHEN status = 'processing'
							     AND ((locked_at IS NOT NULL AND locked_at < %s) OR (locked_at IS NULL AND updated_at < %s))
							THEN 1 ELSE 0
						END) AS stale_locks,

						-- 4. Completed: fully delivered
						SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,

						-- 5. Partial (Retrying): partial success with platforms still eligible for retry
						SUM(CASE
							WHEN status = 'partial'
							     AND (retry_count < %d)
							     AND (
							         (ga4_status NOT IN ('success', 'skipped') AND ga4_attempts < %d)
							         OR (meta_status NOT IN ('success', 'skipped') AND meta_attempts < %d)
							         OR (tiktok_status NOT IN ('success', 'skipped') AND tiktok_attempts < %d)
							         OR (openai_status NOT IN ('success', 'skipped') AND openai_attempts < %d)
							     )
							THEN 1 ELSE 0
						END) AS partial,

						-- 6. Failed (Max): exhausted retries or explicitly failed/rejected
						SUM(CASE
							WHEN status IN ('failed', 'rejected')
							     OR (status IN ('pending', 'queued') AND (
							         retry_count >= %d
							         OR (ga4_attempts >= %d AND meta_attempts >= %d AND tiktok_attempts >= %d AND openai_attempts >= %d)
							     ))
							     OR (status = 'partial' AND (
							         retry_count >= %d
							         OR (
							             (ga4_status IN ('success', 'skipped') OR ga4_attempts >= %d)
							             AND (meta_status IN ('success', 'skipped') OR meta_attempts >= %d)
							             AND (tiktok_status IN ('success', 'skipped') OR tiktok_attempts >= %d)
							             AND (openai_status IN ('success', 'skipped') OR openai_attempts >= %d)
							         )
							     ))
							THEN 1 ELSE 0
						END) AS failed,

						SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
						SUM(CASE WHEN ga4_status = 'success' THEN 1 ELSE 0 END) AS ga4_success,
						SUM(CASE WHEN meta_status = 'success' THEN 1 ELSE 0 END) AS meta_success,
						SUM(CASE WHEN tiktok_status = 'success' THEN 1 ELSE 0 END) AS tiktok_success,
						SUM(CASE WHEN openai_status = 'success' THEN 1 ELSE 0 END) AS openai_success
					FROM {$table_name}",
					// pending:
					$max, $max, $max, $max, $max,
					// pending_immediate:
					$max, $now, $max, $max, $max, $max,
					// retry_scheduled:
					$now,
					// processing_active:
					$threshold, $threshold,
					// stale_locks:
					$threshold, $threshold,
					// partial:
					$max, $max, $max, $max, $max,
					// failed:
					$max, $max, $max, $max, $max,
					$max, $max, $max, $max, $max
				),
				ARRAY_A
			);
		}

		// Tier 2 Fallback: if refined query returned null/empty or retry_count is missing, query basic status
		if ( empty( $row ) ) {
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
						SUM(CASE WHEN status IN ('failed', 'rejected') THEN 1 ELSE 0 END) AS failed,
						SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
						SUM(CASE WHEN ga4_status = 'success' THEN 1 ELSE 0 END) AS ga4_success,
						SUM(CASE WHEN meta_status = 'success' THEN 1 ELSE 0 END) AS meta_success,
						SUM(CASE WHEN tiktok_status = 'success' THEN 1 ELSE 0 END) AS tiktok_success
					FROM {$table_name}",
					$threshold,
					$threshold,
					$threshold,
					$threshold
				),
				ARRAY_A
			);
			if ( is_array( $row ) ) {
				$row['pending_immediate'] = $row['pending'] ?? 0;
				$row['retry_scheduled']   = 0;
				$row['openai_success']    = 0;
			}
		}

		// Tier 3 Fallback: ultra-basic status aggregation if legacy table lacks platform status columns
		if ( empty( $row ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row = $wpdb->get_row(
				"SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN status IN ('pending', 'queued') THEN 1 ELSE 0 END) AS pending,
					SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_active,
					0 AS stale_locks,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
					SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) AS partial,
					SUM(CASE WHEN status IN ('failed', 'rejected') THEN 1 ELSE 0 END) AS failed,
					0 AS rejected,
					0 AS ga4_success,
					0 AS meta_success,
					0 AS tiktok_success,
					0 AS openai_success
				FROM {$table_name}",
				ARRAY_A
			);
			if ( is_array( $row ) ) {
				$row['pending_immediate'] = $row['pending'] ?? 0;
				$row['retry_scheduled']   = 0;
			}
		}

		return array_map( 'intval', $row ?: array(
			'total'             => 0,
			'pending'           => 0,
			'pending_immediate' => 0,
			'retry_scheduled'   => 0,
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
	 * Safe Backlog and State Transition Repair Helper.
	 *
	 * Reprocesses stuck or inconsistent states without data loss:
	 * 1. Releases any stale worker locks older than 10 minutes.
	 * 2. Moves orders with exhausted retries (attempts >= 5) to 'failed'.
	 * 3. Resets expired next_retry_at timestamps so due orders are immediately claimable.
	 * 4. Ensures valid pending orders are ready for immediate queue worker consumption.
	 *
	 * @return array{stale_released: int, max_exhausted_fixed: int, retry_cleared: int}
	 */
	public static function repair_backlog_states(): array {
		global $wpdb;
		$table_name = self::get_table_name();
		$now        = current_time( 'mysql' );
		$max        = self::MAX_ATTEMPTS;

		self::ensure_schema();

		// 1. Release stale locks
		$stale_released = self::release_stale_locks( 600, true );

		$max_exhausted_fixed = 0;
		$retry_cleared       = 0;

		if ( self::has_column( 'retry_count' ) && self::has_column( 'next_retry_at' ) ) {
			// 2. Orders that have reached max attempts on all failed platforms but are still marked pending/partial
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$max_exhausted_fixed = (int) $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name}
					SET status = 'failed',
						next_retry_at = NULL,
						updated_at = %s
					WHERE (status IN ('pending', 'queued') OR status = 'partial')
					  AND (retry_count >= %d OR (
					      (ga4_status IN ('success', 'skipped') OR ga4_attempts >= %d)
					      AND (meta_status IN ('success', 'skipped') OR meta_attempts >= %d)
					      AND (tiktok_status IN ('success', 'skipped') OR tiktok_attempts >= %d)
					      AND (openai_status IN ('success', 'skipped') OR openai_attempts >= %d)
					  ))",
					$now,
					$max,
					$max,
					$max,
					$max,
					$max
				)
			);

			// 3. Reset expired next_retry_at timestamps so due retries are immediately claimable
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retry_cleared = (int) $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name}
					SET next_retry_at = NULL,
						updated_at = %s
					WHERE next_retry_at IS NOT NULL AND next_retry_at <= %s",
					$now,
					$now
				)
			);
		} else {
			// Fallback repair for schemas where retry_count or next_retry_at is not present
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$max_exhausted_fixed = (int) $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name}
					SET status = 'failed',
						updated_at = %s
					WHERE (status IN ('pending', 'queued') OR status = 'partial')
					  AND (
					      (ga4_status IN ('success', 'skipped') OR ga4_attempts >= %d)
					      AND (meta_status IN ('success', 'skipped') OR meta_attempts >= %d)
					      AND (tiktok_status IN ('success', 'skipped') OR tiktok_attempts >= %d)
					  )",
					$now,
					$max,
					$max,
					$max
				)
			);
		}

		return array(
			'stale_released'      => $stale_released,
			'max_exhausted_fixed' => $max_exhausted_fixed,
			'retry_cleared'       => $retry_cleared,
		);
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

		self::ensure_schema();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY id DESC LIMIT %d",
				$limit
			)
		);

		return is_array( $rows ) ? $rows : array();
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
