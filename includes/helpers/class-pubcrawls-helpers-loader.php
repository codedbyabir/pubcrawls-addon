<?php
/**
 * PubCrawls Helpers & Tools Loader
 *
 * Coordinates modular helper features and bulk tools within PubCrawls Addon.
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pubcrawls_Helpers_Loader
 */
class Pubcrawls_Helpers_Loader {

	/**
	 * Single instance.
	 *
	 * @var Pubcrawls_Helpers_Loader|null
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
	 * Initialize loader.
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_helpers();
	}

	/**
	 * Check if a specific helper is enabled in settings.
	 *
	 * @param string $helper_key Helper identifier (e.g. 'faq_seo_importer').
	 * @param string $default Default status ('1' or '0').
	 * @return bool
	 */
	public static function is_helper_enabled( string $helper_key, string $default = '1' ): bool {
		$setting_name = 'enable_' . $helper_key;
		if ( class_exists( 'Pubcrawls_Admin' ) ) {
			$val = Pubcrawls_Admin::get_setting( $setting_name, $default );
			return '1' === (string) $val;
		}
		return '1' === $default;
	}

	/**
	 * Load enabled helpers.
	 */
	private function load_helpers(): void {
		// 1. FAQ & SEO Bulk Importer Helper.
		if ( self::is_helper_enabled( 'faq_seo_importer', '1' ) ) {
			$importer_file = PUBCRAWLS_ADDON_DIR . 'includes/helpers/faq-seo-importer/class-pubcrawls-faq-seo-importer.php';
			if ( file_exists( $importer_file ) ) {
				require_once $importer_file;
				Pubcrawls_FAQ_SEO_Importer::init();
			}
		}

		// 2. Bulk Title Editor Helper (Append, Prepend, Remove, Find & Replace).
		if ( self::is_helper_enabled( 'bulk_title_editor', '1' ) ) {
			$editor_file = PUBCRAWLS_ADDON_DIR . 'includes/helpers/bulk-title-editor/class-pubcrawls-bulk-title-editor.php';
			if ( file_exists( $editor_file ) ) {
				require_once $editor_file;
				Pubcrawls_Bulk_Title_Editor::init();
			}
		}

		// 3. Quick Edit Featured Image Helper.
		if ( self::is_helper_enabled( 'quick_featured_image', '1' ) ) {
			$qefi_file = PUBCRAWLS_ADDON_DIR . 'includes/helpers/quick-featured-image/class-pubcrawls-quick-featured-image.php';
			if ( file_exists( $qefi_file ) ) {
				require_once $qefi_file;
				Pubcrawls_Quick_Featured_Image::init();
			}
		}

		// 4. Post URL Column Helper.
		if ( self::is_helper_enabled( 'post_url_column', '1' ) ) {
			$url_col_file = PUBCRAWLS_ADDON_DIR . 'includes/helpers/post-url-column/class-pubcrawls-post-url-column.php';
			if ( file_exists( $url_col_file ) ) {
				require_once $url_col_file;
				Pubcrawls_Post_URL_Column::init();
			}
		}

		/**
		 * Action hook allowing third-party or future modules to initialize.
		 */
		do_action( 'pubcrawls_helpers_loaded' );
	}
}
