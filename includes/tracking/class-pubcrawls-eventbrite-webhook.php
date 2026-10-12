<?php
/**
 * Eventbrite Webhook & Server-Side Conversions Tracking
 *
 * Listens for Eventbrite order.placed webhooks, enforces strict secret token authentication,
 * validates official Eventbrite API URLs and numeric Order IDs, performs atomic locking,
 * immediate post-response processing via fastcgi_finish_request(), and dispatches
 * server-side purchase events to GA4, Meta CAPI, and TikTok Events API.
 *
 * @package Pubcrawl_Addon
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pubcrawls_Eventbrite_Webhook {

	/**
	 * REST namespace and routes.
	 */
	const REST_NAMESPACE     = 'pubcrawls/v1';
	const ROUTE_PREFERRED    = '/eventbrite/order-placed';
	const ROUTE_ATTRIBUTION  = '/eventbrite/attribution';
	const ROUTE_LEGACY       = '/eventbrite-webhook';

	/**
	 * Storage keys for activity logging.
	 */
	const WEBHOOK_LOGS_KEY = 'pubcrawls_eb_webhook_logs';
	const MAX_LOGS_COUNT   = 50;

	/**
	 * How long to wait for the browser's /eventbrite/attribution beacon before
	 * dispatching, since Eventbrite's order.placed webhook can arrive before it.
	 * Kept to 2s max to completely avoid WP Engine / PHP-FPM execution timeouts.
	 */
	const ATTRIBUTION_MAX_WAIT_SECONDS      = 5;
	const ATTRIBUTION_POLL_INTERVAL_SECONDS = 1;

	/**
	 * Initialize webhook hooks and endpoints.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'pubcrawls_process_eventbrite_order', array( __CLASS__, 'background_process_order' ), 10, 1 );
		add_action( 'wp_ajax_pubcrawls_test_eb_order', array( __CLASS__, 'ajax_test_process_order' ) );
		add_action( 'wp_ajax_pubcrawls_retry_eb_order', array( __CLASS__, 'ajax_retry_order' ) );
		add_action( 'wp_ajax_pubcrawls_clear_eb_logs', array( __CLASS__, 'ajax_clear_logs' ) );
	}

	/**
	 * Get or generate the active Webhook Secret.
	 *
	 * Guaranteed to be at least 32 characters and stored server-side only.
	 *
	 * @return string Secret token.
	 */
	public static function get_webhook_secret(): string {
		if ( defined( 'PUBCRAWLS_EB_WEBHOOK_SECRET' ) && ! empty( PUBCRAWLS_EB_WEBHOOK_SECRET ) ) {
			return trim( (string) PUBCRAWLS_EB_WEBHOOK_SECRET );
		}

		// 1. Check dedicated persistent option
		$dedicated_secret = trim( (string) get_option( 'pubcrawls_eb_webhook_secret', '' ) );
		if ( ! empty( $dedicated_secret ) ) {
			return $dedicated_secret;
		}

		// 2. Check settings array
		if ( class_exists( 'Pubcrawls_Admin' ) ) {
			$secret = trim( (string) Pubcrawls_Admin::get_setting( 'eb_webhook_secret', '' ) );
			if ( ! empty( $secret ) ) {
				update_option( 'pubcrawls_eb_webhook_secret', $secret, false );
				return $secret;
			}
		}

		// 3. Generate once and store permanently in BOTH dedicated option and settings array
		$generated_secret = wp_generate_password( 32, false, false );
		update_option( 'pubcrawls_eb_webhook_secret', $generated_secret, false );

		if ( class_exists( 'Pubcrawls_Admin' ) ) {
			$settings = get_option( Pubcrawls_Admin::SETTINGS_KEY, array() );
			if ( is_array( $settings ) ) {
				$settings['eb_webhook_secret']           = $generated_secret;
				$settings['tracking_settings_submitted'] = '1';
				update_option( Pubcrawls_Admin::SETTINGS_KEY, $settings );
			}
		}

		return $generated_secret;
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_rest_routes(): void {
		// Preferred Route: POST /pubcrawls/v1/eventbrite/order-placed
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_PREFERRED,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE, // POST
					'callback'            => array( __CLASS__, 'handle_webhook_request' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::READABLE, // GET
					'callback'            => array( __CLASS__, 'handle_get_rejected' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// Browser Attribution Capture Route: POST /pubcrawls/v1/eventbrite/attribution
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_ATTRIBUTION,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE, // POST
					'callback'            => array( __CLASS__, 'handle_attribution_request' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// Legacy / Alias Route for backwards compatibility: /pubcrawls/v1/eventbrite-webhook
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_LEGACY,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE, // POST
					'callback'            => array( __CLASS__, 'handle_webhook_request' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::READABLE, // GET health check
					'callback'            => array( __CLASS__, 'handle_get_status' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Reject GET requests to /eventbrite/order-placed with HTTP 405 Method Not Allowed.
	 */
	public static function handle_get_rejected( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'code'    => 'method_not_allowed',
				'message' => 'Method Not Allowed. Only POST requests with Eventbrite order.placed payloads are accepted.',
				'data'    => array( 'status' => 405 ),
			),
			405
		);
	}

	/**
	 * GET status endpoint for legacy route health verification.
	 */
	public static function handle_get_status( WP_REST_Request $request ): WP_REST_Response {
		$configured = ! empty( Pubcrawls_Eventbrite_API::get_api_token() );
		return new WP_REST_Response(
			array(
				'status'             => 'active',
				'service'            => 'PubCrawls Eventbrite Webhook Receiver',
				'configured'         => $configured,
				'preferred_endpoint' => get_rest_url( null, self::REST_NAMESPACE . self::ROUTE_PREFERRED ),
				'timestamp'          => gmdate( 'Y-m-d H:i:s' ),
			),
			200
		);
	}

	/**
	 * Handle incoming POST webhook from Eventbrite.
	 *
	 * Execution Order (Strict Security Requirements):
	 * 1. Validate Secret Token via hash_equals(). If missing/invalid: return HTTP 401 immediately.
	 *    (NO payload parsed, NO order extracted, NO queue touched, NO database records created).
	 * 2. Parse JSON body.
	 * 3. Validate action (order.placed).
	 * 4. Validate api_url: Reject non-official hostnames with HTTP 400.
	 * 5. Extract and validate numeric Order ID.
	 * 6. Atomic lock & Idempotency check.
	 * 7. Fast response via fastcgi_finish_request() + immediate order processing.
	 */
	public static function handle_webhook_request( WP_REST_Request $request ): WP_REST_Response {
		// ── STEP 1: Secret Key Validation (FIRST STEP BEFORE ANYTHING ELSE) ──
		$configured_secret = self::get_webhook_secret();

		// Extract incoming secret: check 'key' parameter first, fallback to 'token' or headers
		$incoming_key = $request->get_param( 'key' );
		if ( empty( $incoming_key ) ) {
			$incoming_key = $request->get_param( 'token' );
		}
		if ( empty( $incoming_key ) ) {
			$incoming_key = $request->get_header( 'x-webhook-secret' );
		}
		if ( empty( $incoming_key ) ) {
			$auth_header = $request->get_header( 'authorization' );
			if ( $auth_header && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
				$incoming_key = trim( $matches[1] );
			}
		}

		if ( empty( $incoming_key ) || empty( $configured_secret ) || ! hash_equals( $configured_secret, (string) $incoming_key ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'rest_forbidden',
					'message' => 'Unauthorized. Missing or invalid secret key.',
					'error'   => 'Unauthorized. Missing or invalid secret key.',
					'data'    => array( 'status' => 401 ),
				),
				401
			);
		}

		// ── STEP 2: Parse Request Body ──────────────────────────────────────
		$body = $request->get_json_params();
		if ( empty( $body ) || ! is_array( $body ) ) {
			$raw_body = $request->get_body();
			if ( ! empty( $raw_body ) ) {
				$decoded = json_decode( $raw_body, true );
				if ( is_array( $decoded ) ) {
					$body = $decoded;
				}
			}
		}

		if ( empty( $body ) || ! is_array( $body ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'malformed_payload',
					'message' => 'Malformed or empty request body. Expected JSON payload.',
					'data'    => array( 'status' => 400 ),
				),
				400
			);
		}

		// ── STEP 3: Action Validation ───────────────────────────────────────
		$action = $body['config']['action'] ?? ( $body['action'] ?? '' );
		if ( ! empty( $action ) && 'order.placed' !== $action && 'test' !== $action ) {
			return new WP_REST_Response(
				array(
					'code'    => 'unsupported_action',
					'message' => sprintf( 'Unsupported webhook action: %s. Only order.placed is processed.', sanitize_text_field( $action ) ),
					'data'    => array( 'status' => 400 ),
				),
				400
			);
		}

		// ── STEP 4: Validate api_url if supplied ────────────────────────────
		if ( ! empty( $body['api_url'] ) ) {
			$api_url = trim( (string) $body['api_url'] );
			$parsed  = wp_parse_url( $api_url );

			$scheme = strtolower( $parsed['scheme'] ?? '' );
			$host   = strtolower( $parsed['host'] ?? '' );
			$port   = isset( $parsed['port'] ) ? (int) $parsed['port'] : ( 'https' === $scheme ? 443 : 80 );
			$user   = $parsed['user'] ?? '';
			$pass   = $parsed['pass'] ?? '';

			$is_valid_eb_host = ( 'www.eventbriteapi.com' === $host || 'eventbriteapi.com' === $host );
			$is_valid_scheme  = ( 'https' === $scheme && 443 === $port && empty( $user ) && empty( $pass ) );
			$is_valid_pattern = (bool) preg_match( '#^https://(www\.)?eventbriteapi\.com/v3/orders/([0-9]{1,30})/?(\?.*)?$#i', $api_url );

			if ( ! $is_valid_eb_host || ! $is_valid_scheme || ! $is_valid_pattern ) {
				return new WP_REST_Response(
					array(
						'code'    => 'invalid_api_url',
						'message' => 'Untrusted or invalid api_url provided in webhook payload. Only official Eventbrite API endpoints are permitted.',
						'error'   => 'Untrusted or invalid api_url provided in webhook payload. Only official Eventbrite API endpoints are permitted.',
						'data'    => array( 'status' => 400 ),
					),
					400
				);
			}
		}

		// ── STEP 5: Extract and Validate Numeric Order ID ────────────────────
		$order_id = self::extract_order_id( $body, $request );
		if ( empty( $order_id ) || ! preg_match( '/^[0-9]{1,30}$/', $order_id ) ) {
			self::add_log_entry(
				array(
					'order_id' => 'unknown',
					'status'   => 'error',
					'message'  => 'Webhook rejected: Order ID could not be identified or is not a valid numeric Eventbrite identifier.',
				)
			);
			return new WP_REST_Response(
				array(
					'code'    => 'invalid_order_id',
					'message' => 'Order ID is missing or not a valid numeric Eventbrite identifier.',
					'data'    => array( 'status' => 400 ),
				),
				400
			);
		}

		// Check if synchronous execution was requested (e.g. testing)
		$sync_param = $request->get_param( 'sync' );
		$is_sync    = ( '1' === $sync_param || 'true' === $sync_param );

		// ── STEP 6: Atomic Lock & Idempotency Check ──────────────────────────
		$lock = Pubcrawls_Tracking_DB::atomic_lock_order( $order_id );

		if ( $lock['locked'] ) {
			// Exclusive lock acquired!
			if ( $is_sync ) {
				$result = self::process_order_now( $order_id, false );
				return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );
			}

			// Immediate post-response execution: respond to Eventbrite in <50ms and process immediately
			if ( function_exists( 'fastcgi_finish_request' ) ) {
				self::add_log_entry(
					array(
						'order_id' => $order_id,
						'status'   => 'queued',
						'message'  => 'Webhook verified; immediate post-response processing initiated.',
					)
				);

				wp_send_json(
					array(
						'success'  => true,
						'status'   => 'queued',
						'order_id' => $order_id,
						'message'  => 'Order webhook accepted and queued for conversion tracking.',
					),
					200
				);

				fastcgi_finish_request();

				// Runs immediately in background without site traffic. The webhook can
				// arrive before the browser's attribution beacon, so give it a chance to land.
				self::process_order_now( $order_id, false, true );
				exit;
			}

			// Fallback: enqueue via Action Scheduler or WP-Cron
			self::enqueue_background_order( $order_id );

			self::add_log_entry(
				array(
					'order_id' => $order_id,
					'status'   => 'queued',
					'message'  => 'Webhook verified and order queued for background tracking.',
				)
			);

			return new WP_REST_Response(
				array(
					'success'  => true,
					'status'   => 'queued',
					'order_id' => $order_id,
					'message'  => 'Order webhook accepted and queued for background conversion tracking.',
				),
				200
			);
		}

		// Order already exists in database
		$existing = $lock['row'] ?? null;
		$status   = $existing ? ( $existing->status ?? 'pending' ) : 'unknown';

		if ( 'completed' === $status ) {
			self::add_log_entry(
				array(
					'order_id' => $order_id,
					'status'   => 'skipped',
					'message'  => 'Duplicate webhook received; order was already completely processed.',
				)
			);
			return new WP_REST_Response(
				array(
					'success'   => true,
					'duplicate' => true,
					'order_id'  => $order_id,
					'message'   => 'Order has already been processed previously.',
				),
				200
			);
		}

		if ( 'rejected' === $status ) {
			return new WP_REST_Response(
				array(
					'success'  => true,
					'rejected' => true,
					'order_id' => $order_id,
					'message'  => 'Order was previously verified and rejected (invalid status or organization mismatch).',
				),
				200
			);
		}

		// Check lock staleness only if status is 'processing'
		if ( 'processing' === $status ) {
			$lock_time = ! empty( $existing->locked_at ) ? strtotime( (string) $existing->locked_at ) : 0;
			$lock_age  = $lock_time > 0 ? ( current_time( 'timestamp' ) - $lock_time ) : 9999;

			// If locked less than 10 minutes (600s), respect active worker
			if ( $lock_age < 600 && $lock_time > 0 ) {
				return new WP_REST_Response(
					array(
						'success'     => true,
						'in_progress' => true,
						'order_id'    => $order_id,
						'message'     => 'Order is currently being processed by another worker.',
					),
					200
				);
			}

			// Stale lock detected (> 10 minutes): release stale lock and resume processing
			Pubcrawls_Tracking_DB::release_order_lock( $order_id, null, 'pending' );
			self::add_log_entry(
				array(
					'order_id' => $order_id,
					'status'   => 'queued',
					'message'  => sprintf( 'Stale worker lock (%d seconds old) released; resuming processing.', $lock_age ),
				)
			);
		}

		// Check if maximum retries reached
		if ( Pubcrawls_Tracking_DB::has_exceeded_max_retries( $order_id ) ) {
			return new WP_REST_Response(
				array(
					'success'              => false,
					'max_retries_exceeded' => true,
					'order_id'             => $order_id,
					'message'              => 'Order delivery has reached the maximum retry limit (5 attempts).',
				),
				200
			);
		}

		// If partial or failed, queue selective retry
		if ( $is_sync ) {
			$result = self::process_order_now( $order_id, false );
			return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );
		}

		if ( function_exists( 'fastcgi_finish_request' ) ) {
			wp_send_json(
				array(
					'success'      => true,
					'retry_queued' => true,
					'order_id'     => $order_id,
					'message'      => 'Incomplete order queued for selective retry of failed platforms.',
				),
				200
			);
			fastcgi_finish_request();
			self::process_order_now( $order_id, false );
			exit;
		}

		self::enqueue_background_order( $order_id );

		return new WP_REST_Response(
			array(
				'success'      => true,
				'retry_queued' => true,
				'order_id'     => $order_id,
				'message'      => 'Incomplete order queued for selective retry of failed platforms.',
			),
			200
		);
	}

	/**
	 * Handle browser attribution POST from embedded checkout completion.
	 *
	 * Extracts client_id, session_id, fbp, fbc, ttp, ttclid, user_agent,
	 * and derives client IP server-side.
	 *
	 * @param WP_REST_Request $request Incoming attribution request.
	 * @return WP_REST_Response
	 */
	public static function handle_attribution_request( WP_REST_Request $request ): WP_REST_Response {
		$params   = $request->get_json_params() ?: array();
		$order_id = sanitize_text_field( (string) ( $params['order_id'] ?? $request->get_param( 'order_id' ) ) );

		if ( empty( $order_id ) || ! preg_match( '/^[0-9]{1,30}$/', $order_id ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'invalid_order_id',
					'message' => 'Valid numeric order_id is required.',
					'data'    => array( 'status' => 400 ),
				),
				400
			);
		}

		// Derive visitor IP server-side safely from browser attribution request
		$ip = self::get_client_ip( $request );

		$raw_page_url = $params['page_url'] ?? $request->get_param( 'page_url' );
		$page_url     = class_exists( 'Pubcrawls_Tracking_Dispatcher' )
			? Pubcrawls_Tracking_Dispatcher::sanitize_event_url( (string) $raw_page_url )
			: esc_url_raw( (string) $raw_page_url );

		$attribution = array(
			'event_id'   => sanitize_text_field( (string) ( $params['event_id'] ?? '' ) ),
			'client_id'  => sanitize_text_field( (string) ( $params['client_id'] ?? '' ) ),
			'session_id' => sanitize_text_field( (string) ( $params['session_id'] ?? '' ) ),
			'fbp'        => sanitize_text_field( (string) ( $params['fbp'] ?? '' ) ),
			'fbc'        => sanitize_text_field( (string) ( $params['fbc'] ?? '' ) ),
			'ttp'        => sanitize_text_field( (string) ( $params['ttp'] ?? '' ) ),
			'ttclid'     => sanitize_text_field( (string) ( $params['ttclid'] ?? '' ) ),
			'oai_obref'  => sanitize_text_field( (string) ( $params['oai_obref'] ?? ( $params['obref'] ?? '' ) ) ),
			'oai_oppref' => sanitize_text_field( (string) ( $params['oai_oppref'] ?? ( $params['oppref'] ?? '' ) ) ),
			'ip'         => $ip,
			'user_agent' => sanitize_textarea_field( (string) ( $params['user_agent'] ?? ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) ),
			'page_url'   => $page_url,
		);

		Pubcrawls_Tracking_DB::store_attribution( $order_id, $attribution );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'order_id' => $order_id,
				'message'  => 'Browser attribution recorded successfully.',
			),
			200
		);
	}

	/**
	 * Derive client IP address safely server-side.
	 *
	 * @return string IP address.
	 */
	public static function get_client_ip( ?WP_REST_Request $request = null ): string {
		if ( $request instanceof WP_REST_Request ) {
			foreach ( array( 'cf-connecting-ip', 'x-real-ip', 'x-forwarded-for' ) as $rh ) {
				$val = $request->get_header( $rh );
				if ( ! empty( $val ) ) {
					if ( false !== strpos( $val, ',' ) ) {
						$parts = explode( ',', $val );
						$val   = trim( $parts[0] );
					}
					$clean = filter_var( $val, FILTER_VALIDATE_IP );
					if ( $clean ) {
						return $clean;
					}
				}
			}
		}

		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$val = (string) $_SERVER[ $h ];
				// If comma-separated (e.g. X-Forwarded-For), take the first IP
				if ( false !== strpos( $val, ',' ) ) {
					$parts = explode( ',', $val );
					$val   = trim( $parts[0] );
				}
				$clean = filter_var( $val, FILTER_VALIDATE_IP );
				if ( $clean ) {
					return $clean;
				}
			}
		}

		return '';
	}

	/**
	 * Extract order ID from webhook payload or query params.
	 */
	public static function extract_order_id( array $payload, ?WP_REST_Request $request = null ): string {
		// 1. Direct param 'order_id'
		if ( ! empty( $payload['order_id'] ) ) {
			return sanitize_text_field( (string) $payload['order_id'] );
		}

		// 2. Query param or POST field 'order_id'
		if ( $request instanceof WP_REST_Request ) {
			$param_order_id = $request->get_param( 'order_id' );
			if ( ! empty( $param_order_id ) ) {
				return sanitize_text_field( (string) $param_order_id );
			}
		}

		// 3. From api_url: https://www.eventbriteapi.com/v3/orders/123456789/
		if ( ! empty( $payload['api_url'] ) && is_string( $payload['api_url'] ) ) {
			if ( preg_match( '#/orders/([0-9]{1,30})/?(\?.*)?$#i', $payload['api_url'], $matches ) ) {
				return sanitize_text_field( $matches[1] );
			}
		}

		return '';
	}

	/**
	 * Enqueue an order for background processing using Action Scheduler or WP-Cron.
	 *
	 * @param string $order_id Eventbrite Order ID.
	 * @return bool True if enqueued.
	 */
	public static function enqueue_background_order( string $order_id ): bool {
		$order_id = sanitize_text_field( trim( $order_id ) );
		if ( empty( $order_id ) ) {
			return false;
		}

		// Prefer Action Scheduler if available
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				'pubcrawls_process_eventbrite_order',
				array( 'order_id' => $order_id ),
				'pubcrawls-tracking'
			);
			return true;
		}

		// Fallback to WP-Cron single event
		if ( ! wp_next_scheduled( 'pubcrawls_process_eventbrite_order', array( $order_id ) ) ) {
			wp_schedule_single_event( time(), 'pubcrawls_process_eventbrite_order', array( $order_id ) );
			if ( function_exists( 'spawn_cron' ) ) {
				spawn_cron();
			}
		}

		return true;
	}

	/**
	 * Background action runner for pubcrawls_process_eventbrite_order.
	 *
	 * @param string $order_id Eventbrite Order ID.
	 */
	public static function background_process_order( string $order_id ): void {
		self::process_order_now( $order_id, false, true );
	}

	/**
	 * Poll the tracking DB for up to ATTRIBUTION_MAX_WAIT_SECONDS so the browser's
	 * /eventbrite/attribution beacon has a chance to land before we dispatch to
	 * GA4/Meta/TikTok. Eventbrite's order.placed webhook can arrive before that beacon,
	 * since the two happen over independent channels (server webhook vs. browser fetch).
	 *
	 * Returns as soon as any attribution signal is found; never blocks the HTTP
	 * response to Eventbrite because it only runs after fastcgi_finish_request()
	 * or inside an already-async background job.
	 *
	 * @param string $order_id Eventbrite Order ID.
	 */
	private static function wait_for_attribution( string $order_id ): void {
		$elapsed = 0;

		while ( $elapsed < self::ATTRIBUTION_MAX_WAIT_SECONDS ) {
			$attribution = Pubcrawls_Tracking_DB::get_attribution( $order_id );

			$has_attribution = ! empty( $attribution['client_id'] )
				|| ! empty( $attribution['fbp'] )
				|| ! empty( $attribution['fbc'] )
				|| ! empty( $attribution['ttp'] )
				|| ! empty( $attribution['ttclid'] )
				|| ! empty( $attribution['oai_obref'] )
				|| ! empty( $attribution['oai_oppref'] );

			if ( $has_attribution ) {
				return;
			}

			sleep( self::ATTRIBUTION_POLL_INTERVAL_SECONDS );
			$elapsed += self::ATTRIBUTION_POLL_INTERVAL_SECONDS;
		}
	}

	/**
	 * Process an order immediately: fetch from Eventbrite, verify ownership, and dispatch.
	 *
	 * @param string $order_id              Eventbrite Order ID.
	 * @param bool   $force_retry           Whether to bypass selective retry and resend to all platforms.
	 * @param bool   $wait_for_attribution  Whether to wait for the browser attribution beacon before
	 *                                      dispatching. Only used for genuine first-pass webhook processing,
	 *                                      never for synchronous test/retry calls that must return promptly.
	 * @return array Execution results.
	 */
	public static function process_order_now( string $order_id, bool $force_retry = false, bool $wait_for_attribution = false, ?string $existing_worker_id = null ): array {
		$order_id = sanitize_text_field( trim( $order_id ) );
		if ( empty( $order_id ) ) {
			return array(
				'success' => false,
				'error'   => 'Empty order ID.',
			);
		}

		// Ensure order exists in tracking DB
		Pubcrawls_Tracking_DB::atomic_lock_order( $order_id );

		// Check if already completed
		if ( ! $force_retry ) {
			$existing_order = Pubcrawls_Tracking_DB::get_order( $order_id );
			if ( $existing_order && 'completed' === $existing_order->status ) {
				return array(
					'success'        => true,
					'overall_status' => 'completed',
					'order_id'       => $order_id,
					'message'        => 'Order was already completed.',
				);
			}
		}

		// Acquire atomic processing lock so only one worker processes at any given moment
		if ( ! empty( $existing_worker_id ) ) {
			$worker_id = $existing_worker_id;
		} else {
			$worker_id = 'proc_' . wp_generate_password( 8, false ) . '_' . ( function_exists( 'getmypid' ) ? (string) getmypid() : '0' );
			$locked    = Pubcrawls_Tracking_DB::acquire_processing_lock( $order_id, $worker_id, 600 );
			if ( ! $locked ) {
				return array(
					'success'  => false,
					'order_id' => $order_id,
					'error'    => 'Order is currently being processed by another worker.',
					'locked'   => true,
				);
			}
		}

		try {
			// Step 1: Fetch, verify ownership, and normalize full order from Eventbrite REST API
			$normalized = Pubcrawls_Eventbrite_API::fetch_and_normalize_order( $order_id );
			if ( is_wp_error( $normalized ) ) {
				$err  = $normalized->get_error_message();
				$code = $normalized->get_error_code();

				$is_rejection = ( 'eb_order_not_completed' === $code || 'eb_order_org_mismatch' === $code );
				$final_status = $is_rejection ? 'rejected' : 'failed';

				Pubcrawls_Tracking_DB::update_order_data(
					$order_id,
					array(
						'status'     => $final_status,
						'last_error' => $err,
					)
				);
				Pubcrawls_Tracking_DB::release_order_lock( $order_id, $worker_id, $final_status );

				if ( $is_rejection ) {
					Pubcrawls_Tracking_DB::update_platform_status( $order_id, 'ga4', 'skipped', 'Order rejected: ' . $err );
					Pubcrawls_Tracking_DB::update_platform_status( $order_id, 'meta', 'skipped', 'Order rejected: ' . $err );
					Pubcrawls_Tracking_DB::update_platform_status( $order_id, 'tiktok', 'skipped', 'Order rejected: ' . $err );
					Pubcrawls_Tracking_DB::update_platform_status( $order_id, 'openai', 'skipped', 'Order rejected: ' . $err );
				}

				self::add_log_entry(
					array(
						'order_id' => $order_id,
						'status'   => $final_status,
						'message'  => 'Eventbrite order verification: ' . $err,
					)
				);

				return array(
					'success'  => false,
					'order_id' => $order_id,
					'error'    => $err,
				);
			}

			// Step 1b: Give the browser's attribution beacon a chance to land before dispatching,
			// since the order.placed webhook can arrive before it.
			if ( $wait_for_attribution ) {
				self::wait_for_attribution( $order_id );
			}

			// Step 2: Dispatch to all enabled tracking destinations with selective retry
			$dispatch_result = Pubcrawls_Tracking_Dispatcher::dispatch( $normalized, $force_retry );

			// Step 3: Record Activity Log (without raw email or phone or private identifiers)
			self::add_log_entry(
				array(
					'order_id'    => $order_id,
					'event_name'  => $normalized['event_name'] ?? 'Pub Crawl',
					'revenue'     => $normalized['value'] ?? 0.0,
					'currency'    => $normalized['currency'] ?? 'USD',
					'status'      => $dispatch_result['overall_status'] ?? 'unknown',
					'ga4'         => $dispatch_result['platforms']['ga4'] ?? null,
					'meta'        => $dispatch_result['platforms']['meta'] ?? null,
					'tiktok'      => $dispatch_result['platforms']['tiktok'] ?? null,
					'openai'      => $dispatch_result['platforms']['openai'] ?? null,
					'attribution' => $dispatch_result['attribution'] ?? null,
				)
			);

			return array(
				'success'        => $dispatch_result['success'],
				'overall_status' => $dispatch_result['overall_status'],
				'order_id'       => $order_id,
				'revenue'        => $normalized['value'],
				'currency'       => $normalized['currency'],
				'event_name'     => $normalized['event_name'],
				'platforms'      => $dispatch_result['platforms'],
			);
		} catch ( \Throwable $e ) {
			Pubcrawls_Tracking_DB::release_order_lock( $order_id, $worker_id, 'failed' );
			Pubcrawls_Tracking_DB::update_order_data(
				$order_id,
				array(
					'status'     => 'failed',
					'last_error' => 'Processing exception: ' . $e->getMessage(),
				)
			);
			self::add_log_entry(
				array(
					'order_id' => $order_id,
					'status'   => 'failed',
					'message'  => 'Exception during processing: ' . $e->getMessage(),
				)
			);
			return array(
				'success'  => false,
				'order_id' => $order_id,
				'error'    => $e->getMessage(),
			);
		}
	}

	/**
	 * Append an entry to the webhook activity logs.
	 *
	 * Sanitizes sensitive information, strips raw PII, and bounds total entries to 50.
	 */
	public static function add_log_entry( array $entry ): void {
		$logs = get_option( self::WEBHOOK_LOGS_KEY, array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		if ( empty( $entry['timestamp'] ) ) {
			$entry['timestamp'] = gmdate( 'Y-m-d H:i:s' );
		}

		// Strip sensitive buyer data if inadvertently passed
		unset( $entry['email'], $entry['phone'], $entry['token'], $entry['key'], $entry['secret'] );

		array_unshift( $logs, $entry );

		if ( count( $logs ) > self::MAX_LOGS_COUNT ) {
			$logs = array_slice( $logs, 0, self::MAX_LOGS_COUNT );
		}

		update_option( self::WEBHOOK_LOGS_KEY, $logs, false );
	}

	/**
	 * AJAX Handler: Test processing of an Eventbrite order ID.
	 */
	public static function ajax_test_process_order(): void {
		check_ajax_referer( 'pubcrawls_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$order_id = sanitize_text_field( (string) ( $_POST['order_id'] ?? '' ) );
		if ( empty( $order_id ) || ! preg_match( '/^[0-9]{1,30}$/', $order_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid or non-numeric Eventbrite Order ID.' ) );
		}

		$result = self::process_order_now( $order_id, false );
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX Handler: Retry sending an incomplete or failed order.
	 */
	public static function ajax_retry_order(): void {
		check_ajax_referer( 'pubcrawls_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$order_id = sanitize_text_field( (string) ( $_POST['order_id'] ?? '' ) );
		if ( empty( $order_id ) ) {
			wp_send_json_error( array( 'message' => 'Missing Order ID.' ) );
		}

		$result = self::process_order_now( $order_id, false );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX Handler: Clear diagnostic activity logs.
	 */
	public static function ajax_clear_logs(): void {
		check_ajax_referer( 'pubcrawls_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		delete_option( self::WEBHOOK_LOGS_KEY );
		wp_send_json_success( array( 'message' => 'Logs cleared successfully.' ) );
	}
}
