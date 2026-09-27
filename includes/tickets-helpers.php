<?php

/**
 * Eventbrite & SquadUp Ticket Helpers and Handlers for PubCrawls Addon.
 *
 * Provides helper functions and asset enqueuing for ticketing integrations.
 *
 * @package Pubcrawl_Addon
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

// ── Helper Functions ──────────────────────────────────────────────────────────

if (! function_exists('pcet_parse_date')) {
	/**
	 * Format a date string from ACF date picker (Y-m-d or Ymd) into parts.
	 *
	 * Returns an array of preformatted strings so JS never needs to parse
	 * dates (avoids timezone shifting issues entirely).
	 *
	 * @param  string $raw  Raw date value from ACF.
	 * @return array{
	 *   timestamp:  int|null,
	 *   full:       string,
	 *   short:      string,
	 *   label:      string,
	 *   day:        string,
	 *   month:      string,
	 *   year:       string,
	 *   iso:        string,
	 * }
	 */
	function pcet_parse_date(string $raw): array
	{
		$empty = [
			'timestamp' => null,
			'full'      => '',
			'short'     => '',
			'label'     => '',
			'day'       => '',
			'month'     => '',
			'year'      => '',
			'iso'       => '',
		];

		if (empty($raw)) {
			return $empty;
		}

		$timestamp = strtotime($raw);

		if (! $timestamp) {
			return $empty;
		}

		return [
			'timestamp' => $timestamp,
			'full'      => gmdate('l, F j, Y', $timestamp),         // Saturday, May 9, 2026
			'short'     => gmdate('M j, Y', $timestamp),            // May 9, 2026
			'label'     => gmdate('D, M j, Y', $timestamp),         // Sat, May 9, 2026
			'day'       => gmdate('d', $timestamp),                  // 09
			'month'     => gmdate('M', $timestamp),                  // May
			'year'      => gmdate('Y', $timestamp),                  // 2026
			'iso'       => gmdate('Y-m-d', $timestamp),              // 2026-05-09 (no time = no tz shift)
		];
	}
}

if (! function_exists('pcet_format_time_range')) {
	/**
	 * Format time range from ACF time picker values.
	 *
	 * @param  string $start  Start time raw value.
	 * @param  string $end    End time raw value.
	 * @return string         e.g. "2:00 PM – 4:00 PM" or empty string.
	 */
	function pcet_format_time_range(string $start, string $end): string
	{
		if (empty($start) || empty($end)) {
			return '';
		}

		$start_ts = strtotime($start);
		$end_ts   = strtotime($end);

		if (! $start_ts || ! $end_ts) {
			return '';
		}

		return gmdate('g:i A', $start_ts) . ' – ' . gmdate('g:i A', $end_ts);
	}
}

if (! function_exists('pcet_unique_id')) {
	/**
	 * Generate a unique prefix for DOM IDs to support multiple instances
	 * on the same page without ID conflicts.
	 *
	 * @return string e.g. "pcet_5f3a2b"
	 */
	function pcet_unique_id(): string
	{
		return 'pcet_' . substr(uniqid('', false), -6);
	}
}

// ── Asset Enqueue Handler Class ───────────────────────────────────────────────

