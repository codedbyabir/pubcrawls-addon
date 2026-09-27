<?php
/**
 * PubCrawls Admin Dashboard & Settings
 *
 * Manages admin menus, settings, widget management, post types, taxonomies, and system health.
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pubcrawls_Admin
 *
 * Handles admin functionality, settings, post types, and widget configurations.
 */
class Pubcrawls_Admin {

	/**
	 * Option keys.
	 */
	const SETTINGS_KEY   = 'pubcrawls_settings';
	const WIDGETS_KEY    = 'pubcrawls_disabled_widgets';
	const POST_TYPES_KEY = 'pubcrawls_disabled_post_types';
	const TAXONOMIES_KEY = 'pubcrawls_disabled_taxonomies';

	/**
	 * Instance.
	 *
	 * @var Pubcrawls_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Initialize admin module.
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Register Admin Menus.
	 */
	public function register_menu(): void {
		$disabled_pt = get_option( self::POST_TYPES_KEY, array() );
		if ( ! is_array( $disabled_pt ) ) {
			$disabled_pt = array();
		}

		// Main Menu.
		add_menu_page(
			esc_html__( 'PubCrawls Dashboard', 'pubcrawls-addon' ),
			esc_html__( 'PubCrawls', 'pubcrawls-addon' ),
			'manage_options',
			'pubcrawls-addon',
			array( $this, 'render_dashboard' ),
			'dashicons-tickets-alt',
			30
		);

		// Submenu: Dashboard.
		add_submenu_page(
			'pubcrawls-addon',
			esc_html__( 'Dashboard & Settings', 'pubcrawls-addon' ),
			esc_html__( 'Dashboard', 'pubcrawls-addon' ),
			'manage_options',
			'pubcrawls-addon',
			array( $this, 'render_dashboard' )
		);

		// Submenu: Cities shortcut (if active).
		if ( ! in_array( 'city', $disabled_pt, true ) ) {
			add_submenu_page(
				'pubcrawls-addon',
				esc_html__( 'All Cities', 'pubcrawls-addon' ),
				esc_html__( 'Cities', 'pubcrawls-addon' ),
				'manage_options',
				'edit.php?post_type=city'
			);
		}

		// Submenu: Events shortcut (if active).
		if ( ! in_array( 'tribe_events', $disabled_pt, true ) ) {
			add_submenu_page(
				'pubcrawls-addon',
				esc_html__( 'All Events', 'pubcrawls-addon' ),
				esc_html__( 'Events', 'pubcrawls-addon' ),
				'manage_options',
				'edit.php?post_type=tribe_events'
			);
		}

		// Submenu: FAQs shortcut (if active).
		if ( ! in_array( 'faq', $disabled_pt, true ) ) {
			add_submenu_page(
				'pubcrawls-addon',
				esc_html__( 'All FAQs', 'pubcrawls-addon' ),
				esc_html__( 'FAQs', 'pubcrawls-addon' ),
				'manage_options',
				'edit.php?post_type=faq'
			);
		}
	}

	/**
	 * Register Plugin Settings.
	 */
	public function register_settings(): void {
		register_setting(
			'pubcrawls_settings_group',
			self::SETTINGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);

		register_setting(
			'pubcrawls_settings_group',
			self::WIDGETS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_disabled_widgets' ),
				'default'           => array(),
			)
		);

