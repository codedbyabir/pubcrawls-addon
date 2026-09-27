<?php
/**
 * PubCrawls Post URL Column Helper
 *
 * Adds a clickable permalink URL column with 1-click clipboard copy
 * to WordPress admin post list tables, with per-post-type configuration.
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pubcrawls_Post_URL_Column
 */
class Pubcrawls_Post_URL_Column {

	/**
	 * Single instance.
	 *
	 * @var Pubcrawls_Post_URL_Column|null
	 */
	private static $instance = null;

	/**
	 * Default post types enabled for URL column.
	 *
	 * @var array
	 */
	public static $default_post_types = array(
		'tribe_events',
		'city',
		'venue',
		'post',
		'page',
	);

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
	 * Initialize helper feature.
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'register_column_hooks' ) );
		add_action( 'admin_head-edit.php', array( $this, 'render_admin_styles' ) );
	}

	/**
	 * Get all content post types available for URL column registration.
	 *
	 * @return array Associative array of [ slug => label ].
	 */
	public static function get_available_post_types(): array {
		$types    = array();
		$raw_types = get_post_types( array( 'public' => true ), 'objects' );

		// System post types to exclude
		$excluded = array(
			'attachment',
			'elementor_library',
			'e-floating-buttons',
			'revision',
			'nav_menu_item',
			'custom_css',
			'customize_changeset',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation',
		);

		foreach ( $raw_types as $slug => $obj ) {
			if ( in_array( $slug, $excluded, true ) ) {
				continue;
			}
			$types[ $slug ] = ! empty( $obj->labels->name ) ? $obj->labels->name : ucfirst( $slug );
		}

		// Ensure known theme/plugin post types are included if registered
		$known = array(
			'tribe_events' => esc_html__( 'Events', 'pubcrawls-addon' ),
			'city'         => esc_html__( 'Cities', 'pubcrawls-addon' ),
			'venue'        => esc_html__( 'Venues', 'pubcrawls-addon' ),
			'faq'          => esc_html__( 'FAQs', 'pubcrawls-addon' ),
			'press'        => esc_html__( 'Press', 'pubcrawls-addon' ),
			'city_map'     => esc_html__( 'City Maps', 'pubcrawls-addon' ),
			'post'         => esc_html__( 'Posts', 'pubcrawls-addon' ),
			'page'         => esc_html__( 'Pages', 'pubcrawls-addon' ),
		);

		foreach ( $known as $slug => $label ) {
			if ( ( post_type_exists( $slug ) || 'page' === $slug || 'post' === $slug ) && ! isset( $types[ $slug ] ) ) {
				$types[ $slug ] = $label;
			}
		}

		/**
		 * Filter available post types for URL column.
		 *
		 * @param array $types Associative array of post type slug => label.
		 */
		return apply_filters( 'pubcrawls_post_url_column_available_post_types', $types );
	}

	/**
	 * Get the currently enabled post types from plugin settings.
	 *
	 * @return array List of enabled post type slugs.
	 */
	public static function get_enabled_post_types(): array {
		$enabled = null;

		if ( class_exists( 'Pubcrawls_Admin' ) ) {
			$enabled = Pubcrawls_Admin::get_setting( 'post_url_column_post_types', self::$default_post_types );
		}

		if ( ! is_array( $enabled ) ) {
			$enabled = self::$default_post_types;
		}

		/**
		 * Filter enabled post types for URL column.
		 *
		 * @param array $enabled List of enabled post type slugs.
		 */
		return apply_filters( 'pubcrawls_post_url_column_post_types', array_unique( $enabled ) );
	}

	/**
	 * Register columns and custom column renders for all enabled post types.
	 */
	public function register_column_hooks(): void {
		$enabled_types = self::get_enabled_post_types();

		foreach ( $enabled_types as $pt ) {
			if ( 'page' === $pt ) {
				add_filter( 'manage_pages_columns', array( $this, 'add_url_column' ) );
				add_action( 'manage_pages_custom_column', array( $this, 'render_url_column' ), 10, 2 );

				add_filter( 'manage_page_posts_columns', array( $this, 'add_url_column' ) );
				add_action( 'manage_page_posts_custom_column', array( $this, 'render_url_column' ), 10, 2 );
			} else {
				add_filter( "manage_{$pt}_posts_columns", array( $this, 'add_url_column' ) );
				add_action( "manage_{$pt}_posts_custom_column", array( $this, 'render_url_column' ), 10, 2 );
			}
		}
	}

	/**
	 * Insert the URL column right before the Date column, or at the end.
	 *
	 * @param array $columns Existing table columns.
	 * @return array Modified columns.
	 */
	public function add_url_column( array $columns ): array {
		$new_columns = array();
		$inserted    = false;

		foreach ( $columns as $key => $title ) {
			if ( 'date' === $key ) {
				$new_columns['post_url'] = esc_html__( 'URL', 'pubcrawls-addon' );
				$inserted                = true;
			}
			$new_columns[ $key ] = $title;
		}

		if ( ! $inserted ) {
			$new_columns['post_url'] = esc_html__( 'URL', 'pubcrawls-addon' );
		}

		return $new_columns;
	}

	/**
	 * Render the permalink URL inside the column.
	 *
	 * @param string $column_name Current column name.
	 * @param int    $post_id     Current post ID.
	 */
	public function render_url_column( string $column_name, int $post_id ): void {
		if ( 'post_url' !== $column_name ) {
			return;
		}

		$url = get_permalink( $post_id );

		if ( empty( $url ) ) {
			echo '<span class="pubcrawls-url-empty" style="color:#94a3b8;">—</span>';
			return;
		}

		echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="pubcrawls-url-link">';
		echo esc_html( $url );
		echo '</a>';
	}

	/**
	 * Render column styles on post list edit screens.
	 */
	public function render_admin_styles(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::get_enabled_post_types(), true ) ) {
			return;
		}
		?>
		<style>
			.manage-column.column-post_url {
				width: 25%;
				min-width: 200px;
			}
			.pubcrawls-url-link {
				color: #2271b1;
				text-decoration: underline;
				word-break: break-all;
				font-size: 13px;
				line-height: 1.4;
				display: inline-block;
				transition: color 0.15s ease;
			}
			.pubcrawls-url-link:hover {
				color: #135e96;
			}
		</style>
		<?php
	}
}
