<?php

/**
 * PubCrawls Admin Dashboard & Settings
 *
 * Manages admin menus, settings, widget management, post types, taxonomies, and system health.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Class Pubcrawls_Admin
 *
 * Handles admin functionality, settings, post types, and widget configurations.
 */
class Pubcrawls_Admin
{

	/**
	 * Option keys.
	 */
	const SETTINGS_KEY   = 'pubcrawls_settings';
	const WIDGETS_KEY    = 'pubcrawls_disabled_widgets';
	const POST_TYPES_KEY = 'pubcrawls_disabled_post_types';
	const TAXONOMIES_KEY = 'pubcrawls_disabled_taxonomies';
	const SLUGS_KEY      = 'pubcrawls_slug_overrides';
	const FLUSH_FLAG     = 'pubcrawls_flush_rewrite';

	/**
	 * Default URL (rewrite) slugs exactly as registered in post-types.php / taxonomies.php.
	 * These are what users get when no override is saved.
	 */
	const DEFAULT_SLUGS = array(
		'post_types' => array(
			'city'         => 'city',
			'faq'          => 'faq',
			'press'        => 'press',
			'venue'        => 'venues',
			'tribe_events' => 'tribe_events',
			'city_map'     => 'maps',
		),
		'taxonomies' => array(
			'venue_category'   => 'venue-category',
			'tribe_events_cat' => 'events',
		),
	);

	/**
	 * Slugs WordPress itself (or common conventions) already uses for URLs.
	 */
	const RESERVED_SLUGS = array(
		'post',
		'posts',
		'page',
		'pages',
		'attachment',
		'attachments',
		'revision',
		'nav_menu_item',
		'wp-admin',
		'wp-content',
		'wp-includes',
		'wp-json',
		'wp-login',
		'wp-login.php',
		'admin',
		'login',
		'feed',
		'feeds',
		'rss',
		'rss2',
		'atom',
		'embed',
		'trackback',
		'comments',
		'comment-page',
		'category',
		'categories',
		'tag',
		'tags',
		'author',
		'authors',
		'search',
		'type',
		'sitemap',
		'robots.txt',
	);

	/**
	 * Instance.
	 *
	 * @var Pubcrawls_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct()
	{
		add_action('admin_menu', array($this, 'register_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

		// Slug / enable-disable changes alter rewrite rules: flush once, after types are re-registered.
		foreach (array(self::SLUGS_KEY, self::POST_TYPES_KEY, self::TAXONOMIES_KEY) as $option) {
			add_action('update_option_' . $option, array($this, 'flag_rewrite_flush'));
			add_action('add_option_' . $option, array($this, 'flag_rewrite_flush'));
		}
		add_action('init', array($this, 'maybe_flush_rewrites'), 999);
	}

	/**
	 * Mark rewrite rules as stale.
	 */
	public function flag_rewrite_flush(): void
	{
		update_option(self::FLUSH_FLAG, 1, false);
	}

	/**
	 * Flush rewrite rules once, after all post types/taxonomies registered with the new slugs.
	 */
	public function maybe_flush_rewrites(): void
	{
		if (get_option(self::FLUSH_FLAG)) {
			delete_option(self::FLUSH_FLAG);
			flush_rewrite_rules(false);
		}
	}

	/**
	 * Default (source-code) slug for a post type or taxonomy.
	 *
	 * @param string $kind 'post_types' or 'taxonomies'.
	 * @param string $key  Post type / taxonomy key.
	 */
	public static function get_default_slug(string $kind, string $key): string
	{
		return self::DEFAULT_SLUGS[$kind][$key] ?? $key;
	}

	/**
	 * Effective slug: saved override, otherwise the source-code default.
	 *
	 * @param string $kind 'post_types' or 'taxonomies'.
	 * @param string $key  Post type / taxonomy key.
	 */
	public static function get_slug(string $kind, string $key): string
	{
		$overrides = get_option(self::SLUGS_KEY, array());
		$slug      = (is_array($overrides) && isset($overrides[$kind][$key]) && is_string($overrides[$kind][$key]))
			? sanitize_title($overrides[$kind][$key])
			: '';
		return '' !== $slug ? $slug : self::get_default_slug($kind, $key);
	}

	/**
	 * Initialize admin module.
	 */
	public static function init(): void
	{
		self::get_instance();
	}

	/**
	 * Register Admin Menus.
	 */
	public function register_menu(): void
	{
		$disabled_pt = get_option(self::POST_TYPES_KEY, array());
		if (! is_array($disabled_pt)) {
			$disabled_pt = array();
		}

		// Main Menu.
		add_menu_page(
			esc_html__('PubCrawls Dashboard', 'pubcrawls-addon'),
			esc_html__('PubCrawls', 'pubcrawls-addon'),
			'manage_options',
			'pubcrawls-addon',
			array($this, 'render_dashboard'),
			'dashicons-tickets-alt',
			30
		);

		// Submenu: Dashboard.
		add_submenu_page(
			'pubcrawls-addon',
			esc_html__('Dashboard & Settings', 'pubcrawls-addon'),
			esc_html__('Dashboard', 'pubcrawls-addon'),
			'manage_options',
			'pubcrawls-addon',
			array($this, 'render_dashboard')
		);

		// Submenu: Cities shortcut (if active).
		if (! in_array('city', $disabled_pt, true)) {
			add_submenu_page(
				'pubcrawls-addon',
				esc_html__('All Cities', 'pubcrawls-addon'),
				esc_html__('Cities', 'pubcrawls-addon'),
				'manage_options',
				'edit.php?post_type=city'
			);
		}

		// Submenu: Events shortcut (if active).
		if (! in_array('tribe_events', $disabled_pt, true)) {
			add_submenu_page(
				'pubcrawls-addon',
				esc_html__('All Events', 'pubcrawls-addon'),
				esc_html__('Events', 'pubcrawls-addon'),
				'manage_options',
				'edit.php?post_type=tribe_events'
			);
		}

		// Submenu: FAQs shortcut (if active).
		if (! in_array('faq', $disabled_pt, true)) {
			add_submenu_page(
				'pubcrawls-addon',
				esc_html__('All FAQs', 'pubcrawls-addon'),
				esc_html__('FAQs', 'pubcrawls-addon'),
				'manage_options',
				'edit.php?post_type=faq'
			);
		}
	}

