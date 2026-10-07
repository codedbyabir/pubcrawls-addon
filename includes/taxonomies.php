<?php
/**
 * Taxonomies — Pubcrawl Addon
 * Registers: Venue Category, Event Category (tribe_events_cat)
 *
 * @package Pubcrawls_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pubcrawls_register_taxonomies' ) ) {
	/**
	 * Register custom taxonomies conditionally based on admin settings.
	 */
	function pubcrawls_register_taxonomies() {

		$disabled = get_option( 'pubcrawls_disabled_taxonomies', array() );
		if ( ! is_array( $disabled ) ) {
			$disabled = array();
		}

		// ── Venue Category ────────────────────────────────────────
		if ( ! in_array( 'venue_category', $disabled, true ) ) {
			register_taxonomy(
				'venue_category',
				'venue',
				array(
					'labels'       => array(
						'name'              => _x( 'Venue Categories', 'taxonomy general name', 'pubcrawls-addon' ),
						'singular_name'     => _x( 'Venue Category', 'taxonomy singular name', 'pubcrawls-addon' ),
						'search_items'      => __( 'Search Venue Categories', 'pubcrawls-addon' ),
						'all_items'         => __( 'All Venue Categories', 'pubcrawls-addon' ),
						'parent_item'       => __( 'Parent Venue Category', 'pubcrawls-addon' ),
						'parent_item_colon' => __( 'Parent Venue Category:', 'pubcrawls-addon' ),
						'edit_item'         => __( 'Edit Venue Category', 'pubcrawls-addon' ),
						'update_item'       => __( 'Update Venue Category', 'pubcrawls-addon' ),
						'add_new_item'      => __( 'Add New Venue Category', 'pubcrawls-addon' ),
						'new_item_name'     => __( 'New Venue Category', 'pubcrawls-addon' ),
						'menu_name'         => __( 'Venue Categories', 'pubcrawls-addon' ),
					),
					'hierarchical' => true,
					'public'       => true,
					'show_in_rest' => true,
					'rewrite'      => array( 'slug' => Pubcrawls_Admin::get_slug( 'taxonomies', 'venue_category' ) ),
				)
			);
		}

		// ── Event Category (tribe_events_cat) ─────────────────────
		if ( ! in_array( 'tribe_events_cat', $disabled, true ) ) {
			register_taxonomy(
				'tribe_events_cat',
				'tribe_events',
				array(
					'labels'       => array(
						'name'              => _x( 'Events Categories', 'taxonomy general name', 'pubcrawls-addon' ),
						'singular_name'     => _x( 'Event Category', 'taxonomy singular name', 'pubcrawls-addon' ),
						'search_items'      => __( 'Search Event Category', 'pubcrawls-addon' ),
						'all_items'         => __( 'All Event Categories', 'pubcrawls-addon' ),
						'parent_item'       => __( 'Parent Event Category', 'pubcrawls-addon' ),
						'parent_item_colon' => __( 'Parent Event Category:', 'pubcrawls-addon' ),
						'edit_item'         => __( 'Edit Event Category', 'pubcrawls-addon' ),
						'update_item'       => __( 'Update Event Category', 'pubcrawls-addon' ),
						'add_new_item'      => __( 'Add New Event Category', 'pubcrawls-addon' ),
						'new_item_name'     => __( 'New Event Category', 'pubcrawls-addon' ),
						'menu_name'         => __( 'Event Category', 'pubcrawls-addon' ),
					),
					'hierarchical' => true,
					'show_in_rest' => true,
					'rewrite'      => array( 'slug' => Pubcrawls_Admin::get_slug( 'taxonomies', 'tribe_events_cat' ) ),
				)
			);
		}
	}
}

if ( ! has_action( 'init', 'pubcrawls_register_taxonomies' ) ) {
	add_action( 'init', 'pubcrawls_register_taxonomies', 0 );
}
