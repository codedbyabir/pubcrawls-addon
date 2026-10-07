<?php
/**
 * Eventbrite REST API Client, Ownership Verifier & Purchase Normalizer
 *
 * Fetches order details from the official Eventbrite REST API, strictly validates
 * order completion and PubCrawls account/organization ownership, and transforms
 * the order into a standardized internal purchase object consumed by GA4, Meta CAPI, and TikTok.
 *
 * @package Pubcrawl_Addon
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pubcrawls_Eventbrite_API {

	/**
	 * Official Eventbrite API Base URL.
	 */
	const API_BASE_URL = 'https://www.eventbriteapi.com/v3';

	/**
	 * Allowed official Eventbrite API hostnames.
	 */
	const ALLOWED_HOSTS = array( 'www.eventbriteapi.com', 'eventbriteapi.com' );

	/**
	 * Get the active Eventbrite Private API Token.
	 * Checks wp-config.php constant first, then plugin setting.
	 *
	 * @return string API token.
	 */
	public static function get_api_token(): string {
		if ( defined( 'PUBCRAWLS_EB_API_TOKEN' ) && ! empty( PUBCRAWLS_EB_API_TOKEN ) ) {
			return trim( (string) PUBCRAWLS_EB_API_TOKEN );
		}

		if ( class_exists( 'Pubcrawls_Admin' ) ) {
			return trim( (string) Pubcrawls_Admin::get_setting( 'eb_api_token', '' ) );
		}

		return '';
	}

	/**
	 * Get the configured PubCrawls Eventbrite Organization ID.
	 * Checks wp-config.php constant first, then plugin setting.
	 *
	 * @return string Organization ID.
	 */
	public static function get_organization_id(): string {
		if ( defined( 'PUBCRAWLS_EB_ORG_ID' ) && ! empty( PUBCRAWLS_EB_ORG_ID ) ) {
			return trim( (string) PUBCRAWLS_EB_ORG_ID );
		}

		if ( class_exists( 'Pubcrawls_Admin' ) ) {
			return trim( (string) Pubcrawls_Admin::get_setting( 'eb_organization_id', '' ) );
		}

		return '';
	}

	/**
	 * Fetch full order details from Eventbrite API and verify ownership.
	 *
	 * Strictly enforces:
	 * - Only contacts official www.eventbriteapi.com host (no redirects, no user-supplied host).
	 * - Validates order completion and status.
	 * - Validates PubCrawls organization ownership.
	 *
	 * @param string $order_id Eventbrite Order ID.
	 * @return array|WP_Error Normalized order array or WP_Error on failure.
	 */
	public static function fetch_and_normalize_order( string $order_id ) {
		$token = self::get_api_token();
		if ( empty( $token ) ) {
			return new WP_Error(
				'eb_missing_token',
				esc_html__( 'Eventbrite Private API Token is not configured.', 'pubcrawls-addon' )
			);
		}

		$order_id = sanitize_text_field( trim( $order_id ) );
		if ( empty( $order_id ) || ! preg_match( '/^[0-9]{1,30}$/', $order_id ) ) {
			return new WP_Error(
				'eb_invalid_order_id',
				esc_html__( 'Invalid or non-numeric Eventbrite Order ID provided.', 'pubcrawls-addon' )
			);
		}

		// Strictly construct endpoint URL internally on official Eventbrite API host
		$endpoint = sprintf( '%s/orders/%s/?expand=event,attendees,promotional_code', self::API_BASE_URL, rawurlencode( $order_id ) );

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout'            => 15,
				'redirection'        => 0, // Never follow redirects (prevents open redirects & SSRF to alternate hosts)
				'reject_unsafe_urls' => true,
				'sslverify'          => true, // Strictly verify SSL/TLS certificate
				'headers'            => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
					'User-Agent'    => 'PubCrawls-Server/2.0 (WordPress)',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'eb_network_error',
				sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Network error contacting Eventbrite: %s', 'pubcrawls-addon' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 401 === $code ) {
			return new WP_Error( 'eb_unauthorized', esc_html__( 'Eventbrite API returned 401 Unauthorized. Please check your Private Token.', 'pubcrawls-addon' ) );
		} elseif ( 403 === $code ) {
			return new WP_Error( 'eb_forbidden', esc_html__( 'Eventbrite API returned 403 Forbidden. Your token lacks permission to view this order.', 'pubcrawls-addon' ) );
		} elseif ( 404 === $code ) {
			return new WP_Error(
				'eb_not_found',
				sprintf(
					/* translators: %s: Order ID */
					esc_html__( 'Order #%s not found in Eventbrite.', 'pubcrawls-addon' ),
					$order_id
				)
			);
		} elseif ( 429 === $code ) {
			return new WP_Error( 'eb_rate_limit', esc_html__( 'Eventbrite API rate limit exceeded. Please retry later.', 'pubcrawls-addon' ) );
		} elseif ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'eb_http_error',
				sprintf(
					/* translators: 1: HTTP code, 2: Response */
					esc_html__( 'Eventbrite HTTP %1$d error: %2$s', 'pubcrawls-addon' ),
					$code,
					substr( wp_strip_all_tags( $body ), 0, 200 )
				)
			);
		}

		if ( ! is_array( $data ) || empty( $data['id'] ) ) {
			return new WP_Error( 'eb_json_error', esc_html__( 'Invalid or empty JSON response received from Eventbrite.', 'pubcrawls-addon' ) );
		}

		// Step 2: Strictly verify order ownership & completion status
		$ownership_check = self::verify_order_ownership( $data );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		return self::normalize_order( $data );
	}

	/**
	 * Verify that an Eventbrite order is completed and belongs to PubCrawls.
	 *
	 * @param array $data Raw Eventbrite API response for the order.
	 * @return true|WP_Error True if valid, WP_Error if invalid or unauthorized.
	 */
	public static function verify_order_ownership( array $data ) {
		// 1. Completion & Placement Verification
		$status = strtolower( trim( (string) ( $data['status'] ?? '' ) ) );
		$valid_statuses = array( 'placed', 'active', 'attended' );

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return new WP_Error(
				'eb_order_not_completed',
				sprintf(
					/* translators: %s: Order status */
					esc_html__( 'Order status is "%s"; only placed/completed orders are processed for purchase tracking.', 'pubcrawls-addon' ),
					$status
				)
			);
		}

		// 2. Organization Ownership Verification
		$configured_org = self::get_organization_id();
		if ( ! empty( $configured_org ) ) {
			$order_org = (string) ( $data['event']['organization_id'] ?? ( $data['organization_id'] ?? '' ) );
			if ( ! empty( $order_org ) && $configured_org !== $order_org ) {
				return new WP_Error(
					'eb_order_org_mismatch',
					sprintf(
						/* translators: 1: Order organization, 2: Configured organization */
						esc_html__( 'Order belongs to organization %1$s, which does not match PubCrawls organization %2$s.', 'pubcrawls-addon' ),
						$order_org,
						$configured_org
					)
				);
			}
		}

		return true;
	}

	/**
	 * Normalize Eventbrite API order payload into standard internal structure.
	 *
	 * Uses order.costs.gross for gross transaction revenue, actual currency,
	 * and aggregates attendee ticket class breakdowns.
	 *
	 * @param array $data Raw Eventbrite API response.
	 * @return array Normalized purchase object.
	 */
	public static function normalize_order( array $data ): array {
		$order_id = sanitize_text_field( (string) ( $data['id'] ?? '' ) );

		// Revenue extraction: strictly from order.costs.gross
		$currency = 'USD';
		$revenue  = 0.0;

		if ( isset( $data['costs']['gross']['major_value'] ) && is_numeric( $data['costs']['gross']['major_value'] ) ) {
			$revenue = (float) $data['costs']['gross']['major_value'];
		} elseif ( isset( $data['costs']['gross']['value'] ) && is_numeric( $data['costs']['gross']['value'] ) ) {
			$revenue = round( (float) $data['costs']['gross']['value'] / 100, 2 );
		}

		if ( ! empty( $data['costs']['gross']['currency'] ) ) {
			$currency = strtoupper( sanitize_text_field( (string) $data['costs']['gross']['currency'] ) );
		}

		// Event info
		$event_id   = sanitize_text_field( (string) ( $data['event_id'] ?? ( $data['event']['id'] ?? '' ) ) );
		$event_name = sanitize_text_field( (string) ( $data['event']['name']['text'] ?? 'Pub Crawl' ) );
		$event_url  = esc_url_raw( (string) ( $data['event']['url'] ?? home_url() ) );

		// Buyer info (sanitized for dispatch, never logged raw)
		$email      = sanitize_email( (string) ( $data['email'] ?? '' ) );
		$first_name = sanitize_text_field( (string) ( $data['first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( (string) ( $data['last_name'] ?? '' ) );
		$full_name  = sanitize_text_field( (string) ( $data['name'] ?? trim( $first_name . ' ' . $last_name ) ) );
		$phone      = '';

		// Items / Attendees breakdown aggregated by ticket class
		$items = array();
		if ( ! empty( $data['attendees'] ) && is_array( $data['attendees'] ) ) {
			foreach ( $data['attendees'] as $attendee ) {
				$ticket_id   = sanitize_text_field( (string) ( $attendee['ticket_class_id'] ?? $event_id ) );
				$ticket_name = sanitize_text_field( (string) ( $attendee['ticket_class_name'] ?? $event_name ) );

				$ticket_price = 0.0;
				if ( isset( $attendee['costs']['gross']['major_value'] ) && is_numeric( $attendee['costs']['gross']['major_value'] ) ) {
					$ticket_price = (float) $attendee['costs']['gross']['major_value'];
				} elseif ( isset( $attendee['costs']['gross']['value'] ) && is_numeric( $attendee['costs']['gross']['value'] ) ) {
					$ticket_price = round( (float) $attendee['costs']['gross']['value'] / 100, 2 );
				}

				if ( empty( $phone ) && ! empty( $attendee['profile']['cell_phone'] ) ) {
					$phone = sanitize_text_field( (string) $attendee['profile']['cell_phone'] );
				}

				if ( isset( $items[ $ticket_id ] ) ) {
					$items[ $ticket_id ]['quantity'] += 1;
				} else {
					$items[ $ticket_id ] = array(
						'item_id'           => $ticket_id,
						'item_name'         => $ticket_name,
						'ticket_class_id'   => $ticket_id,
						'ticket_class_name' => $ticket_name,
						'event_id'          => $event_id,
						'event_name'        => $event_name,
						'price'             => $ticket_price,
						'unit_price'        => $ticket_price,
						'quantity'          => 1,
						'item_category'     => 'Tickets',
					);
				}
			}
		}

		if ( empty( $items ) ) {
			$items[] = array(
				'item_id'           => $event_id ?: $order_id,
				'item_name'         => $event_name,
				'ticket_class_id'   => $event_id ?: $order_id,
				'ticket_class_name' => $event_name,
				'event_id'          => $event_id,
				'event_name'        => $event_name,
				'price'             => $revenue,
				'unit_price'        => $revenue,
				'quantity'          => 1,
				'item_category'     => 'Tickets',
			);
		}

		return array(
			'order_id'   => $order_id,
			'event_id'   => $event_id,
			'event_name' => $event_name,
			'event_url'  => $event_url,
			'status'     => sanitize_text_field( (string) ( $data['status'] ?? 'placed' ) ),
			'created_at' => sanitize_text_field( (string) ( $data['created'] ?? current_time( 'mysql' ) ) ),
			'value'      => (float) $revenue,
			'currency'   => $currency,
			'buyer'      => array(
				'email'      => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'full_name'  => $full_name,
				'phone'      => $phone,
			),
			'items'      => array_values( $items ),
		);
	}
}
