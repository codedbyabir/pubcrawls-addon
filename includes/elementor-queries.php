<?php
/**
 * Elementor Custom Queries — Pubcrawl Addon
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter upcoming events query for Elementor Posts, Loop Grid, and Loop Carousel widgets.
 *
 * Query IDs supported:
 * - upcoming_events
 * - upcoming-events
 * - upcoming_event
 *
 * Automatically resolves:
 * 1. Post type: Checks if widget source was left as default 'post', and switches to
 *    'tribe_events' (or the active event post type), while preserving custom post types if explicitly selected.
 * 2. Multi-tier date keys: Checks ACF repeater dates ('event_dates_0_eventdate', etc.),
 *    legacy/standalone ACF 'eventdate', 'event_date', and The Events Calendar '_EventStartDate'.
 * 3. Date formats: Uses DATE comparison against current date (Y-m-d) so both 'Ymd' (e.g. 20261031),
 *    'Y-m-d' (e.g. 2026-10-31), and datetime strings (e.g. 2026-10-31 18:00:00) match seamlessly.
 * 4. Chronological sorting: Orders upcoming events from nearest to farthest (ASC).
 * 5. Widget filter preservation: Safely merges with any taxonomy filters or existing meta queries.
 *
 * @param \WP_Query $query  The current WP_Query instance.
 * @param mixed     $widget Optional Elementor widget instance.
 */
function pubcrawls_filter_upcoming_events_query( $query, $widget = null ) {
	// ── 1. Resolve Target Post Type ───────────────────────────────────────────
	$existing_pt = $query->get( 'post_type' );

	// Elementor Loop Carousel / Loop Grid defaults to 'post' if untouched.
	// If empty or set to default 'post', resolve to the site's events CPT.
	if ( empty( $existing_pt ) || 'post' === $existing_pt ) {
		if ( post_type_exists( 'tribe_events' ) ) {
			$post_type = 'tribe_events';
		} elseif ( post_type_exists( 'events' ) ) {
			$post_type = 'events';
		} elseif ( post_type_exists( 'event' ) ) {
			$post_type = 'event';
		} else {
			$post_type = 'tribe_events';
		}
	} else {
		$post_type = $existing_pt;
	}

	/**
	 * Filters the post type used for the upcoming events Elementor query.
	 *
	 * @param string|array $post_type The post type(s) to query.
	 * @param \WP_Query    $query     The current WP_Query instance.
	 */
	$post_type = apply_filters( 'pubcrawls_upcoming_events_post_type', $post_type, $query );

	// ── 2. Resolve Candidate Date Meta Keys ───────────────────────────────────
	// Supports ACF repeater rows (0..4), single ACF field, alternative naming, and TEC native key.
	$default_keys = [
		'event_dates_0_eventdate',
		'event_dates_1_eventdate',
		'event_dates_2_eventdate',
		'event_dates_3_eventdate',
		'event_dates_4_eventdate',
		'eventdate',
		'event_date',
		'_EventStartDate',
		'event_start_date',
	];

	// Support legacy single key filter if hooked
	$legacy_single_key = apply_filters( 'pubcrawls_upcoming_events_date_key', '', $query );
	if ( ! empty( $legacy_single_key ) && ! in_array( $legacy_single_key, $default_keys, true ) ) {
		array_unshift( $default_keys, $legacy_single_key );
	}

	/**
	 * Filters the list of meta keys inspected for event dates.
	 *
	 * @param array     $default_keys List of meta keys.
	 * @param \WP_Query $query        The current WP_Query instance.
	 */
	$candidate_keys = apply_filters( 'pubcrawls_upcoming_events_date_keys', $default_keys, $query );

	// Current date in site timezone (Y-m-d format for SQL DATE casting)
	$today = current_time( 'Y-m-d' );

	// ── 3. Build Date Meta Query Clauses & Ordering ───────────────────────────
	$date_clauses    = [ 'relation' => 'OR' ];
	$orderby_clauses = [];

	foreach ( $candidate_keys as $idx => $key ) {
		$clause_id = 'pubcrawl_date_clause_' . $idx;
		$date_clauses[ $clause_id ] = [
			'key'     => $key,
			'value'   => $today,
			'compare' => '>=',
			'type'    => 'DATE', // Works with Ymd (20261031), Y-m-d (2026-10-31), and datetime strings
		];
		$orderby_clauses[ $clause_id ] = 'ASC';
	}

	// ── 4. Apply to WP_Query ──────────────────────────────────────────────────
	$query->set( 'post_type',   $post_type );
	$query->set( 'post_status', 'publish' );
	$query->set( 'orderby',     $orderby_clauses );
	$query->set( 'order',       'ASC' );

	// DO NOT call $query->set( 'meta_key', ... ) as that forces an INNER JOIN on a single key
	// which breaks the OR relation across candidate date keys.

	// Safely merge with any existing meta query from the widget (e.g. city or other filters)
	$existing_meta = $query->get( 'meta_query' );
	if ( ! empty( $existing_meta ) && is_array( $existing_meta ) ) {
		$combined_meta = [
			'relation' => 'AND',
			$existing_meta,
			$date_clauses,
		];
	} else {
		$combined_meta = $date_clauses;
	}

	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	$query->set( 'meta_query', $combined_meta );
}

// Hook into Elementor query actions
$upcoming_query_ids = [
	'upcoming_events',
	'upcoming-events',
	'upcoming_event',
];

foreach ( $upcoming_query_ids as $qid ) {
	add_action( "elementor/query/{$qid}", 'pubcrawls_filter_upcoming_events_query', 10, 2 );
}
