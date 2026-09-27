<?php
/**
 * PubCrawls FAQ & SEO Bulk Importer
 *
 * Bulk-fill ACF `faqs` repeater + Yoast/RankMath/AIOSEO title & meta description
 * on `city`, `tribe_events`, and custom post types via structured JSON.
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pubcrawls_FAQ_SEO_Importer
 */
class Pubcrawls_FAQ_SEO_Importer {

	/**
	 * Default fallback ACF field keys (from source site post #33216).
	 */
	const DEFAULT_FAQ_REPEATER = 'field_69c3c449c2128';
	const DEFAULT_FAQ_QUESTION = 'field_69c3c45ec2129';
	const DEFAULT_FAQ_ANSWER   = 'field_69c3c469c212a';

	/**
	 * Yoast meta keys.
	 */
	const YOAST_TITLE = '_yoast_wpseo_title';
	const YOAST_DESC  = '_yoast_wpseo_metadesc';

	/**
	 * Batch size limit per request.
	 */
	const BATCH_LIMIT = 25;

	/**
	 * Single instance.
	 *
	 * @var Pubcrawls_FAQ_SEO_Importer|null
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
	 * Initialize importer.
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 30 );
	}

	/**
	 * Register Admin Submenus under PubCrawls & Tools.
	 */
	public function register_admin_menus(): void {
		$supported_post_types = self::get_supported_post_types();

		foreach ( $supported_post_types as $pt_slug => $pt_label ) {
			$page_slug  = $pt_slug . '-faq-import';
			$page_title = sprintf( /* translators: %s: Post type label */ esc_html__( '%s FAQ & SEO Import', 'pubcrawls-addon' ), $pt_label );
			$menu_title = sprintf( /* translators: %s: Post type label */ esc_html__( '%s FAQ/SEO Import', 'pubcrawls-addon' ), $pt_label );

			// 1. PubCrawls submenu page
			add_submenu_page(
				'pubcrawls-addon',
				$page_title,
				$menu_title,
				'manage_options',
				$page_slug,
				function () use ( $pt_slug ) {
					$this->render_page( $pt_slug );
				}
			);

			// 2. Tools menu page (add_management_page for backward compatibility)
			add_management_page(
				$page_title,
				$page_title,
				'manage_options',
				$page_slug,
				function () use ( $pt_slug ) {
					$this->render_page( $pt_slug );
				}
			);
		}
	}

	/**
	 * Get supported post types for FAQ & SEO import.
	 *
	 * @return array Array of slug => Label.
	 */
	public static function get_supported_post_types(): array {
		$types = array();

		// Default supported post types: city and tribe_events
		if ( post_type_exists( 'city' ) ) {
			$types['city'] = esc_html__( 'City', 'pubcrawls-addon' );
		}
		if ( post_type_exists( 'tribe_events' ) ) {
			$types['tribe_events'] = esc_html__( 'Events', 'pubcrawls-addon' );
		}

		// Fallback if neither post type is registered on another site
		if ( empty( $types ) ) {
			$types['city']         = esc_html__( 'City', 'pubcrawls-addon' );
			$types['tribe_events'] = esc_html__( 'Events', 'pubcrawls-addon' );
		}

		/**
		 * Allow developers to register additional post types for FAQ & SEO import.
		 */
		return apply_filters( 'pubcrawls_faq_importer_post_types', $types );
	}

	/**
	 * Dynamically resolve ACF field keys for the `faqs` repeater and subfields.
	 *
	 * Ensures seamless compatibility across different WordPress installations where
	 * ACF field keys might vary from the original site.
	 *
	 * @param string $repeater_name Field name for the FAQs repeater (default 'faqs').
	 * @return array Associative array of field keys ('repeater', 'question', 'answer').
	 */
	public static function get_acf_field_keys( string $repeater_name = 'faqs' ): array {
		$keys = array(
			'repeater' => self::DEFAULT_FAQ_REPEATER,
			'question' => self::DEFAULT_FAQ_QUESTION,
			'answer'   => self::DEFAULT_FAQ_ANSWER,
		);

		// If ACF is loaded, attempt dynamic field lookup by field name
		if ( function_exists( 'acf_get_field' ) ) {
			$repeater_field = acf_get_field( $repeater_name );
			if ( $repeater_field && ! empty( $repeater_field['key'] ) ) {
				$keys['repeater'] = $repeater_field['key'];

				if ( ! empty( $repeater_field['sub_fields'] ) && is_array( $repeater_field['sub_fields'] ) ) {
					foreach ( $repeater_field['sub_fields'] as $sub_field ) {
						if ( isset( $sub_field['name'] ) && 'question' === $sub_field['name'] && ! empty( $sub_field['key'] ) ) {
							$keys['question'] = $sub_field['key'];
						}
						if ( isset( $sub_field['name'] ) && 'answer' === $sub_field['name'] && ! empty( $sub_field['key'] ) ) {
							$keys['answer'] = $sub_field['key'];
						}
					}
				}
			}
		}

		/**
		 * Filter resolved ACF field keys.
		 */
		return apply_filters( 'pubcrawls_faq_importer_acf_keys', $keys, $repeater_name );
	}

