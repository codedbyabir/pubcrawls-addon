<?php

/**
 * Schema.org Event Structured Data Generator for PubCrawls.
 *
 * Automatically outputs dynamic Schema.org Event JSON-LD structured data in `wp_head`
 * on every single `tribe_events` page, pulling live data from ACF fields and fallback sources.
 *
 * -------------------------------------------------------------------------------------
 * KNOWN ACF SCHEMA LIMITATIONS & DESIGN DECISIONS:
 *
 * 1. State and ZIP Separation:
 *    The `venue.address` field in ACF is a single unstructured free-text string (e.g.
 *    "221 Washington St, Hoboken, NJ 07030"). Attempting regex extraction for state/ZIP
 *    across arbitrary hand-entered text is fragile and error-prone. Instead:
 *    - `addressRegion` (state) is reliably parsed from the linked city post's title
 *      (which follows the standard "City, ST" convention, e.g. "Boston, MA" -> "MA")
 *      or parsed directly from the event title.
 *    - `addressLocality` is the city post title with that state suffix stripped ("Boston").
 *    - `streetAddress` and `postalCode` are intentionally omitted from PostalAddress
 *      rather than guessed. If structured street/ZIP fields are added to the Venue ACF
 *      field group in the future, wire them directly into `get_event_location()`.
 *
 * 2. Missing Ticket Price Field:
 *    There is currently no ticket price field in ACF for events or date rows.
 *    Per client specification, no fabricated or default price is output unless configured
 *    via the PubCrawls Admin Settings or via the filter hook:
 *    `apply_filters('pubcrawls_event_schema_price', null, $event_id, $date_row)`
 *
 * 3. Resilient Date & Schema Fallbacks:
 *    Every event in the database receives valid Schema.org Event structured data:
 *    - Priority 1: ACF repeater `event_dates`
 *    - Priority 2: Legacy ACF single field / postmeta `eventdate`
 *    - Priority 3: The Events Calendar native `_EventStartDate`
 *    - Priority 4: Inferred holiday date & year from event title & categories
 *    - Priority 5: Post publication date
 *    - Default start time fallback (default 4:00 PM) if time is omitted
 * -------------------------------------------------------------------------------------
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit;
}

class Pubcrawls_Event_Schema
{

	/**
	 * Hook generator into WordPress frontend.
	 *
	 * Runs early on wp_head (priority 5) to output before standard meta tags.
	 */
	public static function init(): void
	{
		add_action('wp_head', [__CLASS__, 'render_schema'], 5);
	}

	/**
	 * Render the Event JSON-LD schema into wp_head.
	 */
	public static function render_schema(): void
	{
		// 1. Resolve event post ID
		$event_id = get_queried_object_id();
		if (! $event_id) {
			$event_id = get_the_ID();
		}

		if (! $event_id) {
			return;
		}

		// Ensure we are dealing with a tribe_events post
		if (get_post_type($event_id) !== 'tribe_events') {
			if (! is_singular('tribe_events')) {
				return;
			}
			$event_id = get_the_ID();
			if (! $event_id || get_post_type($event_id) !== 'tribe_events') {
				return;
			}
		}

		// Master admin toggle check: Allows turning off Event schema site-wide.
		if (class_exists('Pubcrawls_Admin') && '1' !== (string) Pubcrawls_Admin::get_setting('enable_event_schema', '1')) {
			return;
		}

		/**
		 * Filter to disable Event schema output.
		 *
		 * Allows external plugins or custom page templates to disable this schema
		 * to prevent duplicate Event entities.
		 *
		 * @param bool $skip     Whether to skip schema output. Default false.
		 * @param int  $event_id The current event post ID.
		 */
		if (apply_filters('pubcrawls_skip_event_schema', false, $event_id)) {
			return;
		}

		$schema = self::build_event_schema($event_id);
		if (empty($schema)) {
			return;
		}

		echo "\n" . '<script type="application/ld+json">' . "\n";
		echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo "\n" . '</script>' . "\n";
	}

	/**
	 * Build complete schema data for an event.
	 *
	 * Handles both single-date events (outputs a single Event object)
	 * and multi-date events (outputs an array of Event objects).
	 *
	 * @param int        $event_id    Event post ID.
	 * @param array|null $event_dates Optional raw rows from the ACF 'event_dates' repeater.
	 *                                If null or empty, automatically resolved from post meta / fallbacks.
	 * @return array|null Schema array or null if no valid events could be built.
	 */
	public static function build_event_schema(int $event_id, ?array $event_dates = null): ?array
	{
		$event_post = get_post($event_id);
		if (! $event_post) {
			return null;
		}

		// If no date rows provided, resolve through multi-tier fallback pipeline
		if (empty($event_dates) || ! is_array($event_dates)) {
			$event_dates = self::get_resolved_event_dates($event_id);
		}

		if (empty($event_dates)) {
			return null;
		}

		$base_title    = html_entity_decode(get_the_title($event_id), ENT_QUOTES, 'UTF-8');
		$description   = self::get_event_description($event_id, $event_post);
		$image_url     = self::get_event_image($event_id);
		$location      = self::get_event_location($event_id);
		$permalink     = get_permalink($event_id);
		$timezone      = wp_timezone();
		$is_multi_date = count($event_dates) > 1;

		$org_name = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_organizer_name', 'PubCrawls')
			: 'PubCrawls';
		if ('' === trim($org_name)) {
			$org_name = get_bloginfo('name');
		}

		$org_url = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_organizer_url', 'https://pubcrawls.com/')
			: 'https://pubcrawls.com/';
		if ('' === trim($org_url)) {
			$org_url = home_url('/');
		}

		$org_logo = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_organizer_logo', '')
			: '';

		$organizer = [
			'@type' => 'Organization',
			'name'  => html_entity_decode($org_name, ENT_QUOTES, 'UTF-8'),
			'url'   => esc_url_raw($org_url),
		];

		if (! empty($org_logo)) {
			$organizer['logo'] = esc_url_raw($org_logo);
		}

		$events_schema = [];

		foreach ($event_dates as $date_row) {
			if (! is_array($date_row)) {
				continue;
			}

			$event_item = self::build_single_event_schema(
				$event_id,
				$date_row,
				$base_title,
				$is_multi_date,
				$description,
				$image_url,
				$location,
				$organizer,
				$permalink,
				$timezone
			);

			if (! empty($event_item)) {
				$events_schema[] = $event_item;
			}
		}

		if (empty($events_schema)) {
			return null;
		}

		// Per multi-date rule: if only one date row, output a single JSON-LD object, not a one-item array.
		return count($events_schema) === 1 ? $events_schema[0] : $events_schema;
	}

	/**
	 * Resolve event dates for an event post using a robust multi-tier fallback pipeline.
	 *
	 * Checks multiple sources in order:
	 * 1. ACF repeater field 'event_dates'
	 * 2. Legacy ACF / postmeta single field 'eventdate'
	 * 3. Native The Events Calendar meta '_EventStartDate'
	 * 4. Inferred date from title and event categories (e.g. holiday + year)
	 * 5. Fallback to post publication date
	 *
	 * @param int $event_id Event post ID.
	 * @return array Array of normalized date rows.
	 */
	public static function get_resolved_event_dates(int $event_id): array
	{
		// 1. ACF repeater field 'event_dates'
		if (function_exists('get_field')) {
			$repeater = get_field('event_dates', $event_id);
			if (! empty($repeater) && is_array($repeater)) {
				$valid = [];
				foreach ($repeater as $row) {
					if (is_array($row) && ! empty($row['eventdate'])) {
						$valid[] = $row;
					}
				}
				if (! empty($valid)) {
					return $valid;
				}
			}
		}

		// 2. Legacy ACF / postmeta single field 'eventdate'
		$legacy_date = '';
		if (function_exists('get_field')) {
			$legacy_date = trim((string) get_field('eventdate', $event_id));
		}
		if (empty($legacy_date)) {
			$legacy_date = trim((string) get_post_meta($event_id, 'eventdate', true));
		}
		if (! empty($legacy_date)) {
			return [
				[
					'eventdate'       => $legacy_date,
					'start_time'      => self::get_default_start_time($event_id),
					'end_time'        => '',
					'ticket_provider' => get_post_meta($event_id, 'ticket_provider', true) ?: '',
				],
			];
		}

		// 3. Native The Events Calendar meta '_EventStartDate'
		$tribe_start = trim((string) get_post_meta($event_id, '_EventStartDate', true));
		if (! empty($tribe_start)) {
			$ts = strtotime($tribe_start);
			if ($ts) {
				return [
					[
						'eventdate'  => gmdate('Ymd', $ts),
						'start_time' => gmdate('g:i A', $ts),
						'end_time'   => '',
					],
				];
			}
		}

		// 4. Inferred date from title and categories
		$inferred = self::infer_event_date($event_id);
		if (! empty($inferred)) {
			return [
				[
					'eventdate'       => $inferred,
					'start_time'      => self::get_default_start_time($event_id),
					'end_time'        => '',
					'ticket_provider' => get_post_meta($event_id, 'ticket_provider', true) ?: '',
				],
			];
		}

		// 5. Ultimate fallback: Post publication date
		$pub_date = get_the_date('Ymd', $event_id);
		return [
			[
				'eventdate'  => ! empty($pub_date) ? $pub_date : gmdate('Ymd'),
				'start_time' => self::get_default_start_time($event_id),
				'end_time'   => '',
			],
		];
	}

	/**
	 * Infer event date from post title, slug, and event categories.
	 *
	 * Extracts the year and maps holiday keywords (Halloween, St. Patrick's, July 4th,
	 * Cinco de Mayo, New Year's Eve, Oktoberfest, etc.) to standard calendar dates.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Ymd formatted date string.
	 */
	public static function infer_event_date(int $event_id): string
	{
		$title = html_entity_decode(get_the_title($event_id), ENT_QUOTES, 'UTF-8');
		$post  = get_post($event_id);
		$slug  = $post ? $post->post_name : '';

		$terms = wp_get_post_terms($event_id, 'tribe_events_cat', ['fields' => 'names']);
		$cats  = is_array($terms) ? implode(' ', $terms) : '';

		$text = $title . ' ' . $slug . ' ' . $cats;

		// 1. Extract 4-digit year (e.g. 2024 to 2035)
		$year = '';
		if (preg_match('/\b(202[4-9]|203[0-9])\b/', $text, $matches)) {
			$year = $matches[1];
		} else {
			$post_year = get_the_date('Y', $event_id);
			$year = ! empty($post_year) ? $post_year : gmdate('Y');
		}

		// 2. Identify holiday date (MMDD)
		$month_day = '';
		if (stripos($text, 'Halloween') !== false) {
			$month_day = '1031';
		} elseif (stripos($text, 'July 4') !== false || stripos($text, '4th of July') !== false || stripos($text, 'Independence') !== false) {
			$month_day = '0704';
		} elseif (stripos($text, 'Patrick') !== false || stripos($text, 'Paddy') !== false) {
			$month_day = '0317';
		} elseif (stripos($text, 'Oktoberfest') !== false) {
			$month_day = '1003';
		} elseif (stripos($text, 'Cinco de Mayo') !== false || stripos($text, 'Cinco') !== false) {
			$month_day = '0505';
		} elseif (stripos($text, 'New Year') !== false || stripos($text, 'NYE') !== false) {
			$month_day = '1231';
		} elseif (stripos($text, 'Christmas') !== false || stripos($text, 'Santa') !== false) {
			$month_day = '1212';
		} elseif (stripos($text, 'Valentine') !== false) {
			$month_day = '0214';
		} elseif (stripos($text, 'Mardi Gras') !== false) {
			$month_day = '0228';
		}

		if (! empty($month_day)) {
			return $year . $month_day;
		}

		// Fallback to post date if no holiday matched
		$post_date = get_the_date('Ymd', $event_id);
		return ! empty($post_date) ? $post_date : gmdate('Ymd');
	}

	/**
	 * Get default start time for an event.
	 *
	 * Configurable via admin settings, with standard 4:00 PM default.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Time string.
	 */
	public static function get_default_start_time(int $event_id = 0): string
	{
		$default = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_default_start_time', '4:00 PM')
			: '4:00 PM';
		if ('' === trim($default)) {
			$default = '4:00 PM';
		}
		return apply_filters('pubcrawls_event_schema_default_start_time', $default, $event_id);
	}

	/**
	 * Build a single Schema.org Event object for a specific date row.
	 *
	 * @param int           $event_id      Event post ID.
	 * @param array         $date_row      ACF date row.
	 * @param string        $base_title    Unsuffixed event title.
	 * @param bool          $is_multi_date Whether event has multiple date rows.
	 * @param string        $description   Clean event description.
	 * @param string        $image_url     Event image URL.
	 * @param array         $location      Resolved Place location.
	 * @param array         $organizer     Fixed PubCrawls organization data.
	 * @param string        $permalink     Event permalink.
	 * @param \DateTimeZone $timezone      WordPress site timezone.
	 * @return array|null Single Event schema array, or null if row is invalid.
	 */
	protected static function build_single_event_schema(
		int $event_id,
		array $date_row,
		string $base_title,
		bool $is_multi_date,
		string $description,
		string $image_url,
		array $location,
		array $organizer,
		string $permalink,
		\DateTimeZone $timezone
	): ?array {
		$date_raw  = trim((string) ($date_row['eventdate'] ?? ''));
		$start_raw = trim((string) ($date_row['start_time'] ?? ''));
		$end_raw   = trim((string) ($date_row['end_time'] ?? ''));

		if (empty($date_raw)) {
			$date_raw = self::infer_event_date($event_id);
		}

		if (empty($start_raw)) {
			$start_raw = self::get_default_start_time($event_id);
		}

		// Normalize date string (e.g. '20261024' -> '2026-10-24')
		$date_clean = preg_replace('/\D/', '', $date_raw);
		if (strlen($date_clean) === 8) {
			$date_formatted = substr($date_clean, 0, 4) . '-' . substr($date_clean, 4, 2) . '-' . substr($date_clean, 6, 2);
		} else {
			$ts = strtotime($date_raw);
			$date_formatted = $ts ? gmdate('Y-m-d', $ts) : gmdate('Y-m-d');
		}

		$start_datetime = self::build_iso_datetime($date_formatted, $start_raw, $timezone);
		$start_iso      = $start_datetime ? $start_datetime->format(\DateTimeInterface::ATOM) : $date_formatted;

		// Compute endDate if end_time is provided
		$end_iso = null;
		if (! empty($end_raw)) {
			$end_datetime = self::build_iso_datetime($date_formatted, $end_raw, $timezone);
			if ($end_datetime) {
				// If computed end time is earlier than or equal to start time, it crossed midnight -> add 1 day
				if ($start_datetime && $end_datetime <= $start_datetime) {
					$end_datetime = clone $end_datetime;
					$end_datetime->modify('+1 day');
				}
				$end_iso = $end_datetime->format(\DateTimeInterface::ATOM);
			}
		}

		// Name: post title suffixed with formatted date when multi-date
		$name = $base_title;
		if ($is_multi_date) {
			$date_suffix_format = class_exists('Pubcrawls_Admin')
				? (string) Pubcrawls_Admin::get_setting('schema_date_suffix_format', 'M j, Y')
				: 'M j, Y';
			if (! empty($date_suffix_format) && 'none' !== $date_suffix_format) {
				$ts = strtotime($date_formatted);
				if ($ts) {
					$name .= ' - ' . gmdate($date_suffix_format, $ts);
				}
			}
		}

		// Offers: Availability based on ticket ID matching ticket provider
		$availability = self::get_ticket_availability($event_id, $date_row);

		$currency = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_currency', 'USD')
			: 'USD';
		$currency = ! empty($currency) ? strtoupper(trim($currency)) : 'USD';

		$offer = [
			'@type'         => 'Offer',
			'url'           => esc_url_raw($permalink),
			'priceCurrency' => $currency,
			'availability'  => $availability,
		];

		/**
		 * Filter for ticket price.
		 *
		 * Hook into this filter to supply a price without editing this schema generator.
		 * If left unset by filter, falls back to the admin dashboard default price.
		 *
		 * @param float|string|null $price    Ticket price. Default null.
		 * @param int               $event_id Event post ID.
		 * @param array             $date_row ACF date repeater row.
		 */
		$price = apply_filters('pubcrawls_event_schema_price', null, $event_id, $date_row);
		if ($price === null || $price === '') {
			$default_price = class_exists('Pubcrawls_Admin')
				? Pubcrawls_Admin::get_setting('schema_default_price', '')
				: '';
			if ($default_price !== '' && $default_price !== null) {
				$price = $default_price;
			}
		}
		if ($price !== null && $price !== '') {
			$offer['price'] = $price;
		}

		$attendance_mode = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_attendance_mode', 'https://schema.org/OfflineEventAttendanceMode')
			: 'https://schema.org/OfflineEventAttendanceMode';
		if (empty($attendance_mode)) {
			$attendance_mode = 'https://schema.org/OfflineEventAttendanceMode';
		}

		$event_data = [
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'name'                => $name,
			'startDate'           => $start_iso,
			'eventStatus'         => self::get_event_status($event_id, $date_row),
			'eventAttendanceMode' => $attendance_mode,
			'location'            => $location,
			'organizer'           => $organizer,
			'offers'              => $offer,
		];

		if (! empty($end_iso)) {
			$event_data['endDate'] = $end_iso;
		}

		if (! empty($description)) {
			$event_data['description'] = $description;
		}

		if (! empty($image_url)) {
			$event_data['image'] = esc_url_raw($image_url);
		}

		return apply_filters('pubcrawls_single_event_schema', $event_data, $event_id, $date_row);
	}

	/**
	 * Build full ISO 8601 datetime object from date and time strings in the site timezone.
	 *
	 * @param string        $date_str Y-m-d formatted date string.
	 * @param string        $time_str Time string (e.g. "4:00 PM" or "16:00").
	 * @param \DateTimeZone $timezone Configured WordPress timezone.
	 * @return \DateTime|null
	 */
	protected static function build_iso_datetime(string $date_str, string $time_str, \DateTimeZone $timezone): ?\DateTime
	{
		if (empty($date_str)) {
			return null;
		}

		if (empty($time_str)) {
			$time_str = '4:00 PM';
		}

		try {
			return new \DateTime($date_str . ' ' . $time_str, $timezone);
		} catch (\Exception $e) {
			try {
				return new \DateTime($date_str . ' 16:00:00', $timezone);
			} catch (\Exception $e2) {
				return null;
			}
		}
	}

	/**
	 * Determine ticket availability for a specific date row.
	 *
	 * Returns InStock if a valid ticket ID exists matching the active provider,
	 * otherwise SoldOut. Checked individually per date row.
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $date_row ACF date row.
	 * @return string Schema.org availability URL.
	 */
	public static function get_ticket_availability(int $event_id, array $date_row): string
	{
		$default_provider = class_exists('Pubcrawls_Admin')
			? Pubcrawls_Admin::get_setting('default_ticket_provider', 'squadup')
			: 'squadup';

		$post_provider = function_exists('get_field') ? sanitize_key((string) get_field('ticket_provider', $event_id)) : '';
		if (empty($post_provider)) {
			$post_provider = sanitize_key((string) get_post_meta($event_id, 'ticket_provider', true));
		}
		$row_provider  = ! empty($date_row['ticket_provider']) ? sanitize_key((string) $date_row['ticket_provider']) : '';

		$provider = ! empty($row_provider) ? $row_provider : (! empty($post_provider) ? $post_provider : $default_provider);

		$eb_id = trim((string) ($date_row['eventbrite_id'] ?? ''));
		$su_id = trim((string) ($date_row['squadup_id'] ?? ''));

		if (empty($eb_id)) {
			$eb_id = trim((string) get_post_meta($event_id, 'eventbrite_id', true));
		}
		if (empty($su_id)) {
			$su_id = trim((string) get_post_meta($event_id, 'squadup_id', true));
		}

		$buy_link    = trim((string) get_post_meta($event_id, 'buy_now_link', true));
		$ticket_code = trim((string) get_post_meta($event_id, 'purchase_ticket_code', true));

		$has_ticket = false;
		if ($provider === 'eventbrite') {
			$has_ticket = ! empty($eb_id) || ! empty($buy_link);
		} elseif ($provider === 'squadup') {
			$has_ticket = ! empty($su_id) || ! empty($ticket_code) || ! empty($buy_link);
		} else {
			$has_ticket = ! empty($eb_id) || ! empty($su_id) || ! empty($buy_link) || ! empty($ticket_code);
		}

		// Past events are marked as SoldOut
		$eventdate = $date_row['eventdate'] ?? '';
		if (! empty($eventdate)) {
			$norm = preg_replace('/\D/', '', $eventdate);
			if (strlen($norm) === 8 && $norm < gmdate('Ymd')) {
				$has_ticket = false;
			}
		}

		return $has_ticket ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut';
	}

	/**
	 * Get the Schema.org event status for a given event and date row.
	 *
	 * Defaults to EventScheduled, with a dedicated filter so future ACF fields
	 * (cancelled, postponed, rescheduled) can override without restructure.
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $date_row ACF date repeater row.
	 * @return string Schema.org eventStatus URL.
	 */
	public static function get_event_status(int $event_id, array $date_row = []): string
	{
		$default_status = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_event_status', 'https://schema.org/EventScheduled')
			: 'https://schema.org/EventScheduled';
		$status = ! empty($default_status) ? $default_status : 'https://schema.org/EventScheduled';
		return apply_filters('pubcrawls_event_schema_status', $status, $event_id, $date_row);
	}

	/**
	 * Resolve event location(s) and postal address(es).
	 *
	 * Supports multiple venues connected via the 'event_venue' relationship field.
	 * If multiple venues are attached, returns an array of Schema.org Place objects.
	 * If a single venue is attached, returns a single Place object.
	 * If no venues are attached, falls back to the event city or event title Place.
	 *
	 * @param int $event_id Event post ID.
	 * @return array Schema.org Place object or array of Place objects.
	 */
	public static function get_event_location(int $event_id): array
	{
		$venue_posts     = [];
		$event_city_post = null;

		if (function_exists('get_field')) {
			// 1. Resolve all venues from 'event_venue' relationship
			$raw_venues = get_field('event_venue', $event_id);
			if (! empty($raw_venues)) {
				if (is_array($raw_venues)) {
					foreach ($raw_venues as $v) {
						if ($v instanceof \WP_Post) {
							$venue_posts[] = $v;
						} elseif (is_numeric($v)) {
							$p = get_post((int) $v);
							if ($p) {
								$venue_posts[] = $p;
							}
						}
					}
				} elseif ($raw_venues instanceof \WP_Post) {
					$venue_posts[] = $raw_venues;
				} elseif (is_numeric($raw_venues)) {
					$p = get_post((int) $raw_venues);
					if ($p) {
						$venue_posts[] = $p;
					}
				}
			}

			// 2. Resolve event-level city from 'event_city'
			$event_city = get_field('event_city', $event_id);
			if (! empty($event_city)) {
				if (is_array($event_city)) {
					$first_c = reset($event_city);
					$event_city_post = ($first_c instanceof \WP_Post) ? $first_c : get_post((int) $first_c);
				} elseif ($event_city instanceof \WP_Post) {
					$event_city_post = $event_city;
				} elseif (is_numeric($event_city)) {
					$event_city_post = get_post((int) $event_city);
				}
			}
		}

		// Fallback to postmeta if ACF is inactive or fields were not loaded
		if (empty($venue_posts)) {
			$meta_venues = get_post_meta($event_id, 'event_venue', true);
			if (! empty($meta_venues)) {
				$meta_arr = maybe_unserialize($meta_venues);
				if (is_array($meta_arr)) {
					foreach ($meta_arr as $vid) {
						if (is_numeric($vid)) {
							$p = get_post((int) $vid);
							if ($p) {
								$venue_posts[] = $p;
							}
						}
					}
				} elseif (is_numeric($meta_arr)) {
					$p = get_post((int) $meta_arr);
					if ($p) {
						$venue_posts[] = $p;
					}
				}
			}
		}

		if (! $event_city_post) {
			$meta_city = get_post_meta($event_id, 'event_city', true);
			if (! empty($meta_city)) {
				$city_id = is_array($meta_city) ? reset($meta_city) : $meta_city;
				if (is_numeric($city_id)) {
					$event_city_post = get_post((int) $city_id);
				}
			}
		}

		$country_code = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_country_code', 'US')
			: 'US';
		$country_code = ! empty($country_code) ? strtoupper(trim($country_code)) : 'US';

		// Build Place schema for attached venue(s)
		if (! empty($venue_posts)) {
			$places = [];
			foreach ($venue_posts as $venue_post) {
				$places[] = self::build_venue_place_schema($venue_post, $event_city_post, $country_code);
			}

			// If multiple venues, return array of Place objects; if single venue, return single Place object
			$location = count($places) === 1 ? $places[0] : $places;
			return apply_filters('pubcrawls_event_schema_location', $location, $event_id, $venue_posts, $event_city_post);
		}

		// Fallback when no venues are attached to the event
		$city_title  = $event_city_post ? html_entity_decode(get_the_title($event_city_post->ID), ENT_QUOTES, 'UTF-8') : '';
		$event_title = html_entity_decode(get_the_title($event_id), ENT_QUOTES, 'UTF-8');
		$name        = ! empty($city_title) ? $city_title : (! empty($event_title) ? $event_title : get_bloginfo('name'));

		$parsed_address = self::parse_address('', $city_title, $country_code);
		if (empty($parsed_address['addressLocality']) && preg_match('/([A-Za-z\s]+),\s*([A-Z]{2})\b/', $event_title, $m)) {
			$parsed_address['addressLocality'] = trim($m[1]);
			$parsed_address['addressRegion']   = trim($m[2]);
		}

		$fallback_place = [
			'@type'   => 'Place',
			'name'    => $name,
			'address' => $parsed_address,
		];

		return apply_filters('pubcrawls_event_schema_location', $fallback_place, $event_id, [], $event_city_post);
	}

	/**
	 * Build a Schema.org Place object for a single venue post.
	 *
	 * @param \WP_Post      $venue_post          Venue post object.
	 * @param \WP_Post|null $fallback_city_post  Event city post for locality/region fallback.
	 * @param string        $country_code        Two-letter ISO country code.
	 * @return array Schema.org Place object.
	 */
	public static function build_venue_place_schema(\WP_Post $venue_post, ?\WP_Post $fallback_city_post = null, string $country_code = 'US'): array
	{
		$venue_id = $venue_post->ID;
		$name     = html_entity_decode(get_the_title($venue_id), ENT_QUOTES, 'UTF-8');

		// 1. Resolve address string from ACF 'address' or postmeta
		$raw_address = '';
		if (function_exists('get_field')) {
			$raw_address = (string) get_field('address', $venue_id);
		}
		if (empty($raw_address)) {
			$raw_address = (string) get_post_meta($venue_id, 'address', true);
		}
		$raw_address = trim(html_entity_decode($raw_address, ENT_QUOTES, 'UTF-8'));

		// 2. Resolve city from venue's 'city' relationship, falling back to event city
		$city_post = null;
		if (function_exists('get_field')) {
			$venue_city = get_field('city', $venue_id);
			if (! empty($venue_city)) {
				if (is_array($venue_city)) {
					$first_c   = reset($venue_city);
					$city_post = ($first_c instanceof \WP_Post) ? $first_c : get_post((int) $first_c);
				} elseif ($venue_city instanceof \WP_Post) {
					$city_post = $venue_city;
				} elseif (is_numeric($venue_city)) {
					$city_post = get_post((int) $venue_city);
				}
			}
		}
		if (! $city_post) {
			$meta_city = get_post_meta($venue_id, 'city', true);
			if (! empty($meta_city)) {
				$cid = is_array($meta_city) ? reset($meta_city) : $meta_city;
				if (is_numeric($cid)) {
					$city_post = get_post((int) $cid);
				}
			}
		}
		if (! $city_post && $fallback_city_post) {
			$city_post = $fallback_city_post;
		}

		$city_title = $city_post ? html_entity_decode(get_the_title($city_post->ID), ENT_QUOTES, 'UTF-8') : '';

		// 3. Parse address components
		$parsed_address = self::parse_address($raw_address, $city_title, $country_code);

		$place = [
			'@type'   => 'Place',
			'name'    => $name,
			'address' => $parsed_address,
		];

		// Venue Image: Prioritize featured image (post thumbnail)
		if (has_post_thumbnail($venue_id)) {
			$thumb_url = get_the_post_thumbnail_url($venue_id, 'full');
			if ($thumb_url) {
				$place['image'] = esc_url_raw($thumb_url);
			}
		}

		return $place;
	}

	/**
	 * Parse a free-text address into structured PostalAddress schema properties.
	 *
	 * @param string $raw_address  Raw address string (e.g. "221 Washington St, Hoboken, NJ 07030").
	 * @param string $city_title   Linked city post title (e.g. "Hoboken, NJ").
	 * @param string $country_code Country code (default 'US').
	 * @return array Schema.org PostalAddress array.
	 */
	public static function parse_address(string $raw_address, string $city_title = '', string $country_code = 'US'): array
	{
		$addr        = trim($raw_address);
		$locality    = '';
		$region      = '';
		$postal_code = '';

		// Strip trailing country name (e.g. ", United States" or ", USA")
		if (preg_match('/,\s*(?:United States|USA)\s*$/i', $addr)) {
			$addr = preg_replace('/,\s*(?:United States|USA)\s*$/i', '', $addr);
		}

		// Extract postal code (5 digits or 5+4)
		if (preg_match('/\b(\d{5}(?:-\d{4})?)\b/', $addr, $m)) {
			$postal_code = $m[1];
		}

		// Extract locality and region from linked city title "City, ST" (e.g. "Hoboken, NJ")
		if (! empty($city_title) && strpos($city_title, ',') !== false) {
			$last_comma = strrpos($city_title, ',');
			$locality   = trim(substr($city_title, 0, $last_comma));
			$region     = trim(substr($city_title, $last_comma + 1));
		} elseif (! empty($city_title)) {
			$locality = trim($city_title);
		}

		// Fallback: Extract city and state from raw address string if locality/region are missing
		// E.g. "..., Albany, NY 12207" or "..., San Francisco, CA 94123"
		if (empty($locality) || empty($region)) {
			if (preg_match('/,\s*([^,]+),\s*([A-Za-z\s.]+)\s+(?:\d{5}(?:-\d{4})?)?\s*$/', $addr, $cm)) {
				if (empty($locality)) {
					$locality = trim($cm[1]);
				}
				if (empty($region)) {
					$region = strtoupper(trim(str_replace('.', '', $cm[2])));
				}
			} elseif (empty($region) && preg_match('/\b([A-Z]{2}|[A-Z]\.[A-Z]\.)\s*(?:\d{5})?\s*$/i', $addr, $sm)) {
				$region = strtoupper(trim(str_replace('.', '', $sm[1])));
			}
		}

		// Extract clean street address by stripping zip, state, and city from the end
		$street = $addr;
		if (! empty($postal_code)) {
			$street = preg_replace('/\s*' . preg_quote($postal_code, '/') . '\s*$/', '', $street);
		}
		if (! empty($region)) {
			$street = preg_replace('/[,\s]+(?:' . preg_quote($region, '/') . ')\s*$/i', '', $street);
		}
		if (! empty($locality)) {
			$street = preg_replace('/[,\s]+' . preg_quote($locality, '/') . '\s*$/i', '', $street);
		}
		$street = trim(rtrim($street, ','));

		// If cleaning left nothing or was just empty, use original raw address
		if (empty($street) && ! empty($raw_address)) {
			$street = $raw_address;
		}

		$postal_address = [
			'@type'          => 'PostalAddress',
			'addressCountry' => $country_code,
		];

		if (! empty($street)) {
			$postal_address['streetAddress'] = $street;
		}
		if (! empty($locality)) {
			$postal_address['addressLocality'] = $locality;
		}
		if (! empty($region)) {
			$postal_address['addressRegion'] = $region;
		}
		if (! empty($postal_code)) {
			$postal_address['postalCode'] = $postal_code;
		}

		return $postal_address;
	}

	/**
	 * Get full plain text description from schema_description, excerpt, or post content.
	 *
	 * Prioritizes the dedicated 'schema_description' field. Does NOT truncate
	 * or append '...' to ensure the full complete description is output.
	 *
	 * @param int           $event_id   Event post ID.
	 * @param \WP_Post|null $event_post Event post object.
	 * @return string Plain text description.
	 */
	public static function get_event_description(int $event_id, ?\WP_Post $event_post = null): string
	{
		$description = '';

		if (! $event_post) {
			$event_post = get_post($event_id);
		}

		// Priority 1: Dedicated ACF / postmeta 'schema_description' field
		if (function_exists('get_field')) {
			$schema_desc = get_field('schema_description', $event_id);
			if (! empty($schema_desc) && is_string($schema_desc)) {
				$description = trim($schema_desc);
			}
		}
		if (empty($description)) {
			$meta_desc = get_post_meta($event_id, 'schema_description', true);
			if (! empty($meta_desc) && is_string($meta_desc)) {
				$description = trim($meta_desc);
			}
		}

		// Priority 2: Event Excerpt
		if (empty($description) && has_excerpt($event_id)) {
			$description = get_the_excerpt($event_id);
		}

		// Priority 3: Event Post Content (Full plain text, no word trimming/truncation)
		if (empty($description) && $event_post && ! empty($event_post->post_content)) {
			$clean       = strip_shortcodes($event_post->post_content);
			$description = wp_strip_all_tags($clean);
		}

		// Priority 4: Yoast SEO meta description
		if (empty($description)) {
			$yoast = get_post_meta($event_id, '_yoast_wpseo_metadesc', true);
			if (! empty($yoast)) {
				$description = $yoast;
			}
		}

		// Priority 5: First FAQ answer if available
		if (empty($description)) {
			$faq_answer = get_post_meta($event_id, 'faqs_0_answer', true);
			if (! empty($faq_answer)) {
				$description = wp_strip_all_tags($faq_answer);
			}
		}

		// Priority 6: Synthesize clean description from title
		if (empty($description)) {
			$title       = html_entity_decode(get_the_title($event_id), ENT_QUOTES, 'UTF-8');
			$description = sprintf('Join the official %s pub crawl. Get your tickets, exclusive drink specials, costume contests, and party route access.', $title);
		}

		return trim(html_entity_decode(wp_strip_all_tags($description), ENT_QUOTES, 'UTF-8'));
	}

	/**
	 * Get event image URL prioritizing the featured image (post thumbnail).
	 *
	 * Falls back to 'event_cover_img' or brand logo if featured image is not set.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Image URL or empty string.
	 */
	public static function get_event_image(int $event_id): string
	{
		// Priority 1: Featured Image (Post Thumbnail)
		if (has_post_thumbnail($event_id)) {
			$thumb_url = get_the_post_thumbnail_url($event_id, 'full');
			if ($thumb_url) {
				return $thumb_url;
			}
		}

		// Priority 2: ACF / meta field 'event_cover_img'
		if (function_exists('get_field')) {
			$cover_id = get_field('event_cover_img', $event_id);
			if (! empty($cover_id)) {
				if (is_numeric($cover_id)) {
					$url = wp_get_attachment_image_url((int) $cover_id, 'full');
					if ($url) {
						return $url;
					}
				} elseif (is_array($cover_id) && ! empty($cover_id['url'])) {
					return $cover_id['url'];
				} elseif (is_string($cover_id) && filter_var($cover_id, FILTER_VALIDATE_URL)) {
					return $cover_id;
				}
			}
		}

		$post_cover = get_post_meta($event_id, 'event_cover_img', true);
		if (! empty($post_cover) && is_numeric($post_cover)) {
			$url = wp_get_attachment_image_url((int) $post_cover, 'full');
			if ($url) {
				return $url;
			}
		}

		// Priority 3: Brand logo if configured
		$org_logo = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_organizer_logo', '')
			: '';
		if (! empty($org_logo)) {
			return $org_logo;
		}

		return '';
	}
}