if (! class_exists('PCET_Enqueue')) {

	class PCET_Enqueue
	{

		/**
		 * Ensures the SquadUp modal is printed only once per page.
		 */
		private static bool $modal_printed = false;

		/**
		 * Hook into WordPress.
		 */
		public static function init(): void
		{
			add_action('wp_enqueue_scripts', [self::class, 'register_assets'], 5);
		}

		/**
		 * Register (but do not enqueue) all ticket assets.
		 */
		public static function register_assets(): void
		{
			$base_url = PUBCRAWLS_ADDON_URL;
			$ver      = PUBCRAWLS_ADDON_VERSION;

			// ── Plugin CSS ────────────────────────────────────
			if (! wp_style_is('pcet-styles', 'registered')) {
				wp_register_style(
					'pcet-styles',
					$base_url . 'assets/css/tickets.css',
					[],
					$ver
				);
			}

			// ── SquadUp namespaced Bootstrap CSS (official) ────
			if (! wp_style_is('squadup-embed-css', 'registered')) {
				wp_register_style(
					'squadup-embed-css',
					'https://embed.squadup.com/default/css/bootstrap-namespace.min.css',
					[],
					$ver
				);
			}

			// ── Eventbrite Widget (external) ──────────────────
			if (! wp_script_is('eventbrite-widget', 'registered')) {
				wp_register_script(
					'eventbrite-widget',
					'https://www.eventbrite.com/static/widgets/eb_widgets.js',
					[],
					$ver,
					true
				);
			}

			// ── SquadUp SDK (official embed domain) ───────────
			if (! wp_script_is('squadup-widget', 'registered')) {
				wp_register_script(
					'squadup-widget',
					'https://embed.squadup.com/main-v2.min.js',
					[],
					$ver,
					true
				);

				$conf_url = class_exists('Pubcrawls_Admin')
					? esc_url(Pubcrawls_Admin::get_setting('squadup_confirmation_url', 'https://pubcrawls.com/purchase-confirmation/'))
					: 'https://pubcrawls.com/purchase-confirmation/';
				$sms_q    = class_exists('Pubcrawls_Admin')
					? esc_js(Pubcrawls_Admin::get_setting('squadup_sms_question', 'Phone Number for SMS Updates related to your event(s).'))
					: 'Phone Number for SMS Updates related to your event(s).';
				$has_sms  = ! class_exists('Pubcrawls_Admin') || '1' === (string) Pubcrawls_Admin::get_setting('squadup_enable_sms', '1');
				$order_questions_json = $has_sms
					? '[{ question: "' . $sms_q . '", type: "text", required: false }]'
					: '[]';

				// ── SquadUp config — printed BEFORE the SDK runs ──
				wp_add_inline_script(
					'squadup-widget',
					'if ( ! window.squadup ) {
						window.squadup = {
							image: "https://pubcrawls.com/wp-content/uploads/2026/03/Logo.webp",
							root: "squadup-checkout",
							userId: [3438001],
							onDomReady: false,
							shoppingCartEnabled: true,
							ticketGuardianEnabled: false,
							showCheckout: false,
							reservedSeatingEnabled: true,
							descriptionEnabled: false,
							brandingPosition: "none",
							autoScrollElementId: "squadup-checkout",
							confirmationUrl: "' . esc_url($conf_url) . '",
							orderQuestions: ' . $order_questions_json . '
						};
					}',
					'before'
				);
			}

			// ── Event Tickets Card JS ─────────────────────────
			if (! wp_script_is('pcet-tickets', 'registered')) {
				wp_register_script(
					'pcet-tickets',
					$base_url . 'assets/js/single-event-tickets.js',
					['jquery', 'squadup-widget', 'eventbrite-widget'],
					$ver,
					true
				);
			}

			// ── City Events Grid Ticket JS ────────────────────
			if (! wp_script_is('pubcrawls-ticket-js', 'registered')) {
				wp_register_script(
					'pubcrawls-ticket-js',
					$base_url . 'assets/js/city-events-tickets.js',
					['jquery', 'squadup-widget', 'eventbrite-widget'],
					$ver,
					true
				);
			}

			$eb_conf_url = class_exists( 'Pubcrawls_Admin' )
				? esc_url( Pubcrawls_Admin::get_setting( 'eventbrite_confirmation_url', 'https://pubcrawls.com/purchase-confirmation/' ) )
				: 'https://pubcrawls.com/purchase-confirmation/';

			$ticket_config = array(
				'eventbriteConfirmationUrl' => $eb_conf_url,
			);

			wp_localize_script( 'pcet-tickets', 'pubcrawlsTicketConfig', $ticket_config );
			wp_localize_script( 'pubcrawls-ticket-js', 'pubcrawlsTicketConfig', $ticket_config );
		}

		/**
		 * Enqueue assets needed by the ticket card / sticky bar.
		 */
		public static function enqueue_assets(): void
		{
			self::register_assets();

			wp_enqueue_style('pcet-styles');
			wp_enqueue_style('squadup-embed-css');
			wp_enqueue_script('eventbrite-widget');
			wp_enqueue_script('squadup-widget');
			wp_enqueue_script('pcet-tickets');

			// Print the SquadUp modal once, in the footer.
			if (! self::$modal_printed) {
				self::$modal_printed = true;
				add_action('wp_footer', [self::class, 'print_modal'], 100);
			}
		}

		/**
		 * Enqueue assets needed for city events grid tickets.
		 */
		public static function enqueue_city_assets(): void
		{
			self::register_assets();

			wp_enqueue_style('squadup-embed-css');
			wp_enqueue_script('eventbrite-widget');
			wp_enqueue_script('squadup-widget');
			wp_enqueue_script('pubcrawls-ticket-js');

			// Print the SquadUp modal once, in the footer.
			if (! self::$modal_printed) {
				self::$modal_printed = true;
				add_action('wp_footer', [self::class, 'print_modal'], 100);
			}
		}

		/**
		 * Output the SquadUp modal markup once per page.
		 * Unified template for all widgets and shortcodes.
		 */
		public static function print_modal(): void
		{
			if (did_action('pc_squadup_modal_rendered')) {
				return;
			}
			do_action('pc_squadup_modal_rendered');
?>
			<div
				id="pcet-su-modal"
				class="pcet-su-modal pc-su-modal"
				role="dialog"
				aria-modal="true"
				aria-labelledby="pcet-su-modal-title"
				aria-hidden="true">
				<div class="pcet-su-modal__overlay pc-su-modal-overlay" data-pcet-su-close></div>
				<div class="pcet-su-modal__dialog pc-su-modal-dialog" role="document">
					<header class="pcet-su-modal__head pc-su-modal-header">

						<button
							type="button"
							class="pcet-su-modal__close pc-su-modal-close"
							id="pc-su-modal-close"
							data-pcet-su-close
							aria-label="<?php esc_attr_e('Close ticket window', 'pubcrawls-addon'); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
								<path d="M18 6 6 18" />
								<path d="m6 6 12 12" />
							</svg></button>
					</header>
					<div class="pcet-su-modal__body pc-su-modal-body">
						<div id="squadup-checkout"></div>
					</div>
				</div>
			</div>
		<?php
		}
	}

	// Initialize enqueue hooks.
	PCET_Enqueue::init();
}

