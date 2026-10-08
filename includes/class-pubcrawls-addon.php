<?php

/**
 * Main Pubcrawls_Addon Class
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit;
}

final class Pubcrawls_Addon
{

	/**
	 * Single instance of the class.
	 *
	 * @var Pubcrawls_Addon|null
	 */
	private static $instance = null;

	/**
	 * Minimum Elementor Version required.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

	/**
	 * Minimum PHP Version required.
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * Main Pubcrawls_Addon Instance.
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		add_action('plugins_loaded', [$this, 'init']);
	}

	/**
	 * Initialize plugin after all plugins are loaded.
	 */
	public function init()
	{
		// Check PHP version.
		if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
			add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
			return;
		}

		// Check if Elementor is installed and activated.
		if (! did_action('elementor/loaded')) {
			add_action('admin_notices', [$this, 'admin_notice_missing_elementor']);
			return;
		}

		// Check Elementor version.
		if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '<')) {
			add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
			return;
		}

		// Register Elementor Widget Category.
		add_action('elementor/elements/categories_registered', [$this, 'register_categories']);

		// Register Widgets.
		if (version_compare(ELEMENTOR_VERSION, '3.5.0', '>=')) {
			add_action('elementor/widgets/register', [$this, 'register_widgets']);
		} else {
			add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets_legacy']);
		}

		// Enqueue editor preview styles.
		add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_editor_styles']);
	}

	/**
	 * Admin notice: Missing Elementor.
	 */
	public function admin_notice_missing_elementor()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_GET['activate'])) unset($_GET['activate']);
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__('"%1$s" requires "%2$s" to be installed and activated for Elementor widgets.', 'pubcrawls-addon'),
			'<strong>' . esc_html__('Pubcrawl Addon', 'pubcrawls-addon') . '</strong>',
			'<strong>' . esc_html__('Elementor', 'pubcrawls-addon') . '</strong>'
		);
		printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post($message));
	}

	/**
	 * Admin notice: Minimum Elementor version.
	 */
	public function admin_notice_minimum_elementor_version()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_GET['activate'])) unset($_GET['activate']);
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'pubcrawls-addon'),
			'<strong>' . esc_html__('Pubcrawl Addon', 'pubcrawls-addon') . '</strong>',
			'<strong>' . esc_html__('Elementor', 'pubcrawls-addon') . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);
		printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post($message));
	}

	/**
	 * Admin notice: Minimum PHP version.
	 */
	public function admin_notice_minimum_php_version()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_GET['activate'])) unset($_GET['activate']);
		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'pubcrawls-addon'),
			'<strong>' . esc_html__('Pubcrawl Addon', 'pubcrawls-addon') . '</strong>',
			'<strong>' . esc_html__('PHP', 'pubcrawls-addon') . '</strong>',
			self::MINIMUM_PHP_VERSION
		);
		printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post($message));
	}

	/**
	 * Register "PubCrawls" Category in Elementor.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_categories($elements_manager)
	{
		$elements_manager->add_category(
			'pubcrawls',
			[
				'title' => esc_html__('PubCrawls', 'pubcrawls-addon'),
				'icon'  => 'eicon-globe',
			]
		);
	}

	/**
	 * Get widget classes list.
	 *
	 * @return array
	 */
	public function get_widget_classes()
	{
		$widget_map = [
			'pc-cities-list'     => 'Pubcrawls_Cities_List',
			'pc-city-archive'    => 'Pubcrawls_City_Archive',
			'pc-city-events'     => 'Pubcrawls_City_Events',
			'pc-city-map'        => 'Pubcrawls_City_Map',
			'pc-city-venues'     => 'Pubcrawls_City_Venues',
			'pc-event-agenda'    => 'Pubcrawls_Event_Agenda',
			'pc-event-sticky'    => 'Pubcrawls_Event_Sticky',
			'pc-event-tickets'   => 'Pubcrawls_Event_Tickets',
			'pc-event-venues'    => 'Pubcrawls_Event_Venues',
			'pc-faq-grid'        => 'Pubcrawls_FAQ_Grid',
			'pc-get-directions'  => 'Pubcrawls_Get_Directions',
			'pc-job-cities'      => 'Pubcrawls_Job_Cities',
			'pc-next-event-date' => 'Pubcrawls_Next_Event_Date',
			'pc-page-faqs'       => 'Pubcrawls_Page_FAQs',
			'pc-social-share'    => 'Pubcrawls_Social_Share',
			'pc-ai-quick-links'  => 'Pubcrawls_AI_Quick_Links',
<<<<<<< Updated upstream
=======
			'pc-gravity-forms'   => 'Pubcrawls_Gravity_Forms',
			'pc-timeline'        => 'Pubcrawls_Timeline',
			'pc-taxonomy-terms'  => 'Pubcrawls_Taxonomy_Terms',
>>>>>>> Stashed changes
		];

		$disabled = get_option('pubcrawls_disabled_widgets', []);
		if (! is_array($disabled)) {
			$disabled = [];
		}

		$enabled = [];
		foreach ($widget_map as $slug => $class_name) {
			if (! in_array($slug, $disabled, true)) {
				$enabled[] = $class_name;
			}
		}

		return $enabled;
	}

	/**
	 * Load widget files.
	 */
	private function load_widget_files()
	{
		$widgets_dir = PUBCRAWLS_ADDON_DIR . 'includes/widgets/';
		if (! is_dir($widgets_dir)) {
			return;
		}

		foreach (glob($widgets_dir . 'pubcrawl-*.php') as $widget_file) {
			require_once $widget_file;
		}
	}

	/**
	 * Register Elementor Widgets (Elementor 3.5+).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets($widgets_manager)
	{
		$this->load_widget_files();

		foreach ($this->get_widget_classes() as $class_name) {
			if (class_exists($class_name)) {
				$widgets_manager->register(new $class_name());
			}
		}
	}

	/**
	 * Register Elementor Widgets Legacy (Elementor < 3.5).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets_legacy($widgets_manager)
	{
		$this->load_widget_files();

		foreach ($this->get_widget_classes() as $class_name) {
			if (class_exists($class_name)) {
				$widgets_manager->register_widget_type(new $class_name());
			}
		}
	}

	/**
	 * Enqueue Editor styles for widget preview in Elementor editor.
	 */
	public function enqueue_editor_styles()
	{
		wp_enqueue_style(
			'pubcrawls-addon-editor',
			PUBCRAWLS_ADDON_URL . 'assets/css/addon-editor.css',
			['dashicons'],
			PUBCRAWLS_ADDON_VERSION
		);

		// Core plugin stylesheet inside editor
		wp_enqueue_style(
			'pub-crawls-styles',
			PUBCRAWLS_ADDON_URL . 'assets/css/style.css',
			[],
			PUBCRAWLS_ADDON_VERSION
		);

		// Ticket stylesheet inside editor
		wp_enqueue_style(
			'pcet-styles',
			PUBCRAWLS_ADDON_URL . 'assets/css/tickets.css',
			[],
			PUBCRAWLS_ADDON_VERSION
		);
<<<<<<< Updated upstream
=======

		// Gravity Forms stylesheet inside editor
		wp_enqueue_style(
			'pc-gravity-forms',
			PUBCRAWLS_ADDON_URL . 'assets/css/gravity-forms.css',
			[],
			PUBCRAWLS_ADDON_VERSION
		);

		// Steps Timeline stylesheet inside editor
		wp_enqueue_style(
			'pc-timeline-css',
			PUBCRAWLS_ADDON_URL . 'assets/css/timeline.css',
			[],
			PUBCRAWLS_ADDON_VERSION
		);

		// Taxonomy Terms stylesheet inside editor
		wp_enqueue_style(
			'pc-taxonomy-terms-css',
			PUBCRAWLS_ADDON_URL . 'assets/css/taxonomy-terms.css',
			[],
			PUBCRAWLS_ADDON_VERSION
		);
>>>>>>> Stashed changes
	}
}