	/**
	 * Register Plugin Settings.
	 */
	public function register_settings(): void
	{
		register_setting(
			'pubcrawls_settings_group',
			self::SETTINGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize_settings'),
				'default'           => self::get_default_settings(),
			)
		);

		register_setting(
			'pubcrawls_settings_group',
			self::WIDGETS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize_disabled_widgets'),
				'default'           => array(),
			)
		);

		register_setting(
			'pubcrawls_settings_group',
			self::POST_TYPES_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize_disabled_post_types'),
				'default'           => array(),
			)
		);

		register_setting(
			'pubcrawls_settings_group',
			self::TAXONOMIES_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize_disabled_taxonomies'),
				'default'           => array(),
			)
		);

		register_setting(
			'pubcrawls_settings_group',
			self::SLUGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize_slug_overrides'),
				'default'           => array(),
			)
		);
	}

	/**
	 * Get default settings.
	 */
	public static function get_default_settings(): array
	{
		return array(
			'default_ticket_provider'     => 'squadup',
			'squadup_user_id'             => '3438001',
			'squadup_modal_image'         => 'https://pubcrawls.com/wp-content/uploads/2026/03/Logo.webp',
			'squadup_modal_title'         => 'Pubcrawls Events',
			'squadup_confirmation_url'    => 'https://pubcrawls.com/purchase-confirmation/',
			'squadup_sms_question'        => 'Phone Number for SMS Updates related to your event(s).',
			'squadup_enable_sms'          => '1',
			'eventbrite_confirmation_url' => 'https://pubcrawls.com/purchase-confirmation/',
			'eventbrite_open_fallback'    => '1',
			'enable_faq_seo_importer'     => '1',
			'enable_bulk_title_editor'    => '1',
			'enable_quick_featured_image' => '1',
			'enable_post_url_column'      => '1',
			'post_url_column_post_types'  => array('tribe_events', 'city', 'venue', 'post', 'page'),
			// Tracking & Webhook settings
			'eb_api_token'                => '',
			'eb_webhook_secret'           => '',
			'eb_organization_id'          => '',
			'enable_server_tracking'      => '1',
			'enable_ga4_tracking'         => '1',
			'enable_meta_tracking'        => '1',
			'enable_tiktok_tracking'      => '1',
			'ga4_measurement_id'          => '',
			'ga4_api_secret'              => '',
			'ga4_debug_mode'              => '0',
			'meta_pixel_id'               => '',
			'meta_capi_token'             => '',
			'meta_test_code'              => '',
			'tiktok_pixel_id'             => '',
			'tiktok_access_token'         => '',
			'tiktok_test_code'            => '',
			'enable_openai_tracking'      => '1',
			'openai_pixel_id'             => 'JoAtsPLnsm5Jfs1GnaGpxo',
			'openai_capi_key'             => '',
			'openai_validate_only'        => '0',
			// Schema.org Structured Data settings
			'enable_event_schema'         => '1',
			'schema_organizer_name'       => 'PubCrawls',
			'schema_organizer_url'        => 'https://pubcrawls.com/',
			'schema_organizer_logo'       => 'https://pubcrawls.com/wp-content/uploads/2026/03/Logo.webp',
			'schema_currency'             => 'USD',
			'schema_default_price'        => '',
			'schema_default_start_time'   => '4:00 PM',
			'schema_country_code'         => 'US',
			'schema_event_status'         => 'https://schema.org/EventScheduled',
			'schema_attendance_mode'      => 'https://schema.org/OfflineEventAttendanceMode',
			'schema_date_suffix_format'   => 'M j, Y',
			'enable_faq_schema_global'    => '1',
		);
	}

	/**
	 * Get a specific plugin setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default_value Fallback value.
	 * @return mixed
	 */
	public static function get_setting(string $key, $default_value = null)
	{
		if ('eb_webhook_secret' === $key) {
			$dedicated = get_option('pubcrawls_eb_webhook_secret', '');
			if (! empty($dedicated)) {
				return $dedicated;
			}
		}

		$settings = get_option(self::SETTINGS_KEY, self::get_default_settings());
		if (isset($settings[$key]) && '' !== $settings[$key]) {
			return $settings[$key];
		}

		$defaults = self::get_default_settings();
		return isset($defaults[$key]) ? $defaults[$key] : $default_value;
	}

	/**
	 * Mask a secret string for safe display in the admin interface.
	 *
	 * @param string $secret Secret string.
	 * @param int    $visible_trailing Number of trailing characters to show.
	 * @return string Masked string.
	 */
	public static function mask_secret(string $secret, int $visible_trailing = 4): string
	{
		$secret = trim($secret);
		if (empty($secret)) {
			return '';
		}

		$len = strlen($secret);
		if ($len <= $visible_trailing) {
			return str_repeat('•', $len);
		}

		return str_repeat('•', 8) . substr($secret, -$visible_trailing);
	}

	/**
	 * Get status indicators for all tracking platforms.
	 *
	 * @return array Platform configuration statuses.
	 */
	public static function get_platform_statuses(): array
	{
		$eb_const = defined('PUBCRAWLS_EB_API_TOKEN') && ! empty(PUBCRAWLS_EB_API_TOKEN);
		$eb_token = $eb_const ? PUBCRAWLS_EB_API_TOKEN : self::get_setting('eb_api_token', '');

		$ga4_mid_const = defined('PUBCRAWLS_GA4_MEASUREMENT_ID') && ! empty(PUBCRAWLS_GA4_MEASUREMENT_ID);
		$ga4_mid       = $ga4_mid_const ? PUBCRAWLS_GA4_MEASUREMENT_ID : self::get_setting('ga4_measurement_id', '');
		$ga4_sec_const = defined('PUBCRAWLS_GA4_API_SECRET') && ! empty(PUBCRAWLS_GA4_API_SECRET);
		$ga4_sec       = $ga4_sec_const ? PUBCRAWLS_GA4_API_SECRET : self::get_setting('ga4_api_secret', '');

		$meta_pix_const = defined('PUBCRAWLS_META_PIXEL_ID') && ! empty(PUBCRAWLS_META_PIXEL_ID);
		$meta_pix       = $meta_pix_const ? PUBCRAWLS_META_PIXEL_ID : self::get_setting('meta_pixel_id', '');
		$meta_tok_const = defined('PUBCRAWLS_META_CAPI_TOKEN') && ! empty(PUBCRAWLS_META_CAPI_TOKEN);
		$meta_tok       = $meta_tok_const ? PUBCRAWLS_META_CAPI_TOKEN : self::get_setting('meta_capi_token', '');

		$tt_pix_const = defined('PUBCRAWLS_TIKTOK_PIXEL_ID') && ! empty(PUBCRAWLS_TIKTOK_PIXEL_ID);
		$tt_pix       = $tt_pix_const ? PUBCRAWLS_TIKTOK_PIXEL_ID : self::get_setting('tiktok_pixel_id', '');
		$tt_tok_const = defined('PUBCRAWLS_TIKTOK_ACCESS_TOKEN') && ! empty(PUBCRAWLS_TIKTOK_ACCESS_TOKEN);
		$tt_tok       = $tt_tok_const ? PUBCRAWLS_TIKTOK_ACCESS_TOKEN : self::get_setting('tiktok_access_token', '');

		$oai_key_const = ( ( defined('PC_OPENAI_CAPI_KEY') && ! empty(PC_OPENAI_CAPI_KEY) ) || ( defined('PUBCRAWLS_OPENAI_CAPI_KEY') && ! empty(PUBCRAWLS_OPENAI_CAPI_KEY) ) );
		$oai_key       = $oai_key_const ? ( defined('PC_OPENAI_CAPI_KEY') ? PC_OPENAI_CAPI_KEY : PUBCRAWLS_OPENAI_CAPI_KEY ) : self::get_setting('openai_capi_key', '');
		$oai_pix_const = ( ( defined('PC_OPENAI_PIXEL_ID') && ! empty(PC_OPENAI_PIXEL_ID) ) || ( defined('PUBCRAWLS_OPENAI_PIXEL_ID') && ! empty(PUBCRAWLS_OPENAI_PIXEL_ID) ) );
		$oai_pix       = $oai_pix_const ? ( defined('PC_OPENAI_PIXEL_ID') ? PC_OPENAI_PIXEL_ID : PUBCRAWLS_OPENAI_PIXEL_ID ) : self::get_setting('openai_pixel_id', 'JoAtsPLnsm5Jfs1GnaGpxo');

		return array(
			'eventbrite' => array(
				'label'      => 'Eventbrite API',
				'configured' => ! empty($eb_token),
				'is_const'   => $eb_const,
			),
			'ga4'        => array(
				'label'      => 'GA4 Measurement Protocol',
				'configured' => (! empty($ga4_mid) && ! empty($ga4_sec)),
				'is_const'   => ($ga4_mid_const || $ga4_sec_const),
			),
			'meta'       => array(
				'label'      => 'Meta CAPI',
				'configured' => (! empty($meta_pix) && ! empty($meta_tok)),
				'is_const'   => ($meta_pix_const || $meta_tok_const),
			),
			'tiktok'     => array(
				'label'      => 'TikTok Events API',
				'configured' => (! empty($tt_pix) && ! empty($tt_tok)),
				'is_const'   => ($tt_pix_const || $tt_tok_const),
			),
			'openai'     => array(
				'label'      => 'OpenAI Conversions API',
				'configured' => (! empty($oai_pix) && ! empty($oai_key)),
				'is_const'   => ($oai_key_const || $oai_pix_const),
			),
		);
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * @param array $input Raw input data from settings form.
	 * @return array
	 */
	public function sanitize_settings($input): array
	{
		// A request that did not include these settings must never reset them to defaults.
		if (! is_array($input)) {
			$stored = get_option(self::SETTINGS_KEY, self::get_default_settings());
			return is_array($stored) ? $stored : self::get_default_settings();
		}

		$existing = get_option(self::SETTINGS_KEY, self::get_default_settings());
		$defaults = self::get_default_settings();
		$clean    = is_array($existing) ? array_merge($defaults, $existing) : $defaults;

		// Ticket provider settings
		if (isset($input['default_ticket_provider'])) {
			$clean['default_ticket_provider'] = ('eventbrite' === $input['default_ticket_provider']) ? 'eventbrite' : 'squadup';
		}

		if (isset($input['squadup_user_id'])) {
			$clean['squadup_user_id'] = sanitize_text_field(trim((string) $input['squadup_user_id']));
		}

		if (isset($input['squadup_modal_image'])) {
			$clean['squadup_modal_image'] = ! empty($input['squadup_modal_image'])
				? esc_url_raw(trim((string) $input['squadup_modal_image']))
				: $defaults['squadup_modal_image'];
		}

		if (isset($input['squadup_modal_title'])) {
			$clean['squadup_modal_title'] = ! empty($input['squadup_modal_title'])
				? sanitize_text_field($input['squadup_modal_title'])
				: $defaults['squadup_modal_title'];
		}

		if (isset($input['squadup_confirmation_url'])) {
			$clean['squadup_confirmation_url'] = ! empty($input['squadup_confirmation_url'])
				? esc_url_raw($input['squadup_confirmation_url'])
				: $defaults['squadup_confirmation_url'];
		}

		if (isset($input['squadup_sms_question'])) {
			$clean['squadup_sms_question'] = ! empty($input['squadup_sms_question'])
				? sanitize_text_field($input['squadup_sms_question'])
				: $defaults['squadup_sms_question'];
		}

		if (isset($input['ticket_settings_submitted']) || isset($input['default_ticket_provider'])) {
			$clean['squadup_enable_sms']       = ! empty($input['squadup_enable_sms']) ? '1' : '0';
			$clean['eventbrite_open_fallback'] = ! empty($input['eventbrite_open_fallback']) ? '1' : '0';
		}

		if (isset($input['eventbrite_confirmation_url'])) {
			$clean['eventbrite_confirmation_url'] = ! empty($input['eventbrite_confirmation_url'])
				? esc_url_raw($input['eventbrite_confirmation_url'])
				: $defaults['eventbrite_confirmation_url'];
		}

		// Tracking & Webhook settings
		if (isset($input['tracking_settings_submitted'])) {
			$clean['enable_server_tracking'] = ! empty($input['enable_server_tracking']) ? '1' : '0';
			$clean['enable_ga4_tracking']    = ! empty($input['enable_ga4_tracking']) ? '1' : '0';
			$clean['enable_meta_tracking']   = ! empty($input['enable_meta_tracking']) ? '1' : '0';
			$clean['enable_tiktok_tracking'] = ! empty($input['enable_tiktok_tracking']) ? '1' : '0';
			$clean['enable_openai_tracking'] = ! empty($input['enable_openai_tracking']) ? '1' : '0';

			$submitted_secret = isset($input['eb_webhook_secret']) ? sanitize_text_field(trim($input['eb_webhook_secret'])) : '';
			if (! empty($submitted_secret) && false === strpos($submitted_secret, '••••')) {
				$clean['eb_webhook_secret'] = $submitted_secret;
				update_option('pubcrawls_eb_webhook_secret', $submitted_secret, false);
			} elseif (! empty($existing['eb_webhook_secret'])) {
				$clean['eb_webhook_secret'] = $existing['eb_webhook_secret'];
				update_option('pubcrawls_eb_webhook_secret', $existing['eb_webhook_secret'], false);
			} else {
				$dedicated = get_option('pubcrawls_eb_webhook_secret', '');
				if (! empty($dedicated)) {
					$clean['eb_webhook_secret'] = $dedicated;
				} else {
					$new_sec = wp_generate_password(32, false, false);
					$clean['eb_webhook_secret'] = $new_sec;
					update_option('pubcrawls_eb_webhook_secret', $new_sec, false);
				}
			}

			$clean['eb_organization_id']   = isset($input['eb_organization_id']) ? sanitize_text_field(trim($input['eb_organization_id'])) : '';
			$clean['ga4_measurement_id']   = isset($input['ga4_measurement_id']) ? sanitize_text_field(trim($input['ga4_measurement_id'])) : '';
			$clean['ga4_debug_mode']       = ! empty($input['ga4_debug_mode']) ? '1' : '0';
			$clean['meta_pixel_id']        = isset($input['meta_pixel_id']) ? sanitize_text_field(trim($input['meta_pixel_id'])) : '';
			$clean['meta_test_code']       = isset($input['meta_test_code']) ? sanitize_text_field(trim($input['meta_test_code'])) : '';
			$clean['tiktok_pixel_id']      = isset($input['tiktok_pixel_id']) ? sanitize_text_field(trim($input['tiktok_pixel_id'])) : '';
			$clean['tiktok_test_code']     = isset($input['tiktok_test_code']) ? sanitize_text_field(trim($input['tiktok_test_code'])) : '';
			$clean['openai_pixel_id']      = isset($input['openai_pixel_id']) ? sanitize_text_field(trim($input['openai_pixel_id'])) : 'JoAtsPLnsm5Jfs1GnaGpxo';
			$clean['openai_validate_only'] = ! empty($input['openai_validate_only']) ? '1' : '0';

			// Secret preservation: Do not overwrite with empty or masked string if existing secret exists
			$secret_keys = array('eb_api_token', 'ga4_api_secret', 'meta_capi_token', 'tiktok_access_token', 'openai_capi_key');
			foreach ($secret_keys as $sk) {
				if (isset($input[$sk])) {
					$val = trim((string) $input[$sk]);
					if (empty($val) || false !== strpos($val, '••••')) {
						$clean[$sk] = $existing[$sk] ?? '';
					} else {
						$clean[$sk] = sanitize_text_field($val);
					}
				}
			}
		}

		// Helper & Tool toggles
		if (isset($input['helpers_settings_submitted'])) {
			$clean['enable_faq_seo_importer']     = ! empty($input['enable_faq_seo_importer']) ? '1' : '0';
			$clean['enable_bulk_title_editor']    = ! empty($input['enable_bulk_title_editor']) ? '1' : '0';
			$clean['enable_quick_featured_image'] = ! empty($input['enable_quick_featured_image']) ? '1' : '0';
			$clean['enable_post_url_column']      = ! empty($input['enable_post_url_column']) ? '1' : '0';

			// Post types enabled for URL column
			if (! empty($input['post_url_column_post_types']) && is_array($input['post_url_column_post_types'])) {
				$clean['post_url_column_post_types'] = array_values(array_map('sanitize_key', $input['post_url_column_post_types']));
			} else {
				$clean['post_url_column_post_types'] = array();
			}
		} else {
			if (isset($input['enable_faq_seo_importer'])) {
				$clean['enable_faq_seo_importer'] = ! empty($input['enable_faq_seo_importer']) ? '1' : '0';
			}
			if (isset($input['enable_bulk_title_editor'])) {
				$clean['enable_bulk_title_editor'] = ! empty($input['enable_bulk_title_editor']) ? '1' : '0';
			}
			if (isset($input['enable_quick_featured_image'])) {
				$clean['enable_quick_featured_image'] = ! empty($input['enable_quick_featured_image']) ? '1' : '0';
			}
			if (isset($input['enable_post_url_column'])) {
				$clean['enable_post_url_column'] = ! empty($input['enable_post_url_column']) ? '1' : '0';
			}
			if (isset($input['post_url_column_post_types']) && is_array($input['post_url_column_post_types'])) {
				$clean['post_url_column_post_types'] = array_values(array_map('sanitize_key', $input['post_url_column_post_types']));
			}
		}

		// Schema & SEO settings
		if (isset($input['schema_settings_submitted'])) {
			$clean['enable_event_schema']       = ! empty($input['enable_event_schema']) ? '1' : '0';
			$clean['schema_organizer_name']     = isset($input['schema_organizer_name']) ? sanitize_text_field($input['schema_organizer_name']) : '';
			$clean['schema_organizer_url']      = isset($input['schema_organizer_url']) ? esc_url_raw($input['schema_organizer_url']) : '';
			$clean['schema_organizer_logo']     = isset($input['schema_organizer_logo']) ? esc_url_raw($input['schema_organizer_logo']) : '';
			$clean['schema_currency']           = ! empty($input['schema_currency']) ? strtoupper(sanitize_text_field($input['schema_currency'])) : 'USD';
			$clean['schema_default_price']      = isset($input['schema_default_price']) && '' !== trim((string) $input['schema_default_price']) ? sanitize_text_field(trim((string) $input['schema_default_price'])) : '';
			$clean['schema_default_start_time'] = isset($input['schema_default_start_time']) && '' !== trim((string) $input['schema_default_start_time']) ? sanitize_text_field(trim((string) $input['schema_default_start_time'])) : '4:00 PM';
			$clean['schema_country_code']       = ! empty($input['schema_country_code']) ? strtoupper(sanitize_text_field(trim((string) $input['schema_country_code']))) : 'US';
			$clean['schema_event_status']       = ! empty($input['schema_event_status']) ? esc_url_raw($input['schema_event_status']) : 'https://schema.org/EventScheduled';
			$clean['schema_attendance_mode']    = ! empty($input['schema_attendance_mode']) ? esc_url_raw($input['schema_attendance_mode']) : 'https://schema.org/OfflineEventAttendanceMode';
			$clean['schema_date_suffix_format'] = isset($input['schema_date_suffix_format']) ? sanitize_text_field($input['schema_date_suffix_format']) : 'M j, Y';
			$clean['enable_faq_schema_global']  = ! empty($input['enable_faq_schema_global']) ? '1' : '0';
		} else {
			if (isset($input['enable_event_schema'])) {
				$clean['enable_event_schema'] = ! empty($input['enable_event_schema']) ? '1' : '0';
			}
			if (isset($input['schema_organizer_name'])) {
				$clean['schema_organizer_name'] = sanitize_text_field($input['schema_organizer_name']);
			}
			if (isset($input['schema_organizer_url'])) {
				$clean['schema_organizer_url'] = esc_url_raw($input['schema_organizer_url']);
			}
			if (isset($input['schema_organizer_logo'])) {
				$clean['schema_organizer_logo'] = esc_url_raw($input['schema_organizer_logo']);
			}
			if (isset($input['schema_currency'])) {
				$clean['schema_currency'] = strtoupper(sanitize_text_field($input['schema_currency']));
			}
			if (isset($input['schema_default_price'])) {
				$clean['schema_default_price'] = sanitize_text_field(trim((string) $input['schema_default_price']));
			}
			if (isset($input['schema_default_start_time'])) {
				$clean['schema_default_start_time'] = sanitize_text_field(trim((string) $input['schema_default_start_time']));
			}
			if (isset($input['schema_country_code'])) {
				$clean['schema_country_code'] = strtoupper(sanitize_text_field(trim((string) $input['schema_country_code'])));
			}
			if (isset($input['schema_event_status'])) {
				$clean['schema_event_status'] = esc_url_raw($input['schema_event_status']);
			}
			if (isset($input['schema_attendance_mode'])) {
				$clean['schema_attendance_mode'] = esc_url_raw($input['schema_attendance_mode']);
			}
			if (isset($input['schema_date_suffix_format'])) {
				$clean['schema_date_suffix_format'] = sanitize_text_field($input['schema_date_suffix_format']);
			}
			if (isset($input['enable_faq_schema_global'])) {
				$clean['enable_faq_schema_global'] = ! empty($input['enable_faq_schema_global']) ? '1' : '0';
			}
		}

		return $clean;
	}

	/**
	 * Sanitize disabled widgets list.
	 *
	 * @param mixed $input Raw input data.
	 * @return array
	 */
	public function sanitize_disabled_widgets($input): array
	{
		// Not submitted (e.g. a partial/legacy form): keep what is stored instead of re-enabling everything.
		if (! is_array($input)) {
			$stored = get_option(self::WIDGETS_KEY, array());
			return is_array($stored) ? $stored : array();
		}

		$valid_slugs = array_keys(self::get_widget_manifest());
		return array_values(array_intersect($input, $valid_slugs));
	}

	/**
	 * Sanitize disabled post types list.
	 *
	 * @param mixed $input Raw input data.
	 * @return array
	 */
	public function sanitize_disabled_post_types($input): array
	{
		// Not submitted (e.g. a partial/legacy form): keep what is stored instead of re-enabling everything.
		if (! is_array($input)) {
			$stored = get_option(self::POST_TYPES_KEY, array());
			return is_array($stored) ? $stored : array();
		}

		$valid_slugs = array_keys(self::get_post_types_manifest());
		return array_values(array_intersect($input, $valid_slugs));
	}

	/**
	 * Sanitize disabled taxonomies list.
	 *
	 * @param mixed $input Raw input data.
	 * @return array
	 */
	public function sanitize_disabled_taxonomies($input): array
	{
		// Not submitted (e.g. a partial/legacy form): keep what is stored instead of re-enabling everything.
		if (! is_array($input)) {
			$stored = get_option(self::TAXONOMIES_KEY, array());
			return is_array($stored) ? $stored : array();
		}

		$valid_slugs = array_keys(self::get_taxonomies_manifest());
		return array_values(array_intersect($input, $valid_slugs));
	}

	/**
	 * Sanitize URL slug overrides. Only values that differ from the source-code default are stored.
	 * Invalid or conflicting values are rejected (the previous value is kept) with an admin notice.
	 *
	 * @param mixed $input Raw input: [ 'post_types' => [ key => slug ], 'taxonomies' => [ key => slug ] ].
	 * @return array
	 */
	public function sanitize_slug_overrides($input): array
	{
		$stored = get_option(self::SLUGS_KEY, array());
		$stored = is_array($stored) ? $stored : array();
		if (! is_array($input)) {
			return $stored; // Not submitted: keep.
		}

		$labels = array(
			'post_types' => self::get_post_types_manifest(),
			'taxonomies' => self::get_taxonomies_manifest(),
		);

		// Effective slug of every item after this save, used for duplicate detection.
		$effective = array();
		$result    = array('post_types' => array(), 'taxonomies' => array());

		foreach ($labels as $kind => $manifest) {
			foreach ($manifest as $key => $item) {
				$default = self::get_default_slug($kind, $key);
				$raw     = isset($input[$kind][$key]) && is_string($input[$kind][$key]) ? trim(wp_unslash($input[$kind][$key])) : '';
				$slug    = '' === $raw ? $default : sanitize_title($raw);
				$error   = '';

				if ('' === $slug) {
					$error = __('contains no valid characters (use letters, numbers, hyphens or underscores).', 'pubcrawls-addon');
				} elseif (in_array($slug, self::RESERVED_SLUGS, true)) {
					/* translators: %s: slug */
					$error = sprintf(__('"%s" is reserved by WordPress.', 'pubcrawls-addon'), $slug);
				} elseif (isset($effective[$slug])) {
					/* translators: 1: slug, 2: other item label */
					$error = sprintf(__('"%1$s" is already used by %2$s.', 'pubcrawls-addon'), $slug, $effective[$slug]);
				} elseif ($slug !== $default && self::slug_used_elsewhere($slug)) {
					/* translators: %s: slug */
					$error = sprintf(__('"%s" is already used by another post type or taxonomy.', 'pubcrawls-addon'), $slug);
				}

				if ('' !== $error) {
					add_settings_error(
						self::SLUGS_KEY,
						'pubcrawls_slug_' . $key,
						/* translators: 1: item title, 2: reason */
						sprintf(__('Slug for %1$s was not changed: %2$s', 'pubcrawls-addon'), $item['title'], $error),
						'error'
					);
					// Keep the previously saved value (or default).
					$prev = isset($stored[$kind][$key]) && is_string($stored[$kind][$key]) ? sanitize_title($stored[$kind][$key]) : '';
					$slug = '' !== $prev ? $prev : $default;
				}

				$effective[$slug] = $item['title'];
				if ($slug !== $default) {
					$result[$kind][$key] = $slug;
				}
			}
		}
		return $result;
	}

	/**
	 * Whether a rewrite slug is already used by a post type / taxonomy not managed by this plugin.
	 *
	 * @param string $slug Candidate slug.
	 */
	private static function slug_used_elsewhere(string $slug): bool
	{
		$own_pt  = array_keys(self::DEFAULT_SLUGS['post_types']);
		$own_tax = array_keys(self::DEFAULT_SLUGS['taxonomies']);
		foreach (get_post_types(array(), 'objects') as $name => $obj) {
			if (in_array($name, $own_pt, true)) {
				continue;
			}
			$rewrite = is_array($obj->rewrite) && ! empty($obj->rewrite['slug']) ? $obj->rewrite['slug'] : ($obj->public ? $name : '');
			if ($rewrite === $slug) {
				return true;
			}
		}
		foreach (get_taxonomies(array(), 'objects') as $name => $obj) {
			if (in_array($name, $own_tax, true)) {
				continue;
			}
			$rewrite = is_array($obj->rewrite) && ! empty($obj->rewrite['slug']) ? $obj->rewrite['slug'] : '';
			if ($rewrite === $slug) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Enqueue Admin CSS & JS.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets(string $hook): void
	{
		if ('toplevel_page_pubcrawls-addon' !== $hook) {
			return;
		}

		$css_ver = PUBCRAWLS_ADDON_VERSION . '.' . (int) @filemtime(PUBCRAWLS_ADDON_DIR . 'assets/css/admin-dashboard.css'); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$js_ver  = PUBCRAWLS_ADDON_VERSION . '.' . (int) @filemtime(PUBCRAWLS_ADDON_DIR . 'assets/js/admin-dashboard.js');  // phpcs:ignore WordPress.PHP.NoSilencedErrors

		wp_enqueue_style(
			'pubcrawls-admin-dashboard',
			PUBCRAWLS_ADDON_URL . 'assets/css/admin-dashboard.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'pubcrawls-admin-dashboard',
			PUBCRAWLS_ADDON_URL . 'assets/js/admin-dashboard.js',
			array('jquery'),
			$js_ver,
			true
		);

		wp_localize_script(
			'pubcrawls-admin-dashboard',
			'pubcrawlsAdminData',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce'    => wp_create_nonce('pubcrawls_admin_nonce'),
			)
		);
	}

	/**
	 * Custom Post Types manifest.
	 */
	public static function get_post_types_manifest(): array
	{
		return array(
			'city'         => array(
				'title'       => esc_html__('City', 'pubcrawls-addon'),
				'slug'        => 'city',
				'icon'        => 'dashicons-location',
				'description' => esc_html__('Core post type for city crawl locations, city directories, and archives.', 'pubcrawls-addon'),
			),
			'tribe_events' => array(
				'title'       => esc_html__('Events', 'pubcrawls-addon'),
				'slug'        => 'tribe_events',
				'icon'        => 'dashicons-calendar-alt',
				'description' => esc_html__('Main pub crawl events post type with date repeaters and ticket pricing.', 'pubcrawls-addon'),
			),
			'venue'        => array(
				'title'       => esc_html__('Venue', 'pubcrawls-addon'),
				'slug'        => 'venue',
				'icon'        => 'dashicons-store',
				'description' => esc_html__('Participating bars, pubs, clubs, and check-in venue locations.', 'pubcrawls-addon'),
			),
			'faq'          => array(
				'title'       => esc_html__('FAQ', 'pubcrawls-addon'),
				'slug'        => 'faq',
				'icon'        => 'dashicons-editor-help',
				'description' => esc_html__('Global frequently asked questions queried by FAQ grid widgets.', 'pubcrawls-addon'),
			),
			'press'        => array(
				'title'       => esc_html__('Press', 'pubcrawls-addon'),
				'slug'        => 'press',
				'icon'        => 'dashicons-megaphone',
				'description' => esc_html__('Media releases, press coverage, news, and official announcements.', 'pubcrawls-addon'),
			),
			'city_map'     => array(
				'title'       => esc_html__('City Map', 'pubcrawls-addon'),
				'slug'        => 'city_map',
				'icon'        => 'dashicons-location-alt',
				'description' => esc_html__('Dedicated map post type for custom map layouts and Mapme embeds.', 'pubcrawls-addon'),
			),
		);
	}

	/**
	 * Custom Taxonomies manifest.
	 */
	public static function get_taxonomies_manifest(): array
	{
		return array(
			'tribe_events_cat' => array(
				'title'       => esc_html__('Event Category', 'pubcrawls-addon'),
				'slug'        => 'tribe_events_cat',
				'icon'        => 'dashicons-tag',
				'object'      => 'tribe_events',
				'description' => esc_html__('Hierarchical categories for events (e.g. St. Patrick\'s, Halloween, NYE).', 'pubcrawls-addon'),
			),
			'venue_category'   => array(
				'title'       => esc_html__('Venue Category', 'pubcrawls-addon'),
				'slug'        => 'venue_category',
				'icon'        => 'dashicons-tag',
				'object'      => 'venue',
				'description' => esc_html__('Categories to group and filter participating crawl venues.', 'pubcrawls-addon'),
			),
		);
	}

	/**
	 * Widget manifest with metadata.
	 */
	public static function get_widget_manifest(): array
	{
		return array(
			'pc-cities-card'     => array(
				'title'       => esc_html__('Cities Card Grid', 'pubcrawls-addon'),
				'icon'        => 'eicon-gallery-grid',
				'file'        => 'pubcrawl-cities-card.php',
				'class'       => 'Pubcrawls_Cities_Card',
				'description' => esc_html__('Displays visual cards of cities with dynamic upcoming event counts.', 'pubcrawls-addon'),
			),
			'pc-cities-list'     => array(
				'title'       => esc_html__('Cities Directory / List', 'pubcrawls-addon'),
				'icon'        => 'eicon-editor-list-ul',
				'file'        => 'pubcrawl-cities-list.php',
				'class'       => 'Pubcrawls_Cities_List',
				'description' => esc_html__('Alphabetical city directory with "Most Popular" and "All Cities" toggle.', 'pubcrawls-addon'),
			),
			'pc-city-archive'    => array(
				'title'       => esc_html__('City Archive Grid', 'pubcrawls-addon'),
				'icon'        => 'eicon-archive-posts',
				'file'        => 'pubcrawl-city-archive.php',
				'class'       => 'Pubcrawls_City_Archive',
				'description' => esc_html__('City cards filterable by event category for archive and landing pages.', 'pubcrawls-addon'),
			),
			'pc-city-events'     => array(
				'title'       => esc_html__('City Events Grid', 'pubcrawls-addon'),
				'icon'        => 'eicon-post-list',
				'file'        => 'pubcrawl-city-events.php',
				'class'       => 'Pubcrawls_City_Events',
				'description' => esc_html__('Multi-event listing grid with interactive date dropdown & ticket checkout.', 'pubcrawls-addon'),
			),
			'pc-city-map'        => array(
				'title'       => esc_html__('Interactive City Map', 'pubcrawls-addon'),
				'icon'        => 'eicon-google-maps',
				'file'        => 'pubcrawl-city-map.php',
				'class'       => 'Pubcrawls_City_Map',
				'description' => esc_html__('Embeds responsive Mapme or interactive map from ACF map_url field.', 'pubcrawls-addon'),
			),
			'pc-city-venues'     => array(
				'title'       => esc_html__('City Venues List', 'pubcrawls-addon'),
				'icon'        => 'eicon-map-pin',
				'file'        => 'pubcrawl-city-venues.php',
				'class'       => 'Pubcrawls_City_Venues',
				'description' => esc_html__('Grid of bars, pubs, and venues associated with a specific city.', 'pubcrawls-addon'),
			),
			'pc-event-agenda'    => array(
				'title'       => esc_html__('Event Agenda / Schedule', 'pubcrawls-addon'),
				'icon'        => 'eicon-time-line',
				'file'        => 'pubcrawl-event-agenda.php',
				'class'       => 'Pubcrawls_Event_Agenda',
				'description' => esc_html__('Timeline displaying crawl stops, check-in times, and schedule steps.', 'pubcrawls-addon'),
			),
			'pc-event-sticky'    => array(
				'title'       => esc_html__('Event Sticky Bar', 'pubcrawls-addon'),
				'icon'        => 'eicon-footer',
				'file'        => 'pubcrawl-event-sticky.php',
				'class'       => 'Pubcrawls_Event_Sticky',
				'description' => esc_html__('Floating bottom bar displaying event date and sticky ticket button.', 'pubcrawls-addon'),
			),
			'pc-event-tickets'   => array(
				'title'       => esc_html__('Event Tickets Card', 'pubcrawls-addon'),
				'icon'        => 'eicon-price-table',
				'file'        => 'pubcrawl-event-tickets.php',
				'class'       => 'Pubcrawls_Event_Tickets',
				'description' => esc_html__('Primary single-event ticket card with synchronized date selector & modal checkout.', 'pubcrawls-addon'),
			),
			'pc-event-venues'    => array(
				'title'       => esc_html__('Event Participating Venues', 'pubcrawls-addon'),
				'icon'        => 'eicon-gallery-grid',
				'file'        => 'pubcrawl-event-venues.php',
				'class'       => 'Pubcrawls_Event_Venues',
				'description' => esc_html__('Displays participating venues and addresses for the current event.', 'pubcrawls-addon'),
			),
			'pc-faq-grid'        => array(
				'title'       => esc_html__('FAQ Grid', 'pubcrawls-addon'),
				'icon'        => 'eicon-help-o',
				'file'        => 'pubcrawl-faq-grid.php',
				'class'       => 'Pubcrawls_FAQ_Grid',
				'description' => esc_html__('Accordion or grid FAQs queried from the custom FAQ post type.', 'pubcrawls-addon'),
			),
			'pc-get-directions'  => array(
				'title'       => esc_html__('Get Directions Button', 'pubcrawls-addon'),
				'icon'        => 'eicon-navigation-horizontal',
				'file'        => 'pubcrawl-get-directions.php',
				'class'       => 'Pubcrawls_Get_Directions',
				'description' => esc_html__('Dynamic button opening Google Maps directions to the check-in venue.', 'pubcrawls-addon'),
			),
			'pc-job-cities'      => array(
				'title'       => esc_html__('Job Cities Selector', 'pubcrawls-addon'),
				'icon'        => 'eicon-user-preferences',
				'file'        => 'pubcrawl-job-cities.php',
				'class'       => 'Pubcrawls_Job_Cities',
				'description' => esc_html__('City listing specifically designed for careers & job application forms.', 'pubcrawls-addon'),
			),
			'pc-next-event-date' => array(
				'title'       => esc_html__('Next Event Date Badge', 'pubcrawls-addon'),
				'icon'        => 'eicon-calendar',
				'file'        => 'pubcrawl-next-event-date.php',
				'class'       => 'Pubcrawls_Next_Event_Date',
				'description' => esc_html__('Shows next upcoming event date with customizable icon and typography.', 'pubcrawls-addon'),
			),
			'pc-page-faqs'       => array(
				'title'       => esc_html__('Page / Post FAQs', 'pubcrawls-addon'),
				'icon'        => 'eicon-help-o',
				'file'        => 'pubcrawl-page-faqs.php',
				'class'       => 'Pubcrawls_Page_FAQs',
				'description' => esc_html__('Displays contextual FAQs populated from current page/post ACF repeater.', 'pubcrawls-addon'),
			),
			'pc-social-share'    => array(
				'title'       => esc_html__('Social Share Buttons', 'pubcrawls-addon'),
				'icon'        => 'eicon-share',
				'file'        => 'pubcrawl-social-share.php',
				'class'       => 'Pubcrawls_Social_Share',
				'description' => esc_html__('One-click Facebook, X (Twitter), and LinkedIn share buttons.', 'pubcrawls-addon'),
			),
			'pc-ai-quick-links'  => array(
				'title'       => esc_html__('AI Quick Links', 'pubcrawls-addon'),
				'icon'        => 'eicon-share',
				'file'        => 'pubcrawl-ai-quick-links.php',
				'class'       => 'Pubcrawls_AI_Quick_Links',
				'description' => esc_html__('Direct query buttons for ChatGPT, Perplexity, Google AI, and Microsoft Copilot.', 'pubcrawls-addon'),
			),
			'pc-gravity-forms'   => array(
				'title'       => esc_html__('Gravity Forms', 'pubcrawls-addon'),
				'icon'        => 'eicon-form-horizontal',
				'file'        => 'pubcrawl-gravity-forms.php',
				'class'       => 'Pubcrawls_Gravity_Forms',
				'description' => esc_html__('Embeds and provides full Elementor styling controls for any active Gravity Form.', 'pubcrawls-addon'),
			),
			'pc-timeline'        => array(
				'title'       => esc_html__('Steps Timeline', 'pubcrawls-addon'),
				'icon'        => 'eicon-time-line',
				'file'        => 'pubcrawl-timeline.php',
				'class'       => 'Pubcrawls_Timeline',
				'description' => esc_html__('Interactive vertical timeline with scroll line fill progress, steps, icons, and titles.', 'pubcrawls-addon'),
			),
			'pc-taxonomy-terms'  => array(
				'title'       => esc_html__('Taxonomy Terms Grid', 'pubcrawls-addon'),
				'icon'        => 'eicon-tags',
				'file'        => 'pubcrawl-taxonomy-terms.php',
				'class'       => 'Pubcrawls_Taxonomy_Terms',
				'description' => esc_html__('Displays taxonomy terms in a responsive grid with post counts, custom after-text, and complete box/hover styling.', 'pubcrawls-addon'),
			),
			'pc-venue-archive'   => array(
				'title'       => esc_html__('Venue Archive Grid', 'pubcrawls-addon'),
				'icon'        => 'eicon-gallery-grid',
				'file'        => 'pubcrawl-venue-archive.php',
				'class'       => 'Pubcrawls_Venue_Archive',
				'description' => esc_html__('Grid of venues with category filter and directions links, designed for venue category archives.', 'pubcrawls-addon'),
			),
			'pc-post-content'    => array(
				'title'       => esc_html__('Post Content', 'pubcrawls-addon'),
				'icon'        => 'eicon-post-content',
				'file'        => 'pubcrawl-post-content.php',
				'class'       => 'Pubcrawls_Post_Content',
				'description' => esc_html__('Full-featured post content widget designed to style all Gutenberg block elements with pixel-perfect control.', 'pubcrawls-addon'),
			),
			'pc-author-box'       => array(
				'title'       => esc_html__('Author Box', 'pubcrawls-addon'),
				'icon'        => 'eicon-person',
				'file'        => 'pubcrawl-author-box.php',
				'class'       => 'Pubcrawls_Author_Box',
				'description' => esc_html__('Displays a card with author image, name, bio, and social media profiles powered by ACF User meta.', 'pubcrawls-addon'),
			),
		);
	}

	/**
	 * System Health & Dependencies.
	 */
	public static function get_system_health(): array
	{
		$elementor_installed = did_action('elementor/loaded');
		$elementor_version   = defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null;
		$elementor_min_ok    = $elementor_installed && version_compare($elementor_version, '3.5.0', '>=');

		$acf_installed = function_exists('get_field');
		$acf_version   = defined('ACF_VERSION') ? ACF_VERSION : null;

		$tec_installed = post_type_exists('tribe_events');

		return array(
			'elementor' => array(
				'label'   => 'Elementor',
				'status'  => $elementor_min_ok ? 'ok' : ($elementor_installed ? 'warning' : 'error'),
				'version' => ! empty($elementor_version) ? $elementor_version : esc_html__('Not Active', 'pubcrawls-addon'),
				'note'    => $elementor_min_ok ? esc_html__('Compatible (v3.5+)', 'pubcrawls-addon') : esc_html__('Required for Elementor widgets', 'pubcrawls-addon'),
			),
			'acf'       => array(
				'label'   => 'Advanced Custom Fields',
				'status'  => $acf_installed ? 'ok' : 'error',
				'version' => ! empty($acf_version) ? $acf_version : ($acf_installed ? esc_html__('Active', 'pubcrawls-addon') : esc_html__('Not Active', 'pubcrawls-addon')),
				'note'    => $acf_installed ? esc_html__('Required ACF functions available', 'pubcrawls-addon') : esc_html__('Required for event dates, venues & maps', 'pubcrawls-addon'),
			),
			'tec'       => array(
				'label'   => 'The Events Calendar',
				'status'  => $tec_installed ? 'ok' : 'info',
				'version' => defined('TRIBE_EVENTS_FILE') ? 'Active' : ($tec_installed ? 'Custom Post Type' : 'Not Detected'),
				'note'    => $tec_installed ? esc_html__('tribe_events post type is active', 'pubcrawls-addon') : esc_html__('Custom post type or plugin active', 'pubcrawls-addon'),
			),
			'php'       => array(
				'label'   => 'PHP Version',
				'status'  => version_compare(PHP_VERSION, '7.4', '>=') ? 'ok' : 'error',
				'version' => PHP_VERSION,
				'note'    => esc_html__('Server runtime environment', 'pubcrawls-addon'),
			),
		);
	}

	/**
	 * Get overview post type counts.
	 */
	public static function get_overview_counts(): array
	{
		$count_cities = wp_count_posts('city');
		$count_events = wp_count_posts('tribe_events');
		$count_faqs   = wp_count_posts('faq');
		$count_venues = wp_count_posts('venue');

		return array(
			'total_cities' => isset($count_cities->publish) ? intval($count_cities->publish) : 0,
			'total_events' => isset($count_events->publish) ? intval($count_events->publish) : 0,
			'total_faqs'   => isset($count_faqs->publish) ? intval($count_faqs->publish) : 0,
			'total_venues' => isset($count_venues->publish) ? intval($count_venues->publish) : 0,
		);
	}

	/**
	 * Render the Dashboard View.
	 */
	public function render_dashboard(): void
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		$view_path = PUBCRAWLS_ADDON_DIR . 'includes/admin/views/admin-dashboard.php';
		if (file_exists($view_path)) {
			require_once $view_path;
		}
	}
}