	/**
	 * Write FAQs to post (ACF-aware, raw meta fallback).
	 *
	 * @param int   $post_id  The target post ID.
	 * @param array $clean    Sanitized array of ['question' => ..., 'answer' => ...].
	 * @param int   $existing Existing FAQ count.
	 */
	public static function write_faqs( int $post_id, array $clean, int $existing ): void {
		$acf_keys = self::get_acf_field_keys( 'faqs' );

		if ( function_exists( 'update_field' ) ) {
			// First try resolved field key; if false, fallback to field name
			$saved = update_field( $acf_keys['repeater'], $clean, $post_id );
			if ( ! $saved ) {
				update_field( 'faqs', $clean, $post_id );
			}
		} else {
			// Raw postmeta fallback (simulates ACF repeater storage layout)
			$max_rows = max( $existing, count( $clean ) );
			for ( $i = 0; $i < $max_rows; $i++ ) {
				delete_post_meta( $post_id, "faqs_{$i}_question" );
				delete_post_meta( $post_id, "faqs_{$i}_answer" );
				delete_post_meta( $post_id, "_faqs_{$i}_question" );
				delete_post_meta( $post_id, "_faqs_{$i}_answer" );
			}
			foreach ( $clean as $i => $row ) {
				update_post_meta( $post_id, "faqs_{$i}_question", $row['question'] );
				update_post_meta( $post_id, "_faqs_{$i}_question", $acf_keys['question'] );
				update_post_meta( $post_id, "faqs_{$i}_answer",    $row['answer'] );
				update_post_meta( $post_id, "_faqs_{$i}_answer",   $acf_keys['answer'] );
			}
			update_post_meta( $post_id, 'faqs',  count( $clean ) );
			update_post_meta( $post_id, '_faqs', $acf_keys['repeater'] );
		}

		clean_post_cache( $post_id );
	}

	/**
	 * Write SEO fields with multi-SEO plugin compatibility.
	 *
	 * Supports Yoast SEO, Rank Math, All in One SEO, and SEOPress automatically.
	 *
	 * @param int    $post_id   Target post ID.
	 * @param string $title     SEO Title.
	 * @param string $desc      SEO Meta Description.
	 * @param bool   $overwrite Whether to overwrite existing non-empty values.
	 * @return array Array of written fields (e.g. ['title', 'desc']).
	 */
	public static function write_seo( int $post_id, string $title, string $desc, bool $overwrite ): array {
		$written = array();

		// ── SEO Title ──
		if ( '' !== $title ) {
			$sanitized_title = sanitize_text_field( $title );

			// Yoast SEO (Standard)
			$existing_yoast = get_post_meta( $post_id, self::YOAST_TITLE, true );
			if ( '' === $existing_yoast || $overwrite ) {
				update_post_meta( $post_id, self::YOAST_TITLE, $sanitized_title );
				$written[] = 'title';
			}

			// Rank Math compatibility
			if ( defined( 'RANK_MATH_VERSION' ) || metadata_exists( 'post', $post_id, 'rank_math_title' ) ) {
				$existing_rm = get_post_meta( $post_id, 'rank_math_title', true );
				if ( '' === $existing_rm || $overwrite ) {
					update_post_meta( $post_id, 'rank_math_title', $sanitized_title );
				}
			}

			// All in One SEO compatibility
			if ( defined( 'AIOSEO_VERSION' ) || metadata_exists( 'post', $post_id, '_aioseo_title' ) ) {
				$existing_aio = get_post_meta( $post_id, '_aioseo_title', true );
				if ( '' === $existing_aio || $overwrite ) {
					update_post_meta( $post_id, '_aioseo_title', $sanitized_title );
				}
			}

			// SEOPress compatibility
			if ( defined( 'SEOPRESS_VERSION' ) || metadata_exists( 'post', $post_id, '_seopress_titles_title' ) ) {
				$existing_sp = get_post_meta( $post_id, '_seopress_titles_title', true );
				if ( '' === $existing_sp || $overwrite ) {
					update_post_meta( $post_id, '_seopress_titles_title', $sanitized_title );
				}
			}
		}

		// ── SEO Meta Description ──
		if ( '' !== $desc ) {
			$sanitized_desc = sanitize_textarea_field( $desc );

			// Yoast SEO (Standard)
			$existing_yoast_desc = get_post_meta( $post_id, self::YOAST_DESC, true );
			if ( '' === $existing_yoast_desc || $overwrite ) {
				update_post_meta( $post_id, self::YOAST_DESC, $sanitized_desc );
				$written[] = 'desc';
			}

			// Rank Math compatibility
			if ( defined( 'RANK_MATH_VERSION' ) || metadata_exists( 'post', $post_id, 'rank_math_description' ) ) {
				$existing_rm_desc = get_post_meta( $post_id, 'rank_math_description', true );
				if ( '' === $existing_rm_desc || $overwrite ) {
					update_post_meta( $post_id, 'rank_math_description', $sanitized_desc );
				}
			}

			// All in One SEO compatibility
			if ( defined( 'AIOSEO_VERSION' ) || metadata_exists( 'post', $post_id, '_aioseo_description' ) ) {
				$existing_aio_desc = get_post_meta( $post_id, '_aioseo_description', true );
				if ( '' === $existing_aio_desc || $overwrite ) {
					update_post_meta( $post_id, '_aioseo_description', $sanitized_desc );
				}
			}

			// SEOPress compatibility
			if ( defined( 'SEOPRESS_VERSION' ) || metadata_exists( 'post', $post_id, '_seopress_titles_desc' ) ) {
				$existing_sp_desc = get_post_meta( $post_id, '_seopress_titles_desc', true );
				if ( '' === $existing_sp_desc || $overwrite ) {
					update_post_meta( $post_id, '_seopress_titles_desc', $sanitized_desc );
				}
			}
		}

		return array_unique( $written );
	}

