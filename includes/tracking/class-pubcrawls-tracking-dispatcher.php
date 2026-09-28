<?php
/**
 * Server-Side Analytics Dispatcher
 *
 * Orchestrates sending normalized purchases to GA4 Measurement Protocol,
 * Meta Conversions API (CAPI), and TikTok Events API with individual platform
 * status tracking, browser attribution injection, and selective retry support.
 *
 * @package Pubcrawl_Addon
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pubcrawls_Tracking_Dispatcher {

	/**
	 * Process and dispatch an order to all enabled analytics destinations.
	 *
	 * Supports selective retry: platforms that have already succeeded will NOT
	 * be re-sent unless $force_retry is explicitly true.
	 *
	 * @param array $normalized_order Normalized purchase object.
	 * @param bool  $force_retry      Whether to force re-send to already-successful platforms.
	 * @return array Results per platform.
	 */
	public static function dispatch( array $normalized_order, bool $force_retry = false ): array {
		$order_id = sanitize_text_field( (string) ( $normalized_order['order_id'] ?? '' ) );
		if ( empty( $order_id ) ) {
			return array(
				'success' => false,
				'error'   => 'Missing order ID in normalized order object.',
			);
		}

		// Update order details in DB
		Pubcrawls_Tracking_DB::update_order_data(
			$order_id,
			array(
				'value'               => $normalized_order['value'] ?? 0.0,
				'currency'            => $normalized_order['currency'] ?? 'USD',
				'eventbrite_event_id' => $normalized_order['event_id'] ?? '',
			)
		);

		$db_record = Pubcrawls_Tracking_DB::get_order( $order_id );

		// Load stored browser attribution (client_id, session_id, fbp, fbc, ttp, ttclid, ip, user_agent)
		$attribution = Pubcrawls_Tracking_DB::get_attribution( $order_id );

		$results          = array();
		$active_platforms = array();

		// ── 1. Google Analytics 4 (GA4) ──────────────────────────────────────────
		$ga4_enabled = self::is_platform_enabled( 'ga4' );
		if ( $ga4_enabled ) {
			$active_platforms[] = 'ga4';
			$prev_ga4_status    = $db_record ? ( $db_record->ga4_status ?? 'pending' ) : 'pending';

			if ( ! $force_retry && 'success' === $prev_ga4_status ) {
				$results['ga4'] = array(
					'enabled' => true,
					'success' => true,
					'skipped' => true,
					'code'    => (int) ( $db_record->ga4_code ?? 200 ),
					'message' => 'GA4 delivery previously succeeded; skipped resending.',
				);
			} else {
				$res = self::send_ga4( $normalized_order, $attribution );
				$results['ga4'] = $res;
				Pubcrawls_Tracking_DB::update_platform_status(
					$order_id,
					'ga4',
					$res['success'] ? 'success' : 'failed',
					! empty( $res['error'] ) ? $res['error'] : null,
					$res['code'] ?? null
				);
			}
		} else {
			$results['ga4'] = array(
				'enabled' => false,
				'message' => 'GA4 tracking is disabled or missing credentials.',
			);
			Pubcrawls_Tracking_DB::update_platform_status( $order_id, 'ga4', 'skipped', null, null );
		}

		// ── 2. Meta Conversions API (CAPI) ──────────────────────────────────────
		$meta_enabled = self::is_platform_enabled( 'meta' );
		if ( $meta_enabled ) {
			$active_platforms[] = 'meta';
			$prev_meta_status   = $db_record ? ( $db_record->meta_status ?? 'pending' ) : 'pending';

			if ( ! $force_retry && 'success' === $prev_meta_status ) {
				$results['meta'] = array(
					'enabled' => true,
					'success' => true,
					'skipped' => true,
					'code'    => (int) ( $db_record->meta_code ?? 200 ),
					'message' => 'Meta CAPI delivery previously succeeded; skipped resending.',
				);
			} else {
				$res = self::send_meta( $normalized_order, $attribution );
				$results['meta'] = $res;
				Pubcrawls_Tracking_DB::update_platform_status(
					$order_id,
					'meta',
					$res['success'] ? 'success' : 'failed',
					! empty( $res['error'] ) ? $res['error'] : null,
					$res['code'] ?? null
				);
			}
		} else {
			$results['meta'] = array(
				'enabled' => false,
				'message' => 'Meta CAPI tracking is disabled or missing credentials.',
			);
			Pubcrawls_Tracking_DB::update_platform_status( $order_id, 'meta', 'skipped', null, null );
		}

		// ── 3. TikTok Events API ────────────────────────────────────────────────
		$tiktok_enabled = self::is_platform_enabled( 'tiktok' );
		if ( $tiktok_enabled ) {
			$active_platforms[] = 'tiktok';
			$prev_tiktok_status = $db_record ? ( $db_record->tiktok_status ?? 'pending' ) : 'pending';

			if ( ! $force_retry && 'success' === $prev_tiktok_status ) {
				$results['tiktok'] = array(
					'enabled' => true,
					'success' => true,
					'skipped' => true,
					'code'    => (int) ( $db_record->tiktok_code ?? 200 ),
					'message' => 'TikTok delivery previously succeeded; skipped resending.',
				);
			} else {
				$res = self::send_tiktok( $normalized_order, $attribution );
				$results['tiktok'] = $res;
				Pubcrawls_Tracking_DB::update_platform_status(
					$order_id,
					'tiktok',
					$res['success'] ? 'success' : 'failed',
					! empty( $res['error'] ) ? $res['error'] : null,
					$res['code'] ?? null
				);
			}
		} else {
			$results['tiktok'] = array(
				'enabled' => false,
				'message' => 'TikTok tracking is disabled or missing credentials.',
			);
			Pubcrawls_Tracking_DB::update_platform_status( $order_id, 'tiktok', 'skipped', null, null );
		}

		// Finalize overall order status in database
		$overall_status = Pubcrawls_Tracking_DB::finalize_order_status( $order_id, $active_platforms );

		return array(
			'success'        => ( 'completed' === $overall_status || 'partial' === $overall_status ),
			'overall_status' => $overall_status,
			'order_id'       => $order_id,
			'platforms'      => $results,
		);
	}

	/**
	 * Check if a platform is enabled and has minimal required credentials.
	 *
	 * @param string $platform Platform slug ('ga4', 'meta', 'tiktok').
	 * @return bool
	 */
	public static function is_platform_enabled( string $platform ): bool {
		$global_enabled = self::get_config( 'enable_server_tracking', '1' ) === '1';
		if ( ! $global_enabled ) {
			return false;
		}

		if ( 'ga4' === $platform ) {
			$enabled = self::get_config( 'enable_ga4_tracking', '1' ) === '1';
			$mid     = self::get_config( 'ga4_measurement_id', '' );
			$secret  = self::get_config( 'ga4_api_secret', '' );
			return $enabled && ! empty( $mid ) && ! empty( $secret );
		}

		if ( 'meta' === $platform ) {
			$enabled = self::get_config( 'enable_meta_tracking', '1' ) === '1';
			$pixel   = self::get_config( 'meta_pixel_id', '' );
			$token   = self::get_config( 'meta_capi_token', '' );
			return $enabled && ! empty( $pixel ) && ! empty( $token );
		}

		if ( 'tiktok' === $platform ) {
			$enabled = self::get_config( 'enable_tiktok_tracking', '1' ) === '1';
			$pixel   = self::get_config( 'tiktok_pixel_id', '' );
			$token   = self::get_config( 'tiktok_access_token', '' );
			return $enabled && ! empty( $pixel ) && ! empty( $token );
		}

		return false;
	}

	/**
	 * Get configuration setting with support for wp-config.php constants.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_config( string $key, $default = '' ) {
		$constant_map = array(
			'eb_api_token'           => 'PUBCRAWLS_EB_API_TOKEN',
			'eb_webhook_secret'      => 'PUBCRAWLS_EB_WEBHOOK_SECRET',
			'eb_organization_id'     => 'PUBCRAWLS_EB_ORG_ID',
			'ga4_measurement_id'     => 'PUBCRAWLS_GA4_MEASUREMENT_ID',
			'ga4_api_secret'         => 'PUBCRAWLS_GA4_API_SECRET',
			'ga4_debug_mode'         => 'PUBCRAWLS_GA4_DEBUG_MODE',
			'meta_pixel_id'          => 'PUBCRAWLS_META_PIXEL_ID',
			'meta_capi_token'        => 'PUBCRAWLS_META_CAPI_TOKEN',
			'meta_test_code'         => 'PUBCRAWLS_META_TEST_CODE',
			'tiktok_pixel_id'        => 'PUBCRAWLS_TIKTOK_PIXEL_ID',
			'tiktok_access_token'    => 'PUBCRAWLS_TIKTOK_ACCESS_TOKEN',
			'tiktok_test_code'       => 'PUBCRAWLS_TIKTOK_TEST_CODE',
			'enable_server_tracking' => 'PUBCRAWLS_ENABLE_SERVER_TRACKING',
			'enable_ga4_tracking'    => 'PUBCRAWLS_ENABLE_GA4_TRACKING',
			'enable_meta_tracking'   => 'PUBCRAWLS_ENABLE_META_TRACKING',
			'enable_tiktok_tracking' => 'PUBCRAWLS_ENABLE_TIKTOK_TRACKING',
		);

		if ( isset( $constant_map[ $key ] ) && defined( $constant_map[ $key ] ) ) {
			return constant( $constant_map[ $key ] );
		}

		if ( class_exists( 'Pubcrawls_Admin' ) ) {
			return Pubcrawls_Admin::get_setting( $key, $default );
		}

		return $default;
	}

	/**
	 * Send Purchase event to GA4 Measurement Protocol.
	 *
	 * Injects verified browser attribution (client_id, session_id) where available;
	 * otherwise falls back to a deterministic buyer identifier and logs attribution absence.
	 *
	 * @param array $order       Normalized order.
	 * @param array $attribution Stored browser attribution parameters.
	 * @return array
	 */
	public static function send_ga4( array $order, array $attribution = array() ): array {
		$mid        = trim( (string) self::get_config( 'ga4_measurement_id', '' ) );
		$api_secret = trim( (string) self::get_config( 'ga4_api_secret', '' ) );

		if ( empty( $mid ) || empty( $api_secret ) ) {
			return array(
				'enabled' => false,
				'success' => false,
				'error'   => 'Missing GA4 Measurement ID or API Secret.',
			);
		}

		$debug_mode = '1' === (string) self::get_config( 'ga4_debug_mode', '0' );
		$base_url   = $debug_mode
			? 'https://www.google-analytics.com/debug/mp/collect'
			: 'https://www.google-analytics.com/mp/collect';

		$endpoint = add_query_arg(
			array(
				'measurement_id' => $mid,
				'api_secret'     => $api_secret,
			),
			$base_url
		);

		// Format items for GA4 e-commerce
		$ga4_items = array();
		foreach ( $order['items'] as $item ) {
			$ga4_items[] = array(
				'item_id'       => (string) $item['item_id'],
				'item_name'     => (string) $item['item_name'],
				'item_category' => (string) ( $item['item_category'] ?? 'Tickets' ),
				'price'         => (float) $item['price'],
				'quantity'      => (int) $item['quantity'],
			);
		}

		// Client ID & Session ID: prefer captured browser attribution
		$client_id               = '';
		$session_id              = '';
		$has_browser_attribution = false;

		if ( ! empty( $attribution['client_id'] ) ) {
			$client_id               = sanitize_text_field( $attribution['client_id'] );
			if ( preg_match( '/^GA\d+\.\d+\.(.+)$/i', $client_id, $m ) ) {
				$client_id = $m[1];
			}
			$has_browser_attribution = true;
		} else {
			// Deterministic fallback derived from order & buyer email
			$buyer_email = $order['buyer']['email'] ?? '';
			$client_id   = ! empty( $buyer_email )
				? 'eb.' . substr( hash( 'sha256', $buyer_email ), 0, 16 ) . '.' . $order['order_id']
				: 'eb.order.' . $order['order_id'];
		}

		if ( ! empty( $attribution['session_id'] ) ) {
			$session_id = sanitize_text_field( $attribution['session_id'] );
		}

		$event_params = array(
			'transaction_id' => (string) $order['order_id'],
			'value'          => (float) $order['value'],
			'currency'       => (string) $order['currency'],
			'items'          => $ga4_items,
		);

		if ( ! empty( $session_id ) ) {
			$event_params['session_id'] = $session_id;
		}

		if ( $debug_mode ) {
			$event_params['debug_mode'] = 1;
		}

		$payload = array(
			'client_id' => $client_id,
			'events'    => array(
				array(
					'name'   => 'purchase',
					'params' => $event_params,
				),
			),
		);

		if ( ! empty( $order['buyer']['email'] ) ) {
			$payload['user_id'] = hash( 'sha256', strtolower( trim( $order['buyer']['email'] ) ) );
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'            => 12,
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
				'sslverify'          => true,
				'headers'            => array( 'Content-Type' => 'application/json' ),
				'body'               => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'enabled'     => true,
				'success'     => false,
				'attribution' => $has_browser_attribution ? 'browser' : 'fallback',
				'error'       => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		$success = ( 200 === $code || 204 === $code );

		return array(
			'enabled'     => true,
			'success'     => $success,
			'code'        => $code,
			'attribution' => $has_browser_attribution ? 'browser' : 'fallback',
			'debug'       => $debug_mode ? json_decode( $body, true ) : null,
			'error'       => ! $success ? sprintf( 'GA4 HTTP %d: %s', $code, substr( wp_strip_all_tags( $body ), 0, 200 ) ) : null,
		);
	}

	/**
	 * Send Purchase event to Meta Conversions API (CAPI).
	 *
	 * @param array $order       Normalized order.
	 * @param array $attribution Stored browser attribution parameters.
	 * @return array
	 */
	public static function send_meta( array $order, array $attribution = array() ): array {
		$pixel_id     = trim( (string) self::get_config( 'meta_pixel_id', '' ) );
		$access_token = trim( (string) self::get_config( 'meta_capi_token', '' ) );
		$test_code    = trim( (string) self::get_config( 'meta_test_code', self::get_config( 'meta_test_event_code', '' ) ) );

		if ( empty( $pixel_id ) || empty( $access_token ) ) {
			return array(
				'enabled' => false,
				'success' => false,
				'error'   => 'Missing Meta Pixel ID or CAPI Access Token.',
			);
		}

		$endpoint = sprintf( 'https://graph.facebook.com/v21.0/%s/events?access_token=%s', rawurlencode( $pixel_id ), rawurlencode( $access_token ) );

		// User Data with SHA-256 hashing
		$user_data = array();
		if ( ! empty( $order['buyer']['email'] ) ) {
			$user_data['em'] = array( hash( 'sha256', strtolower( trim( $order['buyer']['email'] ) ) ) );
		}
		if ( ! empty( $order['buyer']['first_name'] ) ) {
			$user_data['fn'] = array( hash( 'sha256', strtolower( trim( $order['buyer']['first_name'] ) ) ) );
		}
		if ( ! empty( $order['buyer']['last_name'] ) ) {
			$user_data['ln'] = array( hash( 'sha256', strtolower( trim( $order['buyer']['last_name'] ) ) ) );
		}
		if ( ! empty( $order['buyer']['phone'] ) ) {
			$clean_ph = preg_replace( '/\D/', '', $order['buyer']['phone'] );
			if ( ! empty( $clean_ph ) ) {
				$user_data['ph'] = array( hash( 'sha256', $clean_ph ) );
			}
		}

		// Inject browser attribution where captured
		if ( ! empty( $attribution['fbp'] ) ) {
			$user_data['fbp'] = sanitize_text_field( $attribution['fbp'] );
		}
		if ( ! empty( $attribution['fbc'] ) ) {
			$user_data['fbc'] = sanitize_text_field( $attribution['fbc'] );
		}
		if ( ! empty( $attribution['ip'] ) ) {
			$user_data['client_ip_address'] = sanitize_text_field( $attribution['ip'] );
		}
		if ( ! empty( $attribution['user_agent'] ) ) {
			$user_data['client_user_agent'] = sanitize_text_field( $attribution['user_agent'] );
		} else {
			$user_data['client_user_agent'] = 'PubCrawls-Server/2.0';
		}

		$contents = array();
		foreach ( $order['items'] as $item ) {
			$contents[] = array(
				'id'         => (string) $item['item_id'],
				'quantity'   => (int) $item['quantity'],
				'item_price' => (float) $item['price'],
			);
		}

		$event_payload = array(
			'event_name'       => 'Purchase',
			'event_time'       => time(),
			'event_id'         => (string) $order['order_id'], // Deduplication key
			'action_source'    => 'website',
			'event_source_url' => $order['event_url'] ?: home_url( '/purchase-confirmation/' ),
			'user_data'        => $user_data,
			'custom_data'      => array(
				'currency'     => (string) $order['currency'],
				'value'        => (float) $order['value'],
				'order_id'     => (string) $order['order_id'],
				'content_name' => (string) $order['event_name'],
				'content_type' => 'product',
				'contents'     => $contents,
			),
		);

		$payload = array(
			'data' => array( $event_payload ),
		);

		if ( ! empty( $test_code ) ) {
			$payload['test_event_code'] = $test_code;
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'            => 12,
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
				'sslverify'          => true,
				'headers'            => array( 'Content-Type' => 'application/json' ),
				'body'               => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'enabled' => true,
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$success = ( 200 === $code );

		return array(
			'enabled'  => true,
			'success'  => $success,
			'code'     => $code,
			'response' => $body,
			'error'    => ! $success ? sprintf( 'Meta CAPI HTTP %d: %s', $code, $body['error']['message'] ?? 'Unknown error' ) : null,
		);
	}

	/**
	 * Send CompletePayment event to TikTok Events API.
	 *
	 * @param array $order       Normalized order.
	 * @param array $attribution Stored browser attribution parameters.
	 * @return array
	 */
	public static function send_tiktok( array $order, array $attribution = array() ): array {
		$pixel_id     = trim( (string) self::get_config( 'tiktok_pixel_id', '' ) );
		$access_token = trim( (string) self::get_config( 'tiktok_access_token', '' ) );
		$test_code    = trim( (string) self::get_config( 'tiktok_test_code', self::get_config( 'tiktok_test_event_code', '' ) ) );

		if ( empty( $pixel_id ) || empty( $access_token ) ) {
			return array(
				'enabled' => false,
				'success' => false,
				'error'   => 'Missing TikTok Pixel ID or Access Token.',
			);
		}

		$endpoint = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

		$user_data = array();
		if ( ! empty( $order['buyer']['email'] ) ) {
			$user_data['email'] = hash( 'sha256', strtolower( trim( $order['buyer']['email'] ) ) );
		}
		if ( ! empty( $order['buyer']['phone'] ) ) {
			$clean_ph = preg_replace( '/\D/', '', $order['buyer']['phone'] );
			if ( ! empty( $clean_ph ) ) {
				$user_data['phone'] = hash( 'sha256', $clean_ph );
			}
		}

		// Inject browser attribution where captured
		if ( ! empty( $attribution['ttp'] ) ) {
			$user_data['ttp'] = sanitize_text_field( $attribution['ttp'] );
		}
		if ( ! empty( $attribution['ttclid'] ) ) {
			$user_data['ttclid'] = sanitize_text_field( $attribution['ttclid'] );
		}
		if ( ! empty( $attribution['ip'] ) ) {
			$user_data['ip'] = sanitize_text_field( $attribution['ip'] );
		}
		if ( ! empty( $attribution['user_agent'] ) ) {
			$user_data['user_agent'] = sanitize_text_field( $attribution['user_agent'] );
		}

		$contents = array();
		foreach ( $order['items'] as $item ) {
			$contents[] = array(
				'content_id'   => (string) $item['item_id'],
				'content_name' => (string) $item['item_name'],
				'content_type' => 'product',
				'quantity'     => (int) $item['quantity'],
				'price'        => (float) $item['price'],
			);
		}

		$data_event = array(
			'event'      => 'CompletePayment',
			'event_id'   => (string) $order['order_id'], // Deduplication key
			'event_time' => time(),
			'user'       => $user_data,
			'properties' => array(
				'currency' => (string) $order['currency'],
				'value'    => (float) $order['value'],
				'contents' => $contents,
			),
		);

		if ( ! empty( $attribution['ttclid'] ) ) {
			$data_event['properties']['ttclid'] = sanitize_text_field( $attribution['ttclid'] );
		}

		$payload = array(
			'event_source'    => 'web',
			'event_source_id' => $pixel_id,
			'data'            => array( $data_event ),
		);

		if ( ! empty( $test_code ) ) {
			$payload['test_event_code'] = $test_code;
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'            => 12,
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
				'sslverify'          => true,
				'headers'            => array(
					'Content-Type' => 'application/json',
					'Access-Token' => $access_token,
				),
				'body'               => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'enabled' => true,
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$success = ( 200 === $code && isset( $body['code'] ) && 0 === (int) $body['code'] );

		return array(
			'enabled'  => true,
			'success'  => $success,
			'code'     => $code,
			'response' => $body,
			'error'    => ! $success ? sprintf( 'TikTok HTTP %d: %s', $code, $body['message'] ?? 'Unknown error' ) : null,
		);
	}
}