		register_setting(
			'pubcrawls_settings_group',
			self::POST_TYPES_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_disabled_post_types' ),
				'default'           => array(),
			)
		);

		register_setting(
			'pubcrawls_settings_group',
			self::TAXONOMIES_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_disabled_taxonomies' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Get default settings.
	 */
	public static function get_default_settings(): array {
		return array(
			'default_ticket_provider'     => 'squadup',
			'squadup_confirmation_url'    => 'https://pubcrawls.com/purchase-confirmation/',
			'squadup_sms_question'        => 'Phone Number for SMS Updates related to your event(s).',
			'squadup_enable_sms'          => '1',
			'eventbrite_confirmation_url' => 'https://pubcrawls.com/purchase-confirmation/',
			'eventbrite_open_fallback'    => '1',
		);
	}

	/**
	 * Get a specific plugin setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default_value Fallback value.
	 * @return mixed
	 */
	public static function get_setting( string $key, $default_value = null ) {
		$settings = get_option( self::SETTINGS_KEY, self::get_default_settings() );
		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		$defaults = self::get_default_settings();
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default_value;
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * @param array $input Raw input data from settings form.
	 * @return array
	 */
	public function sanitize_settings( $input ): array {
		$clean    = array();
		$defaults = self::get_default_settings();

		$clean['default_ticket_provider'] = ( isset( $input['default_ticket_provider'] ) && 'eventbrite' === $input['default_ticket_provider'] )
			? 'eventbrite'
			: 'squadup';

		$clean['squadup_confirmation_url'] = ! empty( $input['squadup_confirmation_url'] )
			? esc_url_raw( $input['squadup_confirmation_url'] )
			: $defaults['squadup_confirmation_url'];

		$clean['squadup_sms_question'] = ! empty( $input['squadup_sms_question'] )
			? sanitize_text_field( $input['squadup_sms_question'] )
			: $defaults['squadup_sms_question'];

		$clean['squadup_enable_sms'] = ! empty( $input['squadup_enable_sms'] ) ? '1' : '0';

		$clean['eventbrite_confirmation_url'] = ! empty( $input['eventbrite_confirmation_url'] )
			? esc_url_raw( $input['eventbrite_confirmation_url'] )
			: $defaults['eventbrite_confirmation_url'];

		$clean['eventbrite_open_fallback'] = ! empty( $input['eventbrite_open_fallback'] ) ? '1' : '0';

		return $clean;
	}

	/**
	 * Sanitize disabled widgets list.
	 *
	 * @param mixed $input Raw input data.
	 * @return array
	 */
	public function sanitize_disabled_widgets( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$valid_slugs = array_keys( self::get_widget_manifest() );
		return array_values( array_intersect( $input, $valid_slugs ) );
	}

	/**
	 * Sanitize disabled post types list.
	 *
	 * @param mixed $input Raw input data.
	 * @return array
	 */
	public function sanitize_disabled_post_types( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$valid_slugs = array_keys( self::get_post_types_manifest() );
		return array_values( array_intersect( $input, $valid_slugs ) );
	}

	/**
	 * Sanitize disabled taxonomies list.
	 *
	 * @param mixed $input Raw input data.
	 * @return array
	 */
	public function sanitize_disabled_taxonomies( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$valid_slugs = array_keys( self::get_taxonomies_manifest() );
		return array_values( array_intersect( $input, $valid_slugs ) );
	}

	/**
	 * Enqueue Admin CSS & JS.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'toplevel_page_pubcrawls-addon' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'pubcrawls-admin-dashboard',
			PUBCRAWLS_ADDON_URL . 'assets/css/admin-dashboard.css',
			array(),
			PUBCRAWLS_ADDON_VERSION
		);

		wp_enqueue_script(
			'pubcrawls-admin-dashboard',
			PUBCRAWLS_ADDON_URL . 'assets/js/admin-dashboard.js',
			array( 'jquery' ),
			PUBCRAWLS_ADDON_VERSION,
			true
		);
	}

	/**
	 * Custom Post Types manifest.
	 */
	public static function get_post_types_manifest(): array {
		return array(
			'city'         => array(
				'title'       => esc_html__( 'City', 'pubcrawls-addon' ),
				'slug'        => 'city',
				'icon'        => 'dashicons-location',
				'description' => esc_html__( 'Core post type for city crawl locations, city directories, and archives.', 'pubcrawls-addon' ),
			),
			'tribe_events' => array(
				'title'       => esc_html__( 'Events', 'pubcrawls-addon' ),
				'slug'        => 'tribe_events',
				'icon'        => 'dashicons-calendar-alt',
				'description' => esc_html__( 'Main pub crawl events post type with date repeaters and ticket pricing.', 'pubcrawls-addon' ),
			),
			'venue'        => array(
				'title'       => esc_html__( 'Venue', 'pubcrawls-addon' ),
				'slug'        => 'venue',
				'icon'        => 'dashicons-store',
				'description' => esc_html__( 'Participating bars, pubs, clubs, and check-in venue locations.', 'pubcrawls-addon' ),
			),
			'faq'          => array(
				'title'       => esc_html__( 'FAQ', 'pubcrawls-addon' ),
				'slug'        => 'faq',
				'icon'        => 'dashicons-editor-help',
				'description' => esc_html__( 'Global frequently asked questions queried by FAQ grid widgets.', 'pubcrawls-addon' ),
			),
			'press'        => array(
				'title'       => esc_html__( 'Press', 'pubcrawls-addon' ),
				'slug'        => 'press',
				'icon'        => 'dashicons-megaphone',
				'description' => esc_html__( 'Media releases, press coverage, news, and official announcements.', 'pubcrawls-addon' ),
			),
			'city_map'     => array(
				'title'       => esc_html__( 'City Map', 'pubcrawls-addon' ),
				'slug'        => 'city_map',
				'icon'        => 'dashicons-location-alt',
				'description' => esc_html__( 'Dedicated map post type for custom map layouts and Mapme embeds.', 'pubcrawls-addon' ),
			),
		);
	}

	/**
	 * Custom Taxonomies manifest.
	 */
	public static function get_taxonomies_manifest(): array {
		return array(
			'tribe_events_cat' => array(
				'title'       => esc_html__( 'Event Category', 'pubcrawls-addon' ),
				'slug'        => 'tribe_events_cat',
				'icon'        => 'dashicons-tag',
				'object'      => 'tribe_events',
				'description' => esc_html__( 'Hierarchical categories for events (e.g. St. Patrick\'s, Halloween, NYE).', 'pubcrawls-addon' ),
			),
			'venue_category'   => array(
				'title'       => esc_html__( 'Venue Category', 'pubcrawls-addon' ),
				'slug'        => 'venue_category',
				'icon'        => 'dashicons-tag',
				'object'      => 'venue',
				'description' => esc_html__( 'Categories to group and filter participating crawl venues.', 'pubcrawls-addon' ),
			),
		);
	}

	/**
	 * Widget manifest with metadata.
	 */
	public static function get_widget_manifest(): array {
		return array(
			'pc-cities-card'     => array(
				'title'       => esc_html__( 'Cities Card Grid', 'pubcrawls-addon' ),
				'icon'        => 'eicon-gallery-grid',
				'file'        => 'pubcrawl-cities-card.php',
				'class'       => 'Pubcrawls_Cities_Card',
				'description' => esc_html__( 'Displays visual cards of cities with dynamic upcoming event counts.', 'pubcrawls-addon' ),
			),
			'pc-cities-list'     => array(
				'title'       => esc_html__( 'Cities Directory / List', 'pubcrawls-addon' ),
				'icon'        => 'eicon-editor-list-ul',
				'file'        => 'pubcrawl-cities-list.php',
				'class'       => 'Pubcrawls_Cities_List',
				'description' => esc_html__( 'Alphabetical city directory with "Most Popular" and "All Cities" toggle.', 'pubcrawls-addon' ),
			),
			'pc-city-archive'    => array(
				'title'       => esc_html__( 'City Archive Grid', 'pubcrawls-addon' ),
				'icon'        => 'eicon-archive-posts',
				'file'        => 'pubcrawl-city-archive.php',
				'class'       => 'Pubcrawls_City_Archive',
				'description' => esc_html__( 'City cards filterable by event category for archive and landing pages.', 'pubcrawls-addon' ),
			),
			'pc-city-events'     => array(
				'title'       => esc_html__( 'City Events Grid', 'pubcrawls-addon' ),
				'icon'        => 'eicon-post-list',
				'file'        => 'pubcrawl-city-events.php',
				'class'       => 'Pubcrawls_City_Events',
				'description' => esc_html__( 'Multi-event listing grid with interactive date dropdown & ticket checkout.', 'pubcrawls-addon' ),
			),
			'pc-city-map'        => array(
				'title'       => esc_html__( 'Interactive City Map', 'pubcrawls-addon' ),
				'icon'        => 'eicon-google-maps',
				'file'        => 'pubcrawl-city-map.php',
				'class'       => 'Pubcrawls_City_Map',
				'description' => esc_html__( 'Embeds responsive Mapme or interactive map from ACF map_url field.', 'pubcrawls-addon' ),
			),
			'pc-city-venues'     => array(
				'title'       => esc_html__( 'City Venues List', 'pubcrawls-addon' ),
				'icon'        => 'eicon-map-pin',
				'file'        => 'pubcrawl-city-venues.php',
				'class'       => 'Pubcrawls_City_Venues',
				'description' => esc_html__( 'Grid of bars, pubs, and venues associated with a specific city.', 'pubcrawls-addon' ),
			),
			'pc-event-agenda'    => array(
				'title'       => esc_html__( 'Event Agenda / Schedule', 'pubcrawls-addon' ),
				'icon'        => 'eicon-time-line',
				'file'        => 'pubcrawl-event-agenda.php',
				'class'       => 'Pubcrawls_Event_Agenda',
				'description' => esc_html__( 'Timeline displaying crawl stops, check-in times, and schedule steps.', 'pubcrawls-addon' ),
			),
			'pc-event-sticky'    => array(
				'title'       => esc_html__( 'Event Sticky Bar', 'pubcrawls-addon' ),
				'icon'        => 'eicon-footer',
				'file'        => 'pubcrawl-event-sticky.php',
				'class'       => 'Pubcrawls_Event_Sticky',
				'description' => esc_html__( 'Floating bottom bar displaying event date and sticky ticket button.', 'pubcrawls-addon' ),
			),
			'pc-event-tickets'   => array(
				'title'       => esc_html__( 'Event Tickets Card', 'pubcrawls-addon' ),
				'icon'        => 'eicon-price-table',
				'file'        => 'pubcrawl-event-tickets.php',
				'class'       => 'Pubcrawls_Event_Tickets',
				'description' => esc_html__( 'Primary single-event ticket card with synchronized date selector & modal checkout.', 'pubcrawls-addon' ),
			),
			'pc-event-venues'    => array(
				'title'       => esc_html__( 'Event Participating Venues', 'pubcrawls-addon' ),
				'icon'        => 'eicon-gallery-grid',
				'file'        => 'pubcrawl-event-venues.php',
				'class'       => 'Pubcrawls_Event_Venues',
				'description' => esc_html__( 'Displays participating venues and addresses for the current event.', 'pubcrawls-addon' ),
			),
			'pc-faq-grid'        => array(
				'title'       => esc_html__( 'FAQ Grid', 'pubcrawls-addon' ),
				'icon'        => 'eicon-help-o',
				'file'        => 'pubcrawl-faq-grid.php',
				'class'       => 'Pubcrawls_FAQ_Grid',
				'description' => esc_html__( 'Accordion or grid FAQs queried from the custom FAQ post type.', 'pubcrawls-addon' ),
			),
			'pc-get-directions'  => array(
				'title'       => esc_html__( 'Get Directions Button', 'pubcrawls-addon' ),
				'icon'        => 'eicon-navigation-horizontal',
				'file'        => 'pubcrawl-get-directions.php',
				'class'       => 'Pubcrawls_Get_Directions',
				'description' => esc_html__( 'Dynamic button opening Google Maps directions to the check-in venue.', 'pubcrawls-addon' ),
			),
			'pc-job-cities'      => array(
				'title'       => esc_html__( 'Job Cities Selector', 'pubcrawls-addon' ),
				'icon'        => 'eicon-user-preferences',
				'file'        => 'pubcrawl-job-cities.php',
				'class'       => 'Pubcrawls_Job_Cities',
				'description' => esc_html__( 'City listing specifically designed for careers & job application forms.', 'pubcrawls-addon' ),
			),
			'pc-next-event-date' => array(
				'title'       => esc_html__( 'Next Event Date Badge', 'pubcrawls-addon' ),
				'icon'        => 'eicon-calendar',
				'file'        => 'pubcrawl-next-event-date.php',
				'class'       => 'Pubcrawls_Next_Event_Date',
				'description' => esc_html__( 'Shows next upcoming event date with customizable icon and typography.', 'pubcrawls-addon' ),
			),
			'pc-page-faqs'       => array(
				'title'       => esc_html__( 'Page / Post FAQs', 'pubcrawls-addon' ),
				'icon'        => 'eicon-help-o',
				'file'        => 'pubcrawl-page-faqs.php',
				'class'       => 'Pubcrawls_Page_FAQs',
				'description' => esc_html__( 'Displays contextual FAQs populated from current page/post ACF repeater.', 'pubcrawls-addon' ),
			),
			'pc-social-share'    => array(
				'title'       => esc_html__( 'Social Share Buttons', 'pubcrawls-addon' ),
				'icon'        => 'eicon-share',
				'file'        => 'pubcrawl-social-share.php',
				'class'       => 'Pubcrawls_Social_Share',
				'description' => esc_html__( 'One-click Facebook, X (Twitter), and LinkedIn share buttons.', 'pubcrawls-addon' ),
			),
			'pc-ai-quick-links'  => array(
				'title'       => esc_html__( 'AI Quick Links', 'pubcrawls-addon' ),
				'icon'        => 'eicon-share',
				'file'        => 'pubcrawl-ai-quick-links.php',
				'class'       => 'Pubcrawls_AI_Quick_Links',
				'description' => esc_html__( 'Direct query buttons for ChatGPT, Perplexity, Google AI, and Microsoft Copilot.', 'pubcrawls-addon' ),
			),
		);
	}

	/**
	 * System Health & Dependencies.
	 */
	public static function get_system_health(): array {
		$elementor_installed = did_action( 'elementor/loaded' );
		$elementor_version   = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null;
		$elementor_min_ok    = $elementor_installed && version_compare( $elementor_version, '3.5.0', '>=' );

		$acf_installed = function_exists( 'get_field' );
		$acf_version   = defined( 'ACF_VERSION' ) ? ACF_VERSION : null;

		$tec_installed = post_type_exists( 'tribe_events' );

		return array(
			'elementor' => array(
				'label'   => 'Elementor',
				'status'  => $elementor_min_ok ? 'ok' : ( $elementor_installed ? 'warning' : 'error' ),
				'version' => ! empty( $elementor_version ) ? $elementor_version : esc_html__( 'Not Active', 'pubcrawls-addon' ),
				'note'    => $elementor_min_ok ? esc_html__( 'Compatible (v3.5+)', 'pubcrawls-addon' ) : esc_html__( 'Required for Elementor widgets', 'pubcrawls-addon' ),
			),
			'acf'       => array(
				'label'   => 'Advanced Custom Fields',
				'status'  => $acf_installed ? 'ok' : 'error',
				'version' => ! empty( $acf_version ) ? $acf_version : ( $acf_installed ? esc_html__( 'Active', 'pubcrawls-addon' ) : esc_html__( 'Not Active', 'pubcrawls-addon' ) ),
				'note'    => $acf_installed ? esc_html__( 'Required ACF functions available', 'pubcrawls-addon' ) : esc_html__( 'Required for event dates, venues & maps', 'pubcrawls-addon' ),
			),
			'tec'       => array(
				'label'   => 'The Events Calendar',
				'status'  => $tec_installed ? 'ok' : 'info',
				'version' => defined( 'TRIBE_EVENTS_FILE' ) ? 'Active' : ( $tec_installed ? 'Custom Post Type' : 'Not Detected' ),
				'note'    => $tec_installed ? esc_html__( 'tribe_events post type is active', 'pubcrawls-addon' ) : esc_html__( 'Custom post type or plugin active', 'pubcrawls-addon' ),
			),
			'php'       => array(
				'label'   => 'PHP Version',
				'status'  => version_compare( PHP_VERSION, '7.4', '>=' ) ? 'ok' : 'error',
				'version' => PHP_VERSION,
				'note'    => esc_html__( 'Server runtime environment', 'pubcrawls-addon' ),
			),
		);
	}

	/**
	 * Get overview post type counts.
	 */
	public static function get_overview_counts(): array {
		$count_cities = wp_count_posts( 'city' );
		$count_events = wp_count_posts( 'tribe_events' );
		$count_faqs   = wp_count_posts( 'faq' );
		$count_venues = wp_count_posts( 'venue' );

		return array(
			'total_cities' => isset( $count_cities->publish ) ? intval( $count_cities->publish ) : 0,
			'total_events' => isset( $count_events->publish ) ? intval( $count_events->publish ) : 0,
			'total_faqs'   => isset( $count_faqs->publish ) ? intval( $count_faqs->publish ) : 0,
			'total_venues' => isset( $count_venues->publish ) ? intval( $count_venues->publish ) : 0,
		);
	}

	/**
	 * Render the Dashboard View.
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$view_path = PUBCRAWLS_ADDON_DIR . 'includes/admin/views/admin-dashboard.php';
		if ( file_exists( $view_path ) ) {
			require_once $view_path;
		}
	}
}
