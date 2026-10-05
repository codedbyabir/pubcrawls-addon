<?php

/**
 * Schema.org Event Structured Data Generator for PubCrawls.
 *
 * Automatically outputs dynamic Schema.org Event JSON-LD structured data in `wp_head`
 * on every single `tribe_events` page, pulling live data from ACF fields.
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
 *      by reading the substring after the last comma.
 *    - `addressLocality` is the city post title with that state suffix stripped ("Boston").
 *    - `streetAddress` and `postalCode` are intentionally omitted from PostalAddress
 *      rather than guessed. If structured street/ZIP fields are added to the Venue ACF
 *      field group in the future, wire them directly into `get_event_location()`.
 *
 * 2. Missing Ticket Price Field:
 *    There is currently no ticket price field in ACF for events or date rows.
 *    Per client specification, no fabricated or default price is output. The `offers`
 *    object omits the `price` key by default. A filter hook is provided:
 *    `apply_filters('pubcrawls_event_schema_price', null, $event_id, $date_row)`
 *    so a price source can be connected later without modifying this file.
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
		if (! is_singular('tribe_events')) {
			return;
		}

		$event_id = get_the_ID();
		if (! $event_id) {
			return;
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

		if (! function_exists('get_field')) {
			return;
		}

		$event_dates = get_field('event_dates', $event_id);
		if (empty($event_dates) || ! is_array($event_dates)) {
			return;
		}

		$schema = self::build_event_schema($event_id, $event_dates);
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
	 * @param int   $event_id    Event post ID.
	 * @param array $event_dates Raw rows from the ACF 'event_dates' repeater.
	 * @return array|null Schema array or null if no valid events could be built.
	 */
	public static function build_event_schema(int $event_id, array $event_dates): ?array
	{
		$event_post = get_post($event_id);
		if (! $event_post) {
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
			return null;
		}

		// Normalize date string (e.g. '20261024' -> '2026-10-24')
		$date_formatted = preg_replace('/^(\d{4})(\d{2})(\d{2})$/', '$1-$2-$3', $date_raw);
		$start_datetime = self::build_iso_datetime($date_formatted, $start_raw, $timezone);

		if (! $start_datetime) {
			return null;
		}

		$start_iso = $start_datetime->format(\DateTimeInterface::ATOM);

		// Compute endDate if end_time is provided
		$end_iso = null;
		if (! empty($end_raw)) {
			$end_datetime = self::build_iso_datetime($date_formatted, $end_raw, $timezone);
			if ($end_datetime) {
				// If computed end time is earlier than or equal to start time, it crossed midnight -> add 1 day
				if ($end_datetime <= $start_datetime) {
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
		 * By default omitted because ACF lacks a price field. Hook into this
		 * filter to supply a price without editing this schema generator.
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
		if (empty($date_str) || empty($time_str)) {
			return null;
		}

		try {
			return new \DateTime($date_str . ' ' . $time_str, $timezone);
		} catch (\Exception $e) {
			return null;
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
		$row_provider  = ! empty($date_row['ticket_provider']) ? sanitize_key((string) $date_row['ticket_provider']) : '';

		$provider = ! empty($row_provider) ? $row_provider : (! empty($post_provider) ? $post_provider : $default_provider);

		$eb_id = trim((string) ($date_row['eventbrite_id'] ?? ''));
		$su_id = trim((string) ($date_row['squadup_id'] ?? ''));

		$has_ticket = false;
		if ($provider === 'eventbrite') {
			$has_ticket = ! empty($eb_id);
		} elseif ($provider === 'squadup') {
			$has_ticket = ! empty($su_id);
		} else {
			$has_ticket = ! empty($eb_id) || ! empty($su_id);
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
	 * Resolve event location and postal address.
	 *
	 * Resolves location.name from the venue post (`event_venue`), with fallback
	 * to `event_city` if venue is empty. Parses state and locality from the
	 * linked city post title.
	 *
	 * @param int $event_id Event post ID.
	 * @return array Schema.org Place object.
	 */
	public static function get_event_location(int $event_id): array
	{
		$venue_post = null;
		$city_post  = null;

		if (function_exists('get_field')) {
			// 1. Resolve venue from event_venue relationship
			$venues = get_field('event_venue', $event_id);
			if (! empty($venues)) {
				if (is_array($venues)) {
					$first = reset($venues);
					if ($first instanceof \WP_Post) {
						$venue_post = $first;
					} elseif (is_numeric($first)) {
						$venue_post = get_post((int) $first);
					}
				} elseif ($venues instanceof \WP_Post) {
					$venue_post = $venues;
				} elseif (is_numeric($venues)) {
					$venue_post = get_post((int) $venues);
				}
			}

			// 2. Resolve city from venue's linked 'city' relationship
			if ($venue_post) {
				$venue_city = get_field('city', $venue_post->ID);
				if (! empty($venue_city)) {
					if (is_array($venue_city)) {
						$first_c = reset($venue_city);
						if ($first_c instanceof \WP_Post) {
							$city_post = $first_c;
						} elseif (is_numeric($first_c)) {
							$city_post = get_post((int) $first_c);
						}
					} elseif ($venue_city instanceof \WP_Post) {
						$city_post = $venue_city;
					} elseif (is_numeric($venue_city)) {
						$city_post = get_post((int) $venue_city);
					}
				}
			}

			// 3. Fallback: Resolve city directly from event's 'event_city'
			if (! $city_post) {
				$event_city = get_field('event_city', $event_id);
				if (! empty($event_city)) {
					if (is_array($event_city)) {
						$first_c = reset($event_city);
						if ($first_c instanceof \WP_Post) {
							$city_post = $first_c;
						} elseif (is_numeric($first_c)) {
							$city_post = get_post((int) $first_c);
						}
					} elseif ($event_city instanceof \WP_Post) {
						$city_post = $event_city;
					} elseif (is_numeric($event_city)) {
						$city_post = get_post((int) $event_city);
					}
				}
			}
		}

		// Parse city title "City, ST" (e.g. "Boston, MA" or "Hoboken, NJ")
		$locality = '';
		$region   = '';

		if ($city_post) {
			$raw_city = html_entity_decode(get_the_title($city_post->ID), ENT_QUOTES, 'UTF-8');
			if (strpos($raw_city, ',') !== false) {
				$last_comma = strrpos($raw_city, ',');
				$locality   = trim(substr($raw_city, 0, $last_comma));
				$region     = trim(substr($raw_city, $last_comma + 1));
			} else {
				$locality = trim($raw_city);
			}
		}

		$location = [
			'@type' => 'Place',
		];

		// Location Name: venue post title, or fallback to city post title
		if ($venue_post) {
			$location['name'] = html_entity_decode(get_the_title($venue_post->ID), ENT_QUOTES, 'UTF-8');
		} elseif ($city_post) {
			$location['name'] = html_entity_decode(get_the_title($city_post->ID), ENT_QUOTES, 'UTF-8');
		}

		$country_code = class_exists('Pubcrawls_Admin')
			? (string) Pubcrawls_Admin::get_setting('schema_country_code', 'US')
			: 'US';
		$country_code = ! empty($country_code) ? strtoupper(trim($country_code)) : 'US';

		// PostalAddress
		// Limitation Note: streetAddress and postalCode are omitted because venue.address
		// is stored as a single unstructured free-text string in ACF with no dedicated fields.
		$address = [
			'@type'          => 'PostalAddress',
			'addressCountry' => $country_code,
		];

		if (! empty($locality)) {
			$address['addressLocality'] = $locality;
		}

		if (! empty($region)) {
			$address['addressRegion'] = $region;
		}

		$location['address'] = $address;

		return apply_filters('pubcrawls_event_schema_location', $location, $event_id, $venue_post, $city_post);
	}

	/**
	 * Get clean plain text description from excerpt or post content.
	 *
	 * @param int      $event_id   Event post ID.
	 * @param \WP_Post $event_post Event post object.
	 * @return string Plain text description.
	 */
	public static function get_event_description(int $event_id, \WP_Post $event_post): string
	{
		$description = '';

		if (has_excerpt($event_id)) {
			$description = get_the_excerpt($event_id);
		} elseif (! empty($event_post->post_content)) {
			$clean = strip_shortcodes($event_post->post_content);
			$clean = wp_strip_all_tags($clean);
			$description = wp_trim_words($clean, 55, '...');
		}

		return trim(html_entity_decode(wp_strip_all_tags($description), ENT_QUOTES, 'UTF-8'));
	}

	/**
	 * Get event image URL from 'event_cover_img' or fallback to featured image.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Image URL or empty string.
	 */
	public static function get_event_image(int $event_id): string
	{
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

		if (has_post_thumbnail($event_id)) {
			$thumb_url = get_the_post_thumbnail_url($event_id, 'full');
			if ($thumb_url) {
				return $thumb_url;
			}
		}

		return '';
	}
}