	/**
	 * Resolve slug or numeric ID → WP_Post object.
	 *
	 * @param string $key Post ID or slug.
	 * @param string $post_type Target post type.
	 * @return WP_Post|null
	 */
	public static function resolve_post( string $key, string $post_type ): ?WP_Post {
		if ( is_numeric( $key ) ) {
			$post = get_post( (int) $key );
			return ( $post && $post->post_type === $post_type ) ? $post : null;
		}

		return get_page_by_path( $key, OBJECT, $post_type ) ?: null;
	}

	/**
	 * Detect what fields are present in the JSON payload.
	 *
	 * @param array $data Decoded JSON data array.
	 * @return array Array with ['has_faqs' => bool, 'has_seo' => bool].
	 */
	public static function detect_mode( array $data ): array {
		$has_faqs = false;
		$has_seo  = false;

		foreach ( array_slice( $data, 0, 5, true ) as $rows ) {
			if ( isset( $rows['faqs'] ) ) {
				$has_faqs = true;
			}
			if ( isset( $rows['seo_title'] ) || isset( $rows['seo_desc'] ) ) {
				$has_seo = true;
			}
			if ( isset( $rows[0]['question'] ) ) {
				$has_faqs = true;
			}
		}

		return compact( 'has_faqs', 'has_seo' );
	}

	/**
	 * Calculate post, SEO, and FAQ counts via direct wpdb query.
	 *
	 * @param string $post_type The post type to analyze.
	 * @return array ['total' => int, 'with_seo' => int, 'with_faqs' => int].
	 */
	public static function get_stats( string $post_type ): array {
		global $wpdb;

		// Total published + future + draft + private posts
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				 FROM {$wpdb->posts}
				 WHERE post_type = %s
				   AND post_status NOT IN ('trash','auto-draft','inherit')",
				$post_type
			)
		);

		// Posts that have a non-empty Yoast or Rank Math title meta
		$with_seo = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm
				     ON pm.post_id = p.ID
				    AND (pm.meta_key = %s OR pm.meta_key = 'rank_math_title' OR pm.meta_key = '_aioseo_title')
				    AND pm.meta_value != ''
				 WHERE p.post_type = %s
				   AND p.post_status NOT IN ('trash','auto-draft','inherit')",
				self::YOAST_TITLE,
				$post_type
			)
		);

		// Posts that have a non-zero faqs count meta row
		$with_faqs = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm
				     ON pm.post_id = p.ID
				    AND pm.meta_key = 'faqs'
				    AND pm.meta_value > 0
				 WHERE p.post_type = %s
				   AND p.post_status NOT IN ('trash','auto-draft','inherit')",
				$post_type
			)
		);

		return compact( 'total', 'with_seo', 'with_faqs' );
	}

	/**
	 * Render the import admin page.
	 *
	 * @param string $post_type Target post type.
	 */
	public function render_page( string $post_type ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'pubcrawls-addon' ) );
		}

		$view_file = PUBCRAWLS_ADDON_DIR . 'includes/helpers/faq-seo-importer/views/importer-page.php';
		if ( file_exists( $view_file ) ) {
			require $view_file;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Importer view template not found.', 'pubcrawls-addon' ) . '</p></div>';
		}
	}
}
