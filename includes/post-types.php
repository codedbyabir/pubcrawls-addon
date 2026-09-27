<?php
/**
 * Post Types — Pubcrawl Addon
 * Registers: City, FAQ, Press, Venue, Events (tribe_events), City Map
 *
 * @package Pubcrawls_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pubcrawls_register_post_types' ) ) {
	/**
	 * Register custom post types conditionally based on admin settings.
	 */
	function pubcrawls_register_post_types() {

		$disabled = get_option( 'pubcrawls_disabled_post_types', array() );
		if ( ! is_array( $disabled ) ) {
			$disabled = array();
		}

		// ── City ──────────────────────────────────────────────────
		if ( ! in_array( 'city', $disabled, true ) ) {
			register_post_type(
				'city',
				array(
					'labels'        => array(
						'name'               => _x( 'City', 'post type general name', 'pubcrawls-addon' ),
						'singular_name'      => _x( 'City', 'post type singular name', 'pubcrawls-addon' ),
						'add_new'            => _x( 'Add New', 'City', 'pubcrawls-addon' ),
						'add_new_item'       => __( 'Add New City', 'pubcrawls-addon' ),
						'edit_item'          => __( 'Edit City', 'pubcrawls-addon' ),
						'new_item'           => __( 'New City', 'pubcrawls-addon' ),
						'all_items'          => __( 'All Cities', 'pubcrawls-addon' ),
						'view_item'          => __( 'View City', 'pubcrawls-addon' ),
						'search_items'       => __( 'Search City', 'pubcrawls-addon' ),
						'not_found'          => __( 'No City found', 'pubcrawls-addon' ),
						'not_found_in_trash' => __( 'No City found in the Trash', 'pubcrawls-addon' ),
						'menu_name'          => __( 'City', 'pubcrawls-addon' ),
					),
					'description'   => 'Holds City and City specific data',
					'public'        => true,
					'menu_position' => 5,
					'menu_icon'     => 'dashicons-location',
					'supports'      => array( 'title', 'editor', 'thumbnail' ),
					'has_archive'   => true,
					'show_in_rest'  => true,
				)
			);
		}

		// ── FAQ ───────────────────────────────────────────────────
		if ( ! in_array( 'faq', $disabled, true ) ) {
			register_post_type(
				'faq',
				array(
					'labels'        => array(
						'name'               => _x( 'FAQ', 'post type general name', 'pubcrawls-addon' ),
						'singular_name'      => _x( 'FAQ', 'post type singular name', 'pubcrawls-addon' ),
						'add_new'            => _x( 'Add New', 'FAQ', 'pubcrawls-addon' ),
						'add_new_item'       => __( 'Add New FAQ', 'pubcrawls-addon' ),
						'edit_item'          => __( 'Edit FAQ', 'pubcrawls-addon' ),
						'new_item'           => __( 'New FAQ', 'pubcrawls-addon' ),
						'all_items'          => __( 'All FAQs', 'pubcrawls-addon' ),
						'view_item'          => __( 'View FAQ', 'pubcrawls-addon' ),
						'search_items'       => __( 'Search FAQ', 'pubcrawls-addon' ),
						'not_found'          => __( 'No FAQ found', 'pubcrawls-addon' ),
						'not_found_in_trash' => __( 'No FAQ found in the Trash', 'pubcrawls-addon' ),
						'menu_name'          => __( 'FAQ', 'pubcrawls-addon' ),
					),
					'description'   => 'Holds FAQ and FAQ specific data',
					'public'        => true,
					'menu_position' => 6,
					'menu_icon'     => 'dashicons-editor-help',
					'supports'      => array( 'title', 'editor' ),
					'has_archive'   => true,
					'show_in_rest'  => true,
				)
			);
		}

		// ── Press ─────────────────────────────────────────────────
		if ( ! in_array( 'press', $disabled, true ) ) {
			register_post_type(
				'press',
				array(
					'labels'        => array(
						'name'               => _x( 'Press', 'post type general name', 'pubcrawls-addon' ),
						'singular_name'      => _x( 'Press', 'post type singular name', 'pubcrawls-addon' ),
						'add_new'            => _x( 'Add New', 'Press', 'pubcrawls-addon' ),
						'add_new_item'       => __( 'Add New Press', 'pubcrawls-addon' ),
						'edit_item'          => __( 'Edit Press', 'pubcrawls-addon' ),
						'new_item'           => __( 'New Press', 'pubcrawls-addon' ),
						'all_items'          => __( 'All Press', 'pubcrawls-addon' ),
						'view_item'          => __( 'View Press', 'pubcrawls-addon' ),
						'search_items'       => __( 'Search Press', 'pubcrawls-addon' ),
						'not_found'          => __( 'No Press found', 'pubcrawls-addon' ),
						'not_found_in_trash' => __( 'No Press found in the Trash', 'pubcrawls-addon' ),
						'menu_name'          => __( 'Press', 'pubcrawls-addon' ),
					),
					'description'   => 'Holds Press and Press specific data',
					'public'        => true,
					'menu_position' => 7,
					'menu_icon'     => 'dashicons-megaphone',
					'supports'      => array( 'title' ),
					'has_archive'   => false,
					'show_in_rest'  => true,
				)
			);
		}

		// ── Venue ─────────────────────────────────────────────────
		if ( ! in_array( 'venue', $disabled, true ) ) {
			register_post_type(
				'venue',
				array(
					'labels'        => array(
						'name'               => _x( 'Venue', 'post type general name', 'pubcrawls-addon' ),
						'singular_name'      => _x( 'Venue', 'post type singular name', 'pubcrawls-addon' ),
						'add_new'            => _x( 'Add New', 'Venue', 'pubcrawls-addon' ),
						'add_new_item'       => __( 'Add New Venue', 'pubcrawls-addon' ),
						'edit_item'          => __( 'Edit Venue', 'pubcrawls-addon' ),
						'new_item'           => __( 'New Venue', 'pubcrawls-addon' ),
						'all_items'          => __( 'All Venues', 'pubcrawls-addon' ),
						'view_item'          => __( 'View Venue', 'pubcrawls-addon' ),
						'search_items'       => __( 'Search Venues', 'pubcrawls-addon' ),
						'not_found'          => __( 'No Venues found', 'pubcrawls-addon' ),
						'not_found_in_trash' => __( 'No Venues found in the Trash', 'pubcrawls-addon' ),
						'menu_name'          => __( 'Venue', 'pubcrawls-addon' ),
					),
					'description'   => 'Holds Venues and Venue specific data',
					'public'        => true,
					'menu_position' => 8,
					'menu_icon'     => 'dashicons-store',
					'supports'      => array( 'title', 'editor', 'thumbnail' ),
					'has_archive'   => true,
					'show_in_rest'  => true,
					'rewrite'       => array( 'slug' => 'venues' ),
				)
			);
		}

		// ── Events (tribe_events) ─────────────────────────────────
		if ( ! in_array( 'tribe_events', $disabled, true ) ) {
			register_post_type(
				'tribe_events',
				array(
					'labels'        => array(
						'name'               => _x( 'Events', 'post type general name', 'pubcrawls-addon' ),
						'singular_name'      => _x( 'Events', 'post type singular name', 'pubcrawls-addon' ),
						'add_new'            => _x( 'Add New', 'Event', 'pubcrawls-addon' ),
						'add_new_item'       => __( 'Add New Event', 'pubcrawls-addon' ),
						'edit_item'          => __( 'Edit Event', 'pubcrawls-addon' ),
						'new_item'           => __( 'New Event', 'pubcrawls-addon' ),
						'all_items'          => __( 'All Events', 'pubcrawls-addon' ),
						'view_item'          => __( 'View Event', 'pubcrawls-addon' ),
						'search_items'       => __( 'Search Events', 'pubcrawls-addon' ),
						'not_found'          => __( 'No Events found', 'pubcrawls-addon' ),
						'not_found_in_trash' => __( 'No Events found in the Trash', 'pubcrawls-addon' ),
						'menu_name'          => __( 'Events', 'pubcrawls-addon' ),
					),
					'description'   => 'Holds Events and Events specific data',
					'public'        => true,
					'menu_position' => 9,
					'menu_icon'     => 'dashicons-calendar-alt',
					'supports'      => array( 'title', 'thumbnail' ),
					'has_archive'   => true,
					'show_in_rest'  => true,
				)
			);
		}

		// ── City Map ──────────────────────────────────────────────
		if ( ! in_array( 'city_map', $disabled, true ) ) {
			register_post_type(
				'city_map',
				array(
					'labels'        => array(
						'name'               => _x( 'City Map', 'post type general name', 'pubcrawls-addon' ),
						'singular_name'      => _x( 'City Map', 'post type singular name', 'pubcrawls-addon' ),
						'add_new'            => _x( 'Add New', 'City Map', 'pubcrawls-addon' ),
						'add_new_item'       => __( 'Add New City Map', 'pubcrawls-addon' ),
						'edit_item'          => __( 'Edit City Map', 'pubcrawls-addon' ),
						'new_item'           => __( 'New City Map', 'pubcrawls-addon' ),
						'all_items'          => __( 'All Cities Map', 'pubcrawls-addon' ),
						'view_item'          => __( 'View City Map', 'pubcrawls-addon' ),
						'search_items'       => __( 'Search City Map', 'pubcrawls-addon' ),
						'not_found'          => __( 'No City Map found', 'pubcrawls-addon' ),
						'not_found_in_trash' => __( 'No City Map found in the Trash', 'pubcrawls-addon' ),
						'menu_name'          => __( 'City Map', 'pubcrawls-addon' ),
					),
					'description'   => 'Holds City Map data',
					'public'        => true,
					'menu_position' => 10,
					'menu_icon'     => 'dashicons-location-alt',
					'supports'      => array( 'title', 'editor', 'thumbnail' ),
					'has_archive'   => true,
					'show_in_rest'  => true,
					'rewrite'       => array( 'slug' => 'maps' ),
				)
			);
		}
	}
}

if ( ! has_action( 'init', 'pubcrawls_register_post_types' ) ) {
	add_action( 'init', 'pubcrawls_register_post_types' );
}
