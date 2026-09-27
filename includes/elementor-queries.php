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
 * Filter upcoming events query for Elementor Posts / Loop Grid widget.
 * Query ID: upcoming_events
 */
if ( ! has_filter( 'elementor/query/upcoming_events' ) ) {
	add_filter( 'elementor/query/upcoming_events', function( $query ) {

		// ACF date picker Ymd format
		$today = gmdate( 'Ymd' );

		$query->set( 'post_type',   'tribe_events' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'orderby',     'meta_value' );
		$query->set( 'order',       'ASC' );
		$query->set( 'meta_key',    'eventdate' );  // ACF field ID

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query->set( 'meta_query', [
			[
				'key'     => 'eventdate',   // ACF field ID
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'NUMERIC',     // NUMERIC for Ymd format
			],
		] );

	} );
}