/**
 * Global helper to register all ticketing assets (styles, SDKs, JS).
 */
if (! function_exists('pubcrawls_register_ticket_assets')) {
	function pubcrawls_register_ticket_assets(): void
	{
		if (class_exists('PCET_Enqueue')) {
			PCET_Enqueue::register_assets();
		}
	}
}

/**
 * Global helper to enqueue all ticket card and sticky bar assets.
 */
if (! function_exists('pubcrawls_enqueue_ticket_assets')) {
	function pubcrawls_enqueue_ticket_assets(): void
	{
		if (class_exists('PCET_Enqueue')) {
			PCET_Enqueue::enqueue_assets();
		}
	}
}

/**
 * Global helper to enqueue city events grid ticket assets.
 */
if (! function_exists('pubcrawls_enqueue_city_ticket_assets')) {
	function pubcrawls_enqueue_city_ticket_assets(): void
	{
		if (class_exists('PCET_Enqueue')) {
			PCET_Enqueue::enqueue_city_assets();
		}
	}
}

/**
 * Global helper to render the unified SquadUp modal template once per page.
 */
if (! function_exists('pubcrawls_render_squadup_modal')) {
	function pubcrawls_render_squadup_modal(): void
	{
		if (class_exists('PCET_Enqueue')) {
			PCET_Enqueue::print_modal();
		}
	}
	add_action('wp_footer', 'pubcrawls_render_squadup_modal', 100);
}

// ── Event Date & Instance Helpers ─────────────────────────────────────────────

if (! function_exists('pcet_build_date_rows')) {
	/**
	 * Build normalised row data from ACF repeater rows.
	 *
	 * @param  array $dates  Raw ACF repeater value.
	 * @return array         Structured rows.
	 */
	function pcet_build_date_rows(array $dates): array
	{
		$rows = [];

		foreach ($dates as $row) {
			$date_raw  = $row['eventdate']        ?? '';
			$start_raw = $row['start_time']       ?? '';
			$end_raw   = $row['end_time']         ?? '';
			$provider  = sanitize_key($row['ticket_provider'] ?? 'squadup');
			$eb_id     = trim($row['eventbrite_id']   ?? '');
			$eb_link   = trim($row['eventbrite_link'] ?? '');
			$su_id     = trim($row['squadup_id']      ?? '');

			$date_parts = pcet_parse_date($date_raw);
			$time_range = pcet_format_time_range($start_raw, $end_raw);

			$rows[] = [
				'date_full'  => $date_parts['full'],
				'date_short' => $date_parts['short'],
				'date_label' => $date_parts['label'],
				'date_day'   => $date_parts['day'],
				'date_month' => $date_parts['month'],
				'date_year'  => $date_parts['year'],
				'date_iso'   => $date_parts['iso'],
				'time'       => $time_range,
				'provider'   => $provider,
				'eb_id'      => $eb_id,
				'eb_link'    => $eb_link,
				'su_id'      => $su_id,
			];
		}

		return $rows;
	}
}

if (! function_exists('pubcrawls_build_event_date_rows')) {
	/**
	 * Alias for pcet_build_date_rows.
	 */
	function pubcrawls_build_event_date_rows(array $dates): array
	{
		return pcet_build_date_rows($dates);
	}
}

if (! function_exists('pcet_localize_instance')) {
	/**
	 * Pass structured data to JS via an inline script registry.
	 *
	 * @param string $uid   Unique instance prefix.
	 * @param array  $rows  Structured date rows.
	 */
	function pcet_localize_instance(string $uid, array $rows): void
	{
		$safe_rows = array_map(function (array $row): array {
			return [
				'date_full'  => esc_html($row['date_full']),
				'date_short' => esc_html($row['date_short']),
				'date_label' => esc_html($row['date_label']),
				'date_day'   => esc_html($row['date_day']),
				'date_month' => esc_html($row['date_month']),
				'date_year'  => esc_html($row['date_year']),
				'date_iso'   => esc_html($row['date_iso']),
				'time'       => esc_html($row['time']),
				'provider'   => esc_html($row['provider']),
				'eb_id'      => esc_html($row['eb_id']),
				'eb_link'    => esc_url($row['eb_link']),
				'su_id'      => esc_html($row['su_id']),
			];
		}, $rows);

		$json = wp_json_encode($safe_rows);
		wp_add_inline_script(
			'pcet-tickets',
			"window.PCET = window.PCET || {}; window.PCET['" . esc_js($uid) . "'] = " . $json . ";",
			'before'
		);
	}
}

