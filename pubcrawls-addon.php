<?php

/**
 * Plugin Name:       Pubcrawls
 * Plugin URI:        https://pubcrawls.com/
 * Description:       Complete standalone solution for PubCrawls — provides Custom Post Types (Cities, Events, Venues, FAQs, Press, City Maps), Taxonomies, Shortcodes, Ticket Integrations (SquadUp & Eventbrite), and 17 native Elementor Widgets with rich content & style controls.
 * Version:           3.0.0
 * Author:            Nexiby LLC
 * Author URI:        https://nexiby.com/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pubcrawls-addon
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

// ── Plugin Constants ──────────────────────────────────────────────────────────
define('PUBCRAWLS_ADDON_VERSION', '3.0.0');
define('PUBCRAWLS_ADDON_DIR',     plugin_dir_path(__FILE__));
define('PUBCRAWLS_ADDON_URL',     plugin_dir_url(__FILE__));
define('PUBCRAWLS_ADDON_FILE',    __FILE__);

// Backward-compatibility constants (for any template/asset expecting PC_* or PCET_* constants)
if (! defined('PC_VERSION')) {
	define('PC_VERSION', PUBCRAWLS_ADDON_VERSION);
}
if (! defined('PC_PLUGIN_DIR')) {
	define('PC_PLUGIN_DIR', PUBCRAWLS_ADDON_DIR);
}
if (! defined('PC_PLUGIN_URL')) {
	define('PC_PLUGIN_URL', PUBCRAWLS_ADDON_URL);
}
if (! defined('PCET_VERSION')) {
	define('PCET_VERSION', PUBCRAWLS_ADDON_VERSION);
}
if (! defined('PCET_PLUGIN_DIR')) {
	define('PCET_PLUGIN_DIR', PUBCRAWLS_ADDON_DIR);
}
if (! defined('PCET_PLUGIN_URL')) {
	define('PCET_PLUGIN_URL', PUBCRAWLS_ADDON_URL);
}
if (! defined('PCET_PLUGIN_FILE')) {
	define('PCET_PLUGIN_FILE', __FILE__);
}

// ── Load Core Modules ─────────────────────────────────────────────────────────
require_once PUBCRAWLS_ADDON_DIR . 'includes/post-types.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/taxonomies.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/shortcodes.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/elementor-queries.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/tickets-helpers.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/ai-links-helpers.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/class-pubcrawls-event-schema.php';
Pubcrawls_Event_Schema::init();

// ── Load Server-Side Tracking & Eventbrite Webhook ────────────────────────────
require_once PUBCRAWLS_ADDON_DIR . 'includes/tracking/class-pubcrawls-tracking-db.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/tracking/class-pubcrawls-eventbrite-api.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/tracking/class-pubcrawls-tracking-dispatcher.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/tracking/class-pubcrawls-eventbrite-webhook.php';
require_once PUBCRAWLS_ADDON_DIR . 'includes/tracking/class-pubcrawls-eventbrite-queue.php';
Pubcrawls_Tracking_DB::init();
Pubcrawls_Eventbrite_Webhook::init();
Pubcrawls_Eventbrite_Queue::init();

// ── Load Admin Dashboard ──────────────────────────────────────────────────────
require_once PUBCRAWLS_ADDON_DIR . 'includes/admin/class-pubcrawls-admin.php';
Pubcrawls_Admin::init();

// ── Load Helpers & Bulk Tools ─────────────────────────────────────────────────
require_once PUBCRAWLS_ADDON_DIR . 'includes/helpers/class-pubcrawls-helpers-loader.php';
Pubcrawls_Helpers_Loader::init();

// ── Load Elementor Integration ────────────────────────────────────────────────
require_once PUBCRAWLS_ADDON_DIR . 'includes/class-pubcrawls-addon.php';

// ── Enqueue Core Frontend Styles & Scripts ────────────────────────────────────
add_action('wp_enqueue_scripts', function () {

	// Core Plugin Styles
	if (! wp_style_is('pub-crawls-styles', 'enqueued')) {
		wp_enqueue_style(
			'pub-crawls-styles',
			PUBCRAWLS_ADDON_URL . 'assets/css/style.css',
			[],
			PUBCRAWLS_ADDON_VERSION
		);
	}

	// Core Plugin JS
	if (! wp_script_is('pub-crawls-js', 'enqueued')) {
		wp_enqueue_script(
			'pub-crawls-js',
			PUBCRAWLS_ADDON_URL . 'assets/js/pubcrawls.js',
			['jquery'],
			PUBCRAWLS_ADDON_VERSION,
			true
		);
	}
});

// ── Flush Rewrite Rules on Activation & Deactivation ──────────────────────────
register_activation_hook(__FILE__, function () {
	if (class_exists('Pubcrawls_Tracking_DB')) {
		Pubcrawls_Tracking_DB::create_tables();
	}
	if (function_exists('pubcrawls_register_post_types')) {
		pubcrawls_register_post_types();
	}
	if (function_exists('pubcrawls_register_taxonomies')) {
		pubcrawls_register_taxonomies();
	}
	flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
	flush_rewrite_rules();
});

// ── Bootstrap Elementor Addon ─────────────────────────────────────────────────
function pubcrawls_addon_run()
{
	return Pubcrawls_Addon::instance();
}
pubcrawls_addon_run();
