<?php
/**
 * PubCrawls Eventbrite Background Queue & Worker Manager
 *
 * Provides a resilient, self-healing background queue runner for processing
 * Eventbrite orders without relying exclusively on visitor-triggered WP-Cron.
 *
 * Supports:
 * 1. Recurring WP-Cron task (every 5 minutes)
 * 2. Action Scheduler runner (if Action Scheduler is installed)
 * 3. Dedicated authenticated REST endpoint for real server cron & WP Engine scheduled tasks
 * 4. Stale lock detection & automatic reclamation (10-minute timeout)
 * 5. Atomic per-order compare-and-swap locking
 * 6. Admin AJAX actions for manual backlog replay and stale lock release
 *
 * @package Pubcrawl_Addon
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pubcrawls_Eventbrite_Queue {

	/**
	 * Hook names and options.
	 */
	const CRON_HOOK            = 'pubcrawls_eventbrite_queue_cron';
	const AS_HOOK              = 'pubcrawls_eventbrite_as_queue_worker';
	const AS_GROUP             = 'pubcrawls-tracking';
	const LAST_RUN_OPTION      = 'pubcrawls_eb_last_queue_run';
	const LAST_SUMMARY_OPTION  = 'pubcrawls_eb_last_queue_summary';
	const STALE_LOCK_TIMEOUT   = 600; // 10 minutes in seconds
	const DEFAULT_BATCH_LIMIT  = 25;

	/**
	 * Initialize queue hooks, schedules, and endpoints.
	 */
	public static function init(): void {
		// Custom cron schedule interval (2 minutes)
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_interval' ) );

		// Scheduled cron workers
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scheduled_queue' ) );
		add_action( self::AS_HOOK, array( __CLASS__, 'run_scheduled_queue' ) );

		// Schedule check on init
		add_action( 'init', array( __CLASS__, 'ensure_cron_scheduled' ) );

		// REST endpoint for real server cron (WP Engine, crontab, or external monitors)
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		// Admin AJAX handlers
		add_action( 'wp_ajax_pubcrawls_process_eb_backlog', array( __CLASS__, 'ajax_process_backlog' ) );
		add_action( 'wp_ajax_pubcrawls_release_stale_locks', array( __CLASS__, 'ajax_release_stale_locks' ) );
		add_action( 'wp_ajax_pubcrawls_repair_backlog', array( __CLASS__, 'ajax_repair_backlog' ) );
		add_action( 'wp_ajax_pubcrawls_get_queue_health', array( __CLASS__, 'ajax_get_queue_health' ) );
	}

	/**
	 * Register 30-second interval for WP-Cron.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public static function add_cron_interval( array $schedules ): array {
		$schedules['pubcrawls_thirty_seconds'] = array(
			'interval' => 30,
			'display'  => esc_html__( 'Every 30 Seconds (PubCrawls Queue)', 'pubcrawls-addon' ),
		);
		// Keep alias for backwards compatibility
		$schedules['pubcrawls_two_minutes'] = array(
			'interval' => 30,
			'display'  => esc_html__( 'Every 30 Seconds (PubCrawls Queue)', 'pubcrawls-addon' ),
		);
		return $schedules;
	}

	/**
	 * Ensure the recurring queue worker is scheduled in WP-Cron and Action Scheduler.
	 */
	public static function ensure_cron_scheduled(): void {
		// WP-Cron recurring schedule: ensure 30-second schedule
		$current_schedule = wp_get_schedule( self::CRON_HOOK );
		if ( $current_schedule && 'pubcrawls_thirty_seconds' !== $current_schedule ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 30, 'pubcrawls_thirty_seconds', self::CRON_HOOK );
		}

		// Action Scheduler recurring schedule (if available)
		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( ! as_has_scheduled_action( self::AS_HOOK, array(), self::AS_GROUP ) ) {
				as_schedule_recurring_action(
					time() + 30,
					30,
					self::AS_HOOK,
					array(),
					self::AS_GROUP
				);
			}
		}
	}

	/**
	 * Register dedicated authenticated REST endpoint for real server cron.
	 *
	 * URL: /wp-json/pubcrawls/v1/eventbrite/process-queue?secret=TOKEN
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			Pubcrawls_Eventbrite_Webhook::REST_NAMESPACE,
			'/eventbrite/process-queue',
			array(
				array(
					'methods'             => array( 'GET', 'POST' ),
					'callback'            => array( __CLASS__, 'handle_rest_process_queue' ),
					'permission_callback' => array( __CLASS__, 'verify_rest_secret' ),
				),
			)
		);
	}

	/**
	 * Verify authentication secret for the queue REST endpoint.
	 *
	 * Accepts secret in query param `?secret=...` or header `X-PubCrawls-Secret: ...`.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return bool|WP_Error
	 */
	public static function verify_rest_secret( WP_REST_Request $request ) {
		$provided = (string) ( $request->get_param( 'secret' ) ?: $request->get_header( 'x-pubcrawls-secret' ) );
		$expected = Pubcrawls_Eventbrite_Webhook::get_webhook_secret();

		if ( empty( $provided ) || ! hash_equals( $expected, $provided ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'Invalid or missing queue worker authorization secret.', 'pubcrawls-addon' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * REST Callback: Execute queue batch processing triggered by server cron.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public static function handle_rest_process_queue( WP_REST_Request $request ): WP_REST_Response {
		$limit  = (int) ( $request->get_param( 'limit' ) ?: self::DEFAULT_BATCH_LIMIT );
		$limit  = max( 1, min( 100, $limit ) );
		$result = self::process_queue_batch( $limit );

		return new WP_REST_Response(
			array(
				'success' => true,
				'trigger' => 'server_cron_rest',
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * Runner invoked by recurring WP-Cron or Action Scheduler.
	 */
	public static function run_scheduled_queue(): void {
		self::process_queue_batch( self::DEFAULT_BATCH_LIMIT );
	}

	/**
	 * Core Batch Processor: Safely processes pending/failed/stuck orders.
	 *
	 * 1. Automatically reclaims any stale locks older than 10 minutes.
	 * 2. Fetches up to $limit orders needing delivery or retry.
	 * 3. Uses atomic compare-and-swap locking with unique worker ID to prevent concurrency.
	 * 4. Respects per-platform status and deduplication (never resends successful platforms).
	 * 5. Uses try-catch error boundary so a single failure never kills the queue.
	 *
	 * @param int $limit Maximum number of orders to process in this run.
	 * @return array Batch execution summary.
	 */
	public static function process_queue_batch( int $limit = 25 ): array {
		$start_time = microtime( true );
		$now_mysql  = current_time( 'mysql' );

		// Step 1: Safely release any stale locks older than 10 minutes
		$stale_released = Pubcrawls_Tracking_DB::release_stale_locks( self::STALE_LOCK_TIMEOUT );

		// Step 2: Fetch queued, pending, or retryable orders
		$orders = Pubcrawls_Tracking_DB::get_queued_orders( $limit, self::STALE_LOCK_TIMEOUT );

		$worker_id = 'queue_' . wp_generate_password( 8, false ) . '_' . ( function_exists( 'getmypid' ) ? (string) getmypid() : '0' );

		$summary = array(
			'timestamp'       => $now_mysql,
			'worker_id'       => $worker_id,
			'stale_released'  => $stale_released,
			'total_found'     => count( $orders ),
			'processed'       => 0,
			'completed'       => 0,
			'partial'         => 0,
			'failed'          => 0,
			'skipped_locks'   => 0,
			'ga4_sent'        => 0,
			'meta_sent'       => 0,
			'tiktok_sent'     => 0,
			'openai_sent'     => 0,
			'details'         => array(),
			'duration_ms'     => 0,
		);

		foreach ( $orders as $order ) {
			$order_id = sanitize_text_field( (string) $order->order_id );

			// Step 3: Acquire atomic processing lock
			$locked = Pubcrawls_Tracking_DB::acquire_processing_lock( $order_id, $worker_id, self::STALE_LOCK_TIMEOUT );
			if ( ! $locked ) {
				$summary['skipped_locks']++;
				continue;
			}

			$summary['processed']++;

			try {
				// Step 4: Process order (pass $worker_id so it doesn't double-lock)
				$res = Pubcrawls_Eventbrite_Webhook::process_order_now( $order_id, false, false, $worker_id );

				$status = $res['overall_status'] ?? ( $res['success'] ? 'completed' : 'failed' );

				if ( 'completed' === $status ) {
					$summary['completed']++;
				} elseif ( 'partial' === $status ) {
					$summary['partial']++;
				} else {
					$summary['failed']++;
				}

				// If order is still marked as processing, safely release lock
				$order_row = Pubcrawls_Tracking_DB::get_order( $order_id );
				if ( $order_row && 'processing' === $order_row->status ) {
					Pubcrawls_Tracking_DB::release_order_lock( $order_id, $worker_id, $status );
				}

				if ( ! empty( $res['platforms']['ga4']['success'] ) && empty( $res['platforms']['ga4']['skipped'] ) ) {
					$summary['ga4_sent']++;
				}
				if ( ! empty( $res['platforms']['meta']['success'] ) && empty( $res['platforms']['meta']['skipped'] ) ) {
					$summary['meta_sent']++;
				}
				if ( ! empty( $res['platforms']['tiktok']['success'] ) && empty( $res['platforms']['tiktok']['skipped'] ) ) {
					$summary['tiktok_sent']++;
				}
				if ( ! empty( $res['platforms']['openai']['success'] ) && empty( $res['platforms']['openai']['skipped'] ) ) {
					$summary['openai_sent']++;
				}

				$summary['details'][] = array(
					'order_id' => $order_id,
					'status'   => $status,
					'success'  => $res['success'] ?? false,
				);
			} catch ( \Throwable $e ) {
				$summary['failed']++;
				$order_row       = Pubcrawls_Tracking_DB::get_order( $order_id );
				$current_retries = (int) ( $order_row->retry_count ?? 0 ) + 1;
				$max_reached     = $current_retries >= Pubcrawls_Tracking_DB::MAX_ATTEMPTS;
				$next_retry      = $max_reached ? null : Pubcrawls_Tracking_DB::calculate_next_retry( $current_retries );

				Pubcrawls_Tracking_DB::release_order_lock( $order_id, $worker_id, 'failed', $next_retry );
				Pubcrawls_Tracking_DB::update_order_data(
					$order_id,
					array(
						'status'        => 'failed',
						'retry_count'   => $current_retries,
						'next_retry_at' => $next_retry,
						'last_error'    => 'Queue worker exception: ' . $e->getMessage(),
					)
				);
				$summary['details'][] = array(
					'order_id' => $order_id,
					'status'   => 'failed',
					'error'    => $e->getMessage(),
				);
			}
		}

		$summary['duration_ms'] = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		// Record execution metrics
		update_option( self::LAST_RUN_OPTION, time(), false );
		update_option( self::LAST_SUMMARY_OPTION, $summary, false );

		return $summary;
	}

	/**
	 * AJAX Handler: Admin manually triggers backlog processing batch.
	 */
	public static function ajax_process_backlog(): void {
		check_ajax_referer( 'pubcrawls_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$limit  = isset( $_POST['limit'] ) ? max( 1, min( 100, (int) $_POST['limit'] ) ) : 50;
		$result = self::process_queue_batch( $limit );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX Handler: Admin manually releases stale worker locks.
	 */
	public static function ajax_release_stale_locks(): void {
		check_ajax_referer( 'pubcrawls_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$force    = ! empty( $_POST['force'] );
		$released = Pubcrawls_Tracking_DB::release_stale_locks( self::STALE_LOCK_TIMEOUT, $force );

		// If 0 released normally, check if any active/stuck processing locks exist and release them
		if ( 0 === $released && ! $force ) {
			$counts = Pubcrawls_Tracking_DB::get_queue_counts( self::STALE_LOCK_TIMEOUT );
			if ( ! empty( $counts['processing_active'] ) ) {
				$released = Pubcrawls_Tracking_DB::release_stale_locks( self::STALE_LOCK_TIMEOUT, true );
			}
		}

		wp_send_json_success(
			array(
				'released_count' => $released,
				'message'        => sprintf(
					/* translators: %d: Number of released locks */
					esc_html__( 'Successfully released %d processing locks back to pending queue.', 'pubcrawls-addon' ),
					$released
				),
			)
		);
	}

	/**
	 * AJAX Handler: Admin manually triggers safe backlog repair and state realignment.
	 */
	public static function ajax_repair_backlog(): void {
		check_ajax_referer( 'pubcrawls_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$result = Pubcrawls_Tracking_DB::repair_backlog_states();
		wp_send_json_success(
			array(
				'repair_result' => $result,
				'message'       => sprintf(
					/* translators: 1: Stale locks released, 2: Max attempts realigned */
					esc_html__( 'Backlog repaired: %1$d stale locks recovered, %2$d max-attempt rows realigned.', 'pubcrawls-addon' ),
					$result['stale_released'],
					$result['max_exhausted_fixed']
				),
			)
		);
	}

	/**
	 * AJAX Handler: Fetch real-time queue health diagnostics.
	 */
	public static function ajax_get_queue_health(): void {
		check_ajax_referer( 'pubcrawls_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		wp_send_json_success( self::get_health_diagnostics() );
	}

	/**
	 * Get comprehensive queue health diagnostics.
	 *
	 * @return array
	 */
	public static function get_health_diagnostics(): array {
		$counts    = Pubcrawls_Tracking_DB::get_queue_counts( self::STALE_LOCK_TIMEOUT );
		$last_run  = (int) get_option( self::LAST_RUN_OPTION, 0 );
		$next_cron = wp_next_scheduled( self::CRON_HOOK );
		$secret    = Pubcrawls_Eventbrite_Webhook::get_webhook_secret();

		$as_active = function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::AS_HOOK, array(), self::AS_GROUP );
		$cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

		$endpoint_url = rest_url( sprintf( '%s/eventbrite/process-queue?secret=%s', Pubcrawls_Eventbrite_Webhook::REST_NAMESPACE, rawurlencode( $secret ) ) );
		$cron_command = sprintf( '* * * * * curl -fsS -m 25 "%1$s" >/dev/null 2>&1; sleep 30; curl -fsS -m 25 "%1$s" >/dev/null 2>&1', $endpoint_url );

		$worker_status = 'active';
		if ( $counts['stale_locks'] > 0 ) {
			$worker_status = 'stale_locks_detected';
		} elseif ( $counts['pending'] > 0 && $last_run > 0 && ( time() - $last_run ) > 600 ) {
			$worker_status = 'warning_delayed';
		}

		return array(
			'worker_status'          => $worker_status,
			'counts'                 => $counts,
			'last_run'               => $last_run,
			'last_run_human'         => $last_run > 0 ? human_time_diff( $last_run, time() ) . ' ago' : 'Never',
			'next_cron_run'          => $next_cron ?: null,
			'next_cron_human'        => $next_cron ? human_time_diff( time(), $next_cron ) . ' from now' : 'Not scheduled',
			'wp_cron_disabled'       => $cron_disabled,
			'action_scheduler_active'=> $as_active,
			'server_cron_endpoint'   => $endpoint_url,
			'server_cron_command'    => $cron_command,
			'last_summary'           => get_option( self::LAST_SUMMARY_OPTION, null ),
		);
	}
}
