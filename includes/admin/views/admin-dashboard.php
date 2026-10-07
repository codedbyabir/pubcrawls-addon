<?php

/**
 * PubCrawls Admin Dashboard View Template
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
$health     = Pubcrawls_Admin::get_system_health();
$counts     = Pubcrawls_Admin::get_overview_counts();
$post_types = Pubcrawls_Admin::get_post_types_manifest();
$taxonomies = Pubcrawls_Admin::get_taxonomies_manifest();
$widgets    = Pubcrawls_Admin::get_widget_manifest();
$settings   = get_option(Pubcrawls_Admin::SETTINGS_KEY, Pubcrawls_Admin::get_default_settings());

$disabled_w = get_option(Pubcrawls_Admin::WIDGETS_KEY, array());
if (! is_array($disabled_w)) {
	$disabled_w = array();
}

$disabled_pt = get_option(Pubcrawls_Admin::POST_TYPES_KEY, array());
if (! is_array($disabled_pt)) {
	$disabled_pt = array();
}

$disabled_tax = get_option(Pubcrawls_Admin::TAXONOMIES_KEY, array());
if (! is_array($disabled_tax)) {
	$disabled_tax = array();
}
?>

<div class="wrap pubcrawls-dashboard-wrap">

	<!-- Header -->
	<header class="pubcrawls-header">
		<div class="pubcrawls-header__logo">
			<span class="pubcrawls-header__mark"><span class="dashicons dashicons-tickets-alt"></span></span>
			<div>
				<h1><?php esc_html_e('PubCrawls', 'pubcrawls-addon'); ?></h1>
				<span class="pubcrawls-version">v<?php echo esc_html(PUBCRAWLS_ADDON_VERSION); ?></span>
			</div>
		</div>
		<div class="pubcrawls-header__meta">
			<span class="pubcrawls-badge pubcrawls-badge--elementor">
				<span class="dashicons dashicons-welcome-widgets-menus"></span> <?php echo esc_html(count($widgets) - count($disabled_w)); ?>/<?php echo esc_html(count($widgets)); ?> <?php esc_html_e('Widgets Active', 'pubcrawls-addon'); ?>
			</span>
			<?php if (! in_array('tribe_events', $disabled_pt, true)) : ?>
				<a href="<?php echo esc_url(admin_url('edit.php?post_type=tribe_events')); ?>" class="button button-secondary">
					<span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Events', 'pubcrawls-addon'); ?>
				</a>
			<?php endif; ?>
			<?php if (! in_array('city', $disabled_pt, true)) : ?>
				<a href="<?php echo esc_url(admin_url('edit.php?post_type=city')); ?>" class="button button-primary">
					<span class="dashicons dashicons-location-alt"></span> <?php esc_html_e('Cities', 'pubcrawls-addon'); ?>
				</a>
			<?php endif; ?>
		</div>
	</header>
	<hr class="wp-header-end" />

	<?php settings_errors(); ?>

	<?php
	$active_helpers_count = (! empty($settings['enable_faq_seo_importer']) && '1' === (string) $settings['enable_faq_seo_importer'] ? 1 : 0)
		+ (! empty($settings['enable_bulk_title_editor']) && '1' === (string) $settings['enable_bulk_title_editor'] ? 1 : 0)
		+ (! empty($settings['enable_quick_featured_image']) && '1' === (string) $settings['enable_quick_featured_image'] ? 1 : 0)
		+ (! empty($settings['enable_post_url_column']) && '1' === (string) $settings['enable_post_url_column'] ? 1 : 0);

	// Sidebar navigation: grouped so related settings live together.
	$nav_groups = array(
		array(
			'label' => '',
			'items' => array(
				'overview' => array(esc_html__('Overview & Health', 'pubcrawls-addon'), 'dashicons-dashboard', ''),
			),
		),
		array(
			'label' => esc_html__('Content', 'pubcrawls-addon'),
			'items' => array(
				'post-types' => array(esc_html__('Post Types & Taxonomies', 'pubcrawls-addon'), 'dashicons-category', (count($post_types) - count($disabled_pt)) . '/' . count($post_types)),
				'widgets'    => array(esc_html__('Widgets Manager', 'pubcrawls-addon'), 'dashicons-screenoptions', (count($widgets) - count($disabled_w)) . '/' . count($widgets)),
			),
		),
		array(
			'label' => esc_html__('Tickets & Tracking', 'pubcrawls-addon'),
			'items' => array(
				'settings' => array(esc_html__('Ticket Settings', 'pubcrawls-addon'), 'dashicons-tickets-alt', ''),
				'tracking' => array(esc_html__('Tracking & Webhooks', 'pubcrawls-addon'), 'dashicons-rest-api', 'CAPI'),
			),
		),
		array(
			'label' => esc_html__('SEO', 'pubcrawls-addon'),
			'items' => array(
				'schema' => array(esc_html__('Schema & SEO', 'pubcrawls-addon'), 'dashicons-networking', 'JSON-LD'),
			),
		),
		array(
			'label' => esc_html__('Tools', 'pubcrawls-addon'),
			'items' => array(
				'helpers' => array(esc_html__('Helpers & Tools', 'pubcrawls-addon'), 'dashicons-admin-tools', $active_helpers_count > 0 ? (string) $active_helpers_count : ''),
			),
		),
	);
	?>

	<!-- One form for every tab: a single Save stores all tabs together, so nothing is lost when switching tabs. -->
	<form method="post" action="options.php" id="pubcrawls-settings-form" class="pubcrawls-form" novalidate>
		<?php settings_fields('pubcrawls_settings_group'); ?>
		<?php foreach (array('ticket_settings_submitted', 'schema_settings_submitted', 'tracking_settings_submitted', 'helpers_settings_submitted') as $pc_flag) : ?>
			<input type="hidden" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[<?php echo esc_attr($pc_flag); ?>]" value="1" />
		<?php endforeach; ?>
		<?php /* Empty markers so "nothing disabled" is submitted as an empty list instead of being treated as "not submitted". */ ?>
		<input type="hidden" name="<?php echo esc_attr(Pubcrawls_Admin::WIDGETS_KEY); ?>[]" value="" />
		<input type="hidden" name="<?php echo esc_attr(Pubcrawls_Admin::POST_TYPES_KEY); ?>[]" value="" />
		<input type="hidden" name="<?php echo esc_attr(Pubcrawls_Admin::TAXONOMIES_KEY); ?>[]" value="" />

		<div class="pubcrawls-layout">

			<aside class="pubcrawls-sidebar">
				<nav class="pubcrawls-nav" id="pubcrawls-tab-nav" aria-label="<?php esc_attr_e('PubCrawls sections', 'pubcrawls-addon'); ?>">
					<?php foreach ($nav_groups as $group) : ?>
						<?php if ('' !== $group['label']) : ?>
							<span class="pubcrawls-nav__group"><?php echo esc_html($group['label']); ?></span>
						<?php endif; ?>
						<?php foreach ($group['items'] as $tab_key => $tab) : ?>
							<a href="?page=pubcrawls-addon&tab=<?php echo esc_attr($tab_key); ?>" class="pubcrawls-nav__item <?php echo $tab_key === $active_tab ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr($tab_key); ?>">
								<span class="dashicons <?php echo esc_attr($tab[1]); ?>"></span>
								<span class="pubcrawls-nav__label"><?php echo esc_html($tab[0]); ?></span>
								<?php if ('' !== $tab[2]) : ?>
									<span class="pubcrawls-tab-count"><?php echo esc_html($tab[2]); ?></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</nav>
			</aside>

			<div class="pubcrawls-main">


	<!-- ============================================================ -->
	<!-- TAB 1: OVERVIEW & HEALTH -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'overview' === $active_tab ? 'is-active' : ''; ?>" id="tab-overview">

		<!-- Stats Grid -->
		<div class="pubcrawls-stats-grid">
			<div class="pubcrawls-stat-card">
				<div class="pubcrawls-stat-icon pubcrawls-stat-icon--city">
					<span class="dashicons dashicons-location"></span>
				</div>
				<div class="pubcrawls-stat-data">
					<span class="pubcrawls-stat-num"><?php echo esc_html($counts['total_cities']); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e('Published Cities', 'pubcrawls-addon'); ?></span>
				</div>
				<?php if (! in_array('city', $disabled_pt, true)) : ?>
					<a href="<?php echo esc_url(admin_url('post-new.php?post_type=city')); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add City', 'pubcrawls-addon'); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="pubcrawls-stat-card">
				<div class="pubcrawls-stat-icon pubcrawls-stat-icon--event">
					<span class="dashicons dashicons-calendar-alt"></span>
				</div>
				<div class="pubcrawls-stat-data">
					<span class="pubcrawls-stat-num"><?php echo esc_html($counts['total_events']); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e('Events Listed', 'pubcrawls-addon'); ?></span>
				</div>
				<?php if (! in_array('tribe_events', $disabled_pt, true)) : ?>
					<a href="<?php echo esc_url(admin_url('post-new.php?post_type=tribe_events')); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add Event', 'pubcrawls-addon'); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="pubcrawls-stat-card">
				<div class="pubcrawls-stat-icon pubcrawls-stat-icon--venue">
					<span class="dashicons dashicons-store"></span>
				</div>
				<div class="pubcrawls-stat-data">
					<span class="pubcrawls-stat-num"><?php echo esc_html($counts['total_venues']); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e('Participating Venues', 'pubcrawls-addon'); ?></span>
				</div>
				<?php if (! in_array('venue', $disabled_pt, true)) : ?>
					<a href="<?php echo esc_url(admin_url('post-new.php?post_type=venue')); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add Venue', 'pubcrawls-addon'); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="pubcrawls-stat-card">
				<div class="pubcrawls-stat-icon pubcrawls-stat-icon--faq">
					<span class="dashicons dashicons-editor-help"></span>
				</div>
				<div class="pubcrawls-stat-data">
					<span class="pubcrawls-stat-num"><?php echo esc_html($counts['total_faqs']); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e('Published FAQs', 'pubcrawls-addon'); ?></span>
				</div>
				<?php if (! in_array('faq', $disabled_pt, true)) : ?>
					<a href="<?php echo esc_url(admin_url('post-new.php?post_type=faq')); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add FAQ', 'pubcrawls-addon'); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- System Diagnostics -->
		<div class="pubcrawls-card">
			<div class="pubcrawls-card__header">
				<h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e('System Integration Health', 'pubcrawls-addon'); ?></h2>
				<span class="pubcrawls-card__subtitle"><?php esc_html_e('Real-time check of all dependencies and required environments.', 'pubcrawls-addon'); ?></span>
			</div>
			<div class="pubcrawls-card__body">
				<table class="widefat pubcrawls-health-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Component', 'pubcrawls-addon'); ?></th>
							<th><?php esc_html_e('Status', 'pubcrawls-addon'); ?></th>
							<th><?php esc_html_e('Version / State', 'pubcrawls-addon'); ?></th>
							<th><?php esc_html_e('Details', 'pubcrawls-addon'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($health as $comp) : ?>
							<tr>
								<td><strong><?php echo esc_html($comp['label']); ?></strong></td>
								<td>
									<?php if ('ok' === $comp['status']) : ?>
										<span class="pubcrawls-status-tag pubcrawls-status-tag--ok">
											<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Operational', 'pubcrawls-addon'); ?>
										</span>
									<?php elseif ('warning' === $comp['status']) : ?>
										<span class="pubcrawls-status-tag pubcrawls-status-tag--warning">
											<span class="dashicons dashicons-warning"></span> <?php esc_html_e('Notice', 'pubcrawls-addon'); ?>
										</span>
									<?php else : ?>
										<span class="pubcrawls-status-tag pubcrawls-status-tag--error">
											<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Missing / Inactive', 'pubcrawls-addon'); ?>
										</span>
									<?php endif; ?>
								</td>
								<td><code><?php echo esc_html($comp['version']); ?></code></td>
								<td class="description"><?php echo esc_html($comp['note']); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Quick Feature Overview -->
		<div class="pubcrawls-card-grid">
			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h3><span class="dashicons dashicons-category"></span> <?php esc_html_e('Post Types & Taxonomies', 'pubcrawls-addon'); ?></h3>
				</div>
				<div class="pubcrawls-card__body">
					<p><?php esc_html_e('Manage custom post types (Cities, Events, Venues, FAQs, Press, City Maps) and taxonomies directly from dashboard toggles.', 'pubcrawls-addon'); ?></p>
					<a href="?page=pubcrawls-addon&tab=post-types" data-tab="post-types" class="button button-secondary"><?php esc_html_e('Configure Post Types', 'pubcrawls-addon'); ?> &rarr;</a>
				</div>
			</div>

			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h3><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e('Widgets Manager', 'pubcrawls-addon'); ?></h3>
				</div>
				<div class="pubcrawls-card__body">
					<p><?php esc_html_e('Toggle off unused Elementor widgets to keep the editor panel tidy and boost editor loading speeds.', 'pubcrawls-addon'); ?></p>
					<a href="?page=pubcrawls-addon&tab=widgets" data-tab="widgets" class="button button-secondary"><?php esc_html_e('Configure Widgets', 'pubcrawls-addon'); ?> &rarr;</a>
				</div>
			</div>
		</div>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 2: POST TYPES & TAXONOMIES -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'post-types' === $active_tab ? 'is-active' : ''; ?>" id="tab-post-types">

		<div class="pubcrawls-callout pubcrawls-callout--info">
			<span class="dashicons dashicons-info-outline"></span>
			<div>
				<strong><?php esc_html_e('About URL slugs', 'pubcrawls-addon'); ?></strong>
				<p><?php esc_html_e('The slug is the part of the public URL for each post type or taxonomy. It is prefilled with the plugin default; clear the field or press reset to go back to it. Permalinks are refreshed automatically after saving. Old URLs stop working after a change, so set up redirects if the pages are already indexed.', 'pubcrawls-addon'); ?></p>
			</div>
		</div>

			<!-- Custom Post Types Section -->
			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header pubcrawls-card__header--flex">
					<div>
						<h2><span class="dashicons dashicons-admin-post"></span> <?php esc_html_e('Custom Post Types', 'pubcrawls-addon'); ?></h2>
						<span class="pubcrawls-card__subtitle"><?php esc_html_e('Enable or disable custom post types registered by the plugin.', 'pubcrawls-addon'); ?></span>
					</div>
					<div class="pubcrawls-bulk-actions">
						<button type="button" class="button" id="pubcrawls-enable-all-pt"><?php esc_html_e('Enable All', 'pubcrawls-addon'); ?></button>
						<button type="button" class="button" id="pubcrawls-disable-all-pt"><?php esc_html_e('Disable All', 'pubcrawls-addon'); ?></button>
					</div>
				</div>

				<div class="pubcrawls-card__body">
					<div class="pubcrawls-widgets-grid">
						<?php
						foreach ($post_types as $slug => $pt) :
							$is_disabled = in_array($slug, $disabled_pt, true);
						?>
							<div class="pubcrawls-widget-card <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>">
								<div class="pubcrawls-widget-card__top">
									<div class="pubcrawls-widget-card__icon">
										<span class="dashicons <?php echo esc_attr($pt['icon']); ?>"></span>
									</div>
									<label class="pubcrawls-switch">
										<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::POST_TYPES_KEY); ?>[]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_disabled); ?> class="pubcrawls-pt-toggle" />
										<span class="pubcrawls-slider"></span>
									</label>
								</div>

								<h3 class="pubcrawls-widget-card__title"><?php echo esc_html($pt['title']); ?></h3>
								<p class="pubcrawls-widget-card__desc"><?php echo esc_html($pt['description']); ?></p>
									<?php
									$pc_default = Pubcrawls_Admin::get_default_slug('post_types', $slug);
									$pc_current = Pubcrawls_Admin::get_slug('post_types', $slug);
									?>
									<div class="pubcrawls-slug">
										<label class="pubcrawls-slug__label" for="pc-slug-post_types-<?php echo esc_attr($slug); ?>"><?php esc_html_e('URL slug', 'pubcrawls-addon'); ?></label>
										<div class="pubcrawls-slug__field">
											<span class="pubcrawls-slug__prefix"><?php echo esc_html(preg_replace("#^https?://#", "", untrailingslashit(home_url()))); ?>/</span>
											<input type="text" id="pc-slug-post_types-<?php echo esc_attr($slug); ?>" class="pubcrawls-slug__input" name="<?php echo esc_attr(Pubcrawls_Admin::SLUGS_KEY); ?>[post_types][<?php echo esc_attr($slug); ?>]" value="<?php echo esc_attr($pc_current); ?>" data-default="<?php echo esc_attr($pc_default); ?>" placeholder="<?php echo esc_attr($pc_default); ?>" autocomplete="off" spellcheck="false" />
											<button type="button" class="pubcrawls-slug__reset" title="<?php esc_attr_e('Reset to default slug', 'pubcrawls-addon'); ?>" <?php echo $pc_current === $pc_default ? 'hidden' : ''; ?>><span class="dashicons dashicons-undo"></span></button>
										</div>
										<p class="pubcrawls-slug__hint">
											<?php esc_html_e('Default:', 'pubcrawls-addon'); ?> <code><?php echo esc_html($pc_default); ?></code>
											&middot; <span class="pubcrawls-slug__preview" data-suffix="sample-post">/<?php echo esc_html($pc_current); ?>/sample-post/</span>
										</p>
									</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Custom Taxonomies Section -->
			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header pubcrawls-card__header--flex">
					<div>
						<h2><span class="dashicons dashicons-tag"></span> <?php esc_html_e('Custom Taxonomies', 'pubcrawls-addon'); ?></h2>
						<span class="pubcrawls-card__subtitle"><?php esc_html_e('Enable or disable custom taxonomies registered by the plugin.', 'pubcrawls-addon'); ?></span>
					</div>
					<div class="pubcrawls-bulk-actions">
						<button type="button" class="button" id="pubcrawls-enable-all-tax"><?php esc_html_e('Enable All', 'pubcrawls-addon'); ?></button>
						<button type="button" class="button" id="pubcrawls-disable-all-tax"><?php esc_html_e('Disable All', 'pubcrawls-addon'); ?></button>
					</div>
				</div>

				<div class="pubcrawls-card__body">
					<div class="pubcrawls-widgets-grid">
						<?php
						foreach ($taxonomies as $slug => $tax_item) :
							$is_disabled = in_array($slug, $disabled_tax, true);
						?>
							<div class="pubcrawls-widget-card <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>">
								<div class="pubcrawls-widget-card__top">
									<div class="pubcrawls-widget-card__icon">
										<span class="dashicons <?php echo esc_attr($tax_item['icon']); ?>"></span>
									</div>
									<label class="pubcrawls-switch">
										<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::TAXONOMIES_KEY); ?>[]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_disabled); ?> class="pubcrawls-tax-toggle" />
										<span class="pubcrawls-slider"></span>
									</label>
								</div>

								<h3 class="pubcrawls-widget-card__title"><?php echo esc_html($tax_item['title']); ?></h3>
								<p class="pubcrawls-widget-card__desc"><?php echo esc_html($tax_item['description']); ?></p>
									<?php
									$pc_default = Pubcrawls_Admin::get_default_slug('taxonomies', $slug);
									$pc_current = Pubcrawls_Admin::get_slug('taxonomies', $slug);
									?>
									<div class="pubcrawls-slug">
										<label class="pubcrawls-slug__label" for="pc-slug-taxonomies-<?php echo esc_attr($slug); ?>"><?php esc_html_e('URL slug', 'pubcrawls-addon'); ?></label>
										<div class="pubcrawls-slug__field">
											<span class="pubcrawls-slug__prefix"><?php echo esc_html(preg_replace("#^https?://#", "", untrailingslashit(home_url()))); ?>/</span>
											<input type="text" id="pc-slug-taxonomies-<?php echo esc_attr($slug); ?>" class="pubcrawls-slug__input" name="<?php echo esc_attr(Pubcrawls_Admin::SLUGS_KEY); ?>[taxonomies][<?php echo esc_attr($slug); ?>]" value="<?php echo esc_attr($pc_current); ?>" data-default="<?php echo esc_attr($pc_default); ?>" placeholder="<?php echo esc_attr($pc_default); ?>" autocomplete="off" spellcheck="false" />
											<button type="button" class="pubcrawls-slug__reset" title="<?php esc_attr_e('Reset to default slug', 'pubcrawls-addon'); ?>" <?php echo $pc_current === $pc_default ? 'hidden' : ''; ?>><span class="dashicons dashicons-undo"></span></button>
										</div>
										<p class="pubcrawls-slug__hint">
											<?php esc_html_e('Default:', 'pubcrawls-addon'); ?> <code><?php echo esc_html($pc_default); ?></code>
											&middot; <span class="pubcrawls-slug__preview" data-suffix="sample-term">/<?php echo esc_html($pc_current); ?>/sample-term/</span>
										</p>
									</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>


	</section>

	<!-- ============================================================ -->
	<!-- TAB 3: WIDGETS MANAGER -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'widgets' === $active_tab ? 'is-active' : ''; ?>" id="tab-widgets">

			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header pubcrawls-card__header--flex">
					<div>
						<h2><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e('Elementor Widgets Manager', 'pubcrawls-addon'); ?></h2>
						<span class="pubcrawls-card__subtitle"><?php esc_html_e('Toggle off widgets you are not using to speed up Elementor editor load times and keep your widgets panel tidy.', 'pubcrawls-addon'); ?></span>
					</div>
					<div class="pubcrawls-bulk-actions">
						<button type="button" class="button" id="pubcrawls-enable-all"><?php esc_html_e('Enable All', 'pubcrawls-addon'); ?></button>
						<button type="button" class="button" id="pubcrawls-disable-all"><?php esc_html_e('Disable All', 'pubcrawls-addon'); ?></button>
					</div>
				</div>

				<div class="pubcrawls-card__body">

					<div class="pubcrawls-widgets-grid">
						<?php
						foreach ($widgets as $slug => $widget) :
							$is_disabled = in_array($slug, $disabled_w, true);
						?>
							<div class="pubcrawls-widget-card <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>">
								<div class="pubcrawls-widget-card__top">
									<div class="pubcrawls-widget-card__icon">
										<span class="dashicons dashicons-screenoptions"></span>
									</div>
									<label class="pubcrawls-switch">
										<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::WIDGETS_KEY); ?>[]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_disabled); ?> class="pubcrawls-widget-toggle" />
										<span class="pubcrawls-slider"></span>
									</label>
								</div>

								<h3 class="pubcrawls-widget-card__title"><?php echo esc_html($widget['title']); ?></h3>
								<p class="pubcrawls-widget-card__desc"><?php echo esc_html($widget['description']); ?></p>
							</div>
						<?php endforeach; ?>
					</div>

				</div>
			</div>


	</section>

	<!-- ============================================================ -->
	<!-- TAB 4: TICKET SETTINGS -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>" id="tab-settings">

			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Ticket Provider & Checkout Options', 'pubcrawls-addon'); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e('Configure default checkout behavior and SquadUp checkout parameters.', 'pubcrawls-addon'); ?></span>
				</div>
				<div class="pubcrawls-card__body">

					<table class="form-table" role="presentation">
						<tbody>
							<!-- Default Ticket Provider -->
							<tr>
								<th scope="row">
									<label for="default_ticket_provider"><?php esc_html_e('Default Ticket Provider', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<select name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[default_ticket_provider]" id="default_ticket_provider" class="regular-text">
										<option value="squadup" <?php selected($settings['default_ticket_provider'], 'squadup'); ?>>
											<?php esc_html_e('SquadUp (Modal Checkout)', 'pubcrawls-addon'); ?>
										</option>
										<option value="eventbrite" <?php selected($settings['default_ticket_provider'], 'eventbrite'); ?>>
											<?php esc_html_e('Eventbrite (Widget Modal)', 'pubcrawls-addon'); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e('Default fallback ticket provider when an individual event date does not specify one.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- SquadUp User / Organizer ID -->
							<tr>
								<th scope="row">
									<label for="squadup_user_id"><?php esc_html_e('SquadUp Organizer / User ID', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[squadup_user_id]" id="squadup_user_id" value="<?php echo esc_attr($settings['squadup_user_id'] ?? '3438001'); ?>" class="regular-text code" />
									<p class="description"><?php esc_html_e('Your SquadUp numeric user/organizer ID (e.g. 3438001). Passed to the SquadUp checkout SDK.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- SquadUp Modal Logo / Header Image -->
							<tr>
								<th scope="row">
									<label for="squadup_modal_image"><?php esc_html_e('SquadUp Modal Logo URL', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="url" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[squadup_modal_image]" id="squadup_modal_image" value="<?php echo esc_attr($settings['squadup_modal_image'] ?? 'https://pubcrawls.com/wp-content/uploads/2026/03/Logo.webp'); ?>" class="large-text code" />
									<p class="description"><?php esc_html_e('Brand logo image URL rendered in the header of the SquadUp checkout modal.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- SquadUp Modal Title -->
							<tr>
								<th scope="row">
									<label for="squadup_modal_title"><?php esc_html_e('SquadUp Modal Title', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[squadup_modal_title]" id="squadup_modal_title" value="<?php echo esc_attr($settings['squadup_modal_title'] ?? 'Pubcrawls Events'); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e('Modal title passed to the SquadUp checkout configuration.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- SquadUp Confirmation URL -->
							<tr>
								<th scope="row">
									<label for="squadup_confirmation_url"><?php esc_html_e('SquadUp Confirmation URL', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="url" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[squadup_confirmation_url]" id="squadup_confirmation_url" value="<?php echo esc_attr($settings['squadup_confirmation_url']); ?>" class="large-text code" />
									<p class="description"><?php esc_html_e('The page users are redirected to after a successful ticket purchase in the SquadUp checkout modal.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- SquadUp SMS Question -->
							<tr>
								<th scope="row">
									<label for="squadup_sms_question"><?php esc_html_e('SquadUp SMS Prompt Question', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[squadup_sms_question]" id="squadup_sms_question" value="<?php echo esc_attr($settings['squadup_sms_question']); ?>" class="large-text" />
									<p class="description"><?php esc_html_e('The order question displayed inside SquadUp checkout requesting SMS phone updates.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- SquadUp SMS Enable -->
							<tr>
								<th scope="row"><?php esc_html_e('SMS Question Prompt', 'pubcrawls-addon'); ?></th>
								<td>
									<label for="squadup_enable_sms">
										<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[squadup_enable_sms]" id="squadup_enable_sms" value="1" <?php checked('1', $settings['squadup_enable_sms']); ?> />
										<?php esc_html_e('Include SMS phone prompt question in SquadUp modal checkout', 'pubcrawls-addon'); ?>
									</label>
								</td>
							</tr>

							<!-- Eventbrite Confirmation URL -->
							<tr>
								<th scope="row">
									<label for="eventbrite_confirmation_url"><?php esc_html_e('Eventbrite Confirmation URL', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="url" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[eventbrite_confirmation_url]" id="eventbrite_confirmation_url" value="<?php echo esc_attr(isset($settings['eventbrite_confirmation_url']) ? $settings['eventbrite_confirmation_url'] : 'https://pubcrawls.com/purchase-confirmation/'); ?>" class="large-text code" />
									<p class="description"><?php esc_html_e('The page users are redirected to after a successful ticket purchase in the Eventbrite checkout modal.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Eventbrite Fallback Behavior -->
							<tr>
								<th scope="row"><?php esc_html_e('Eventbrite Ad-blocker Fallback', 'pubcrawls-addon'); ?></th>
								<td>
									<label for="eventbrite_open_fallback">
										<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[eventbrite_open_fallback]" id="eventbrite_open_fallback" value="1" <?php checked('1', $settings['eventbrite_open_fallback']); ?> />
										<?php esc_html_e('Automatically open Eventbrite URL in new tab if modal script is blocked by user ad-blockers', 'pubcrawls-addon'); ?>
									</label>
								</td>
							</tr>

						</tbody>
					</table>

				</div>
			</div>


	</section>

	<!-- ============================================================ -->
	<!-- TAB: SCHEMA.ORG & MULTI-SITE SEO SETTINGS -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'schema' === $active_tab ? 'is-active' : ''; ?>" id="tab-schema">

			<!-- Card 1: Event Schema (JSON-LD) Configuration -->
			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Schema.org Event Structured Data', 'pubcrawls-addon'); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e('Automatically injects Google-compliant Event JSON-LD structured data into wp_head on single event pages (tribe_events).', 'pubcrawls-addon'); ?></span>
				</div>
				<div class="pubcrawls-card__body">

					<table class="form-table" role="presentation">
						<tbody>
							<!-- Master Event Schema Switch -->
							<tr>
								<th scope="row"><?php esc_html_e('Event Schema Output', 'pubcrawls-addon'); ?></th>
								<td>
									<label class="pubcrawls-switch pubcrawls-switch--standard">
										<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[enable_event_schema]" value="1" <?php checked('1', $settings['enable_event_schema'] ?? '1'); ?> />
										<span class="pubcrawls-slider"></span>
									</label>
									<span style="margin-left: 10px; font-weight: 600; vertical-align: middle;">
										<?php if (('1' === (string) ($settings['enable_event_schema'] ?? '1'))) : ?>
											<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e('Active', 'pubcrawls-addon'); ?></span>
										<?php else : ?>
											<span class="pubcrawls-status-tag" style="background:#f1f5f9;color:#64748b;"><?php esc_html_e('Disabled', 'pubcrawls-addon'); ?></span>
										<?php endif; ?>
									</span>
									<p class="description"><?php esc_html_e('When enabled, valid Schema.org Event JSON-LD tags are automatically generated for search engines and social scrapers.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Organizer Brand Name -->
							<tr>
								<th scope="row">
									<label for="schema_organizer_name"><?php esc_html_e('Organizer Brand Name', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_organizer_name]" id="schema_organizer_name" value="<?php echo esc_attr($settings['schema_organizer_name'] ?? 'PubCrawls'); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e('Name of the event organizer output in organizer.name (e.g. PubCrawls, Bar Crawl Live, City Crawls). Leave empty to use site title.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Organizer Website URL -->
							<tr>
								<th scope="row">
									<label for="schema_organizer_url"><?php esc_html_e('Organizer Website URL', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="url" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_organizer_url]" id="schema_organizer_url" value="<?php echo esc_attr($settings['schema_organizer_url'] ?? 'https://pubcrawls.com/'); ?>" placeholder="<?php echo esc_attr(home_url('/')); ?>" class="regular-text code" />
									<p class="description"><?php esc_html_e('Brand or official website URL output in organizer.url. Leave empty to use the current site home URL.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Organizer Logo URL -->
							<tr>
								<th scope="row">
									<label for="schema_organizer_logo"><?php esc_html_e('Organizer Logo URL', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="url" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_organizer_logo]" id="schema_organizer_logo" value="<?php echo esc_attr($settings['schema_organizer_logo'] ?? 'https://pubcrawls.com/wp-content/uploads/2026/03/Logo.webp'); ?>" placeholder="https://example.com/logo.png" class="large-text code" />
									<p class="description"><?php esc_html_e('Full URL to brand logo image for Schema.org organizer.logo. Leave empty to omit logo.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Currency -->
							<tr>
								<th scope="row">
									<label for="schema_currency"><?php esc_html_e('Ticket Price Currency', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_currency]" id="schema_currency" value="<?php echo esc_attr($settings['schema_currency'] ?? 'USD'); ?>" maxlength="3" style="width: 100px; text-transform: uppercase;" class="regular-text code" />
									<p class="description"><?php esc_html_e('3-letter ISO 4217 currency code for ticket offers (e.g. USD, CAD, GBP, EUR, AUD).', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Default / Fallback Ticket Price -->
							<tr>
								<th scope="row">
									<label for="schema_default_price"><?php esc_html_e('Default Ticket Price (Optional)', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_default_price]" id="schema_default_price" value="<?php echo esc_attr($settings['schema_default_price'] ?? ''); ?>" placeholder="e.g. 20.00" class="regular-text" />
									<p class="description">
										<?php esc_html_e('Because ACF does not have a dedicated price field, offers.price is omitted by default per Google guidelines. Enter a fallback amount (e.g. 20.00) here if you want a default price in your schema, or leave empty to omit price.', 'pubcrawls-addon'); ?>
									</p>
								</td>
							</tr>

							<!-- Venue Country Code -->
							<tr>
								<th scope="row">
									<label for="schema_country_code"><?php esc_html_e('Venue Country Code', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_country_code]" id="schema_country_code" value="<?php echo esc_attr($settings['schema_country_code'] ?? 'US'); ?>" maxlength="2" style="width: 80px; text-transform: uppercase;" class="regular-text code" />
									<p class="description"><?php esc_html_e('2-letter ISO 3166-1 country code for PostalAddress.addressCountry (e.g. US, CA, GB, AU).', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Default Event Status -->
							<tr>
								<th scope="row">
									<label for="schema_event_status"><?php esc_html_e('Default Event Status', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<?php $curr_status = $settings['schema_event_status'] ?? 'https://schema.org/EventScheduled'; ?>
									<select name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_event_status]" id="schema_event_status" class="regular-text">
										<option value="https://schema.org/EventScheduled" <?php selected($curr_status, 'https://schema.org/EventScheduled'); ?>>
											<?php esc_html_e('EventScheduled (Normal)', 'pubcrawls-addon'); ?>
										</option>
										<option value="https://schema.org/EventPostponed" <?php selected($curr_status, 'https://schema.org/EventPostponed'); ?>>
											<?php esc_html_e('EventPostponed', 'pubcrawls-addon'); ?>
										</option>
										<option value="https://schema.org/EventCancelled" <?php selected($curr_status, 'https://schema.org/EventCancelled'); ?>>
											<?php esc_html_e('EventCancelled', 'pubcrawls-addon'); ?>
										</option>
										<option value="https://schema.org/EventMovedOnline" <?php selected($curr_status, 'https://schema.org/EventMovedOnline'); ?>>
											<?php esc_html_e('EventMovedOnline', 'pubcrawls-addon'); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e('Default status value for events. Can also be overridden per-event using the pubcrawls_event_schema_status filter.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Event Attendance Mode -->
							<tr>
								<th scope="row">
									<label for="schema_attendance_mode"><?php esc_html_e('Event Attendance Mode', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<?php $curr_mode = $settings['schema_attendance_mode'] ?? 'https://schema.org/OfflineEventAttendanceMode'; ?>
									<select name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_attendance_mode]" id="schema_attendance_mode" class="regular-text">
										<option value="https://schema.org/OfflineEventAttendanceMode" <?php selected($curr_mode, 'https://schema.org/OfflineEventAttendanceMode'); ?>>
											<?php esc_html_e('OfflineEventAttendanceMode (In-Person)', 'pubcrawls-addon'); ?>
										</option>
										<option value="https://schema.org/OnlineEventAttendanceMode" <?php selected($curr_mode, 'https://schema.org/OnlineEventAttendanceMode'); ?>>
											<?php esc_html_e('OnlineEventAttendanceMode (Virtual)', 'pubcrawls-addon'); ?>
										</option>
										<option value="https://schema.org/MixedEventAttendanceMode" <?php selected($curr_mode, 'https://schema.org/MixedEventAttendanceMode'); ?>>
											<?php esc_html_e('MixedEventAttendanceMode (Hybrid)', 'pubcrawls-addon'); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e('Specifies whether events take place at physical venues, online, or both.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

							<!-- Multi-Date Title Suffix Format -->
							<tr>
								<th scope="row">
									<label for="schema_date_suffix_format"><?php esc_html_e('Multi-Date Title Suffix', 'pubcrawls-addon'); ?></label>
								</th>
								<td>
									<?php $curr_suffix = $settings['schema_date_suffix_format'] ?? 'M j, Y'; ?>
									<select name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[schema_date_suffix_format]" id="schema_date_suffix_format" class="regular-text">
										<option value="M j, Y" <?php selected($curr_suffix, 'M j, Y'); ?>>
											<?php esc_html_e('Oct 24, 2026 (M j, Y)', 'pubcrawls-addon'); ?>
										</option>
										<option value="D, M j, Y" <?php selected($curr_suffix, 'D, M j, Y'); ?>>
											<?php esc_html_e('Sat, Oct 24, 2026 (D, M j, Y)', 'pubcrawls-addon'); ?>
										</option>
										<option value="D, M j" <?php selected($curr_suffix, 'D, M j'); ?>>
											<?php esc_html_e('Sat, Oct 24 (D, M j)', 'pubcrawls-addon'); ?>
										</option>
										<option value="F j, Y" <?php selected($curr_suffix, 'F j, Y'); ?>>
											<?php esc_html_e('October 24, 2026 (F j, Y)', 'pubcrawls-addon'); ?>
										</option>
										<option value="none" <?php selected($curr_suffix, 'none'); ?>>
											<?php esc_html_e('None (Do not append date to title)', 'pubcrawls-addon'); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e('Date formatting applied to event names when an event has multiple date rows.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>

						</tbody>
					</table>

				</div>
			</div>

			<!-- Card 2: FAQ Schema Markup Options -->
			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e('FAQPage Schema Markup', 'pubcrawls-addon'); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e('Global toggle for Schema.org FAQPage JSON-LD generated by FAQ Grid and Page FAQ widgets.', 'pubcrawls-addon'); ?></span>
				</div>
				<div class="pubcrawls-card__body">

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e('Global FAQ Schema', 'pubcrawls-addon'); ?></th>
								<td>
									<label class="pubcrawls-switch pubcrawls-switch--standard">
										<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[enable_faq_schema_global]" value="1" <?php checked('1', $settings['enable_faq_schema_global'] ?? '1'); ?> />
										<span class="pubcrawls-slider"></span>
									</label>
									<span style="margin-left: 10px; font-weight: 600; vertical-align: middle;">
										<?php if (('1' === (string) ($settings['enable_faq_schema_global'] ?? '1'))) : ?>
											<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e('Active', 'pubcrawls-addon'); ?></span>
										<?php else : ?>
											<span class="pubcrawls-status-tag" style="background:#f1f5f9;color:#64748b;"><?php esc_html_e('Disabled', 'pubcrawls-addon'); ?></span>
										<?php endif; ?>
									</span>
									<p class="description"><?php esc_html_e('Master switch for FAQ Schema. When turned off, suppresses FAQPage structured data across all Elementor FAQ widgets site-wide.', 'pubcrawls-addon'); ?></p>
								</td>
							</tr>
						</tbody>
					</table>

				</div>
			</div>

			<!-- Card 3: Multi-site Portability & Developer Reference -->
			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e('Multi-Site & Migration Portability Guide', 'pubcrawls-addon'); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e('How this plugin adapts automatically when installed on different websites.', 'pubcrawls-addon'); ?></span>
				</div>
				<div class="pubcrawls-card__body">
					<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;">
						<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
							<h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-clock" style="color:#2563eb;"></span>
								<?php esc_html_e('Automatic Timezone Matching', 'pubcrawls-addon'); ?>
							</h4>
							<p style="margin:0;font-size:12px;color:#475569;line-height:1.5;">
								<?php esc_html_e('Dates and start/end times are converted to ISO 8601 using wp_timezone(). When moving to a site in another timezone (e.g. New York, London, Sydney), the schema automatically adopts the WordPress site timezone.', 'pubcrawls-addon'); ?>
							</p>
						</div>
						<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
							<h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-money-alt" style="color:#16a34a;"></span>
								<?php esc_html_e('Custom Currency & Pricing Hooks', 'pubcrawls-addon'); ?>
							</h4>
							<p style="margin:0;font-size:12px;color:#475569;line-height:1.5;">
								<?php esc_html_e('Developers can programmatically inject dynamic ticket prices per event or date row using: add_filter(\'pubcrawls_event_schema_price\', function($price, $event_id, $row) { return 25.00; }, 10, 3);', 'pubcrawls-addon'); ?>
							</p>
						</div>
						<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
							<h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-filter" style="color:#ca8a04;"></span>
								<?php esc_html_e('Selective Override Hooks', 'pubcrawls-addon'); ?>
							</h4>
							<p style="margin:0;font-size:12px;color:#475569;line-height:1.5;">
								<?php esc_html_e('Disable output selectively on specific event IDs with pubcrawls_skip_event_schema, or mutate the completed schema array before output with pubcrawls_single_event_schema.', 'pubcrawls-addon'); ?>
							</p>
						</div>
					</div>
				</div>
			</div>


	</section>

	<!-- ============================================================ -->
	<!-- TAB: TRACKING & WEBHOOKS -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'tracking' === $active_tab ? 'is-active' : ''; ?>" id="tab-tracking">

		<?php
		$base_webhook_url   = get_rest_url( null, 'pubcrawls/v1/eventbrite/order-placed' );
		$webhook_secret     = class_exists( 'Pubcrawls_Eventbrite_Webhook' ) ? Pubcrawls_Eventbrite_Webhook::get_webhook_secret() : '';
		$active_webhook_url = ! empty( $webhook_secret ) ? add_query_arg( 'key', $webhook_secret, $base_webhook_url ) : $base_webhook_url;
		$recent_orders      = class_exists( 'Pubcrawls_Tracking_DB' ) ? Pubcrawls_Tracking_DB::get_recent_orders( 50 ) : array();
		$logs               = get_option( 'pubcrawls_eb_webhook_logs', array() );
		$platform_statuses  = class_exists( 'Pubcrawls_Admin' ) ? Pubcrawls_Admin::get_platform_statuses() : array();
		$queue_health       = class_exists( 'Pubcrawls_Eventbrite_Queue' ) ? Pubcrawls_Eventbrite_Queue::get_health_diagnostics() : array();
		$queue_counts       = $queue_health['counts'] ?? array();
		?>

		<!-- Platform Health & Configuration Summary Banner -->
		<div class="pubcrawls-card" style="margin-bottom: 24px;background:#f8fafc;border:1px solid #cbd5e1;">
			<div class="pubcrawls-card__body" style="padding:16px 20px;">
				<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
					<span style="font-weight:700;font-size:13px;text-transform:uppercase;color:#475569;letter-spacing:0.5px;">
						<?php esc_html_e( 'Platform Configuration Status:', 'pubcrawls-addon' ); ?>
					</span>
					<div style="display:flex;gap:12px;flex-wrap:wrap;">
						<?php foreach ( $platform_statuses as $p_key => $p_data ) : ?>
							<div style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;<?php echo $p_data['configured'] ? 'background:#dcfce7;color:#166534;border:1px solid #86efac;' : 'background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;'; ?>">
								<span class="dashicons <?php echo $p_data['configured'] ? 'dashicons-yes' : 'dashicons-no'; ?>" style="font-size:14px;width:14px;height:14px;line-height:14px;"></span>
								<span><?php echo esc_html( $p_data['label'] ); ?></span>
								<?php if ( ! empty( $p_data['is_const'] ) ) : ?>
									<span style="font-size:10px;opacity:0.8;">(wp-config)</span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Card: Queue Health & Diagnostics -->
		<div class="pubcrawls-card" style="margin-bottom: 24px;border:1px solid #e2e8f0;" id="pubcrawls-queue-health-card">
			<div class="pubcrawls-card__header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
				<div>
					<h2 style="display:flex;align-items:center;gap:8px;">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Conversion Queue & Worker Diagnostics', 'pubcrawls-addon' ); ?>
						<?php if ( ! empty( $queue_counts['stale_locks'] ) ) : ?>
							<span style="font-size:12px;background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:12px;font-weight:700;">
								<?php printf( esc_html__( '%d Stale Lock(s)', 'pubcrawls-addon' ), (int) $queue_counts['stale_locks'] ); ?>
							</span>
						<?php elseif ( ! empty( $queue_counts['pending'] ) ) : ?>
							<span style="font-size:12px;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:12px;font-weight:700;">
								<?php printf( esc_html__( '%d Backlog Queued', 'pubcrawls-addon' ), (int) $queue_counts['pending'] ); ?>
							</span>
						<?php else : ?>
							<span style="font-size:12px;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;font-weight:700;">
								<?php esc_html_e( 'Healthy', 'pubcrawls-addon' ); ?>
							</span>
						<?php endif; ?>
					</h2>
					<span class="pubcrawls-card__subtitle">
						<?php esc_html_e( 'Automated background processor with atomic locking, 10-minute stale lock recovery, and WP Engine server cron support.', 'pubcrawls-addon' ); ?>
					</span>
				</div>
				<div style="display:flex;gap:8px;align-items:center;">
					<button type="button" class="button button-primary" id="pubcrawls-process-backlog-btn" style="height:36px;display:flex;align-items:center;gap:6px;">
						<span class="dashicons dashicons-controls-play" style="font-size:16px;width:16px;height:16px;"></span>
						<?php esc_html_e( 'Process Backlog Now', 'pubcrawls-addon' ); ?>
					</button>
					<button type="button" class="button button-secondary" id="pubcrawls-release-stale-btn" style="height:36px;display:flex;align-items:center;gap:6px;">
						<span class="dashicons dashicons-unlock" style="font-size:16px;width:16px;height:16px;"></span>
						<?php esc_html_e( 'Release Stale Locks', 'pubcrawls-addon' ); ?>
					</button>
				</div>
			</div>
			<div class="pubcrawls-card__body">
				<!-- Metrics Summary Tiles -->
				<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:12px;margin-bottom:20px;">
					<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;text-align:center;">
						<div style="font-size:22px;font-weight:700;color:#0f172a;"><?php echo (int) ( $queue_counts['pending'] ?? 0 ); ?></div>
						<div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-top:2px;"><?php esc_html_e( 'Pending / Backlog', 'pubcrawls-addon' ); ?></div>
					</div>
					<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;text-align:center;">
						<div style="font-size:22px;font-weight:700;color:#0284c7;"><?php echo (int) ( $queue_counts['processing_active'] ?? 0 ); ?></div>
						<div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-top:2px;"><?php esc_html_e( 'Active Workers', 'pubcrawls-addon' ); ?></div>
					</div>
					<div style="background:<?php echo ( ! empty( $queue_counts['stale_locks'] ) ) ? '#fef2f2;border:1px solid #fecaca' : '#f8fafc;border:1px solid #e2e8f0'; ?>;border-radius:8px;padding:12px 14px;text-align:center;">
						<div style="font-size:22px;font-weight:700;color:<?php echo ( ! empty( $queue_counts['stale_locks'] ) ) ? '#dc2626' : '#64748b'; ?>;"><?php echo (int) ( $queue_counts['stale_locks'] ?? 0 ); ?></div>
						<div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-top:2px;"><?php esc_html_e( 'Stale Locks', 'pubcrawls-addon' ); ?></div>
					</div>
					<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;text-align:center;">
						<div style="font-size:22px;font-weight:700;color:#16a34a;"><?php echo (int) ( $queue_counts['completed'] ?? 0 ); ?></div>
						<div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-top:2px;"><?php esc_html_e( 'Completed', 'pubcrawls-addon' ); ?></div>
					</div>
					<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;text-align:center;">
						<div style="font-size:22px;font-weight:700;color:#d97706;"><?php echo (int) ( $queue_counts['partial'] ?? 0 ); ?></div>
						<div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-top:2px;"><?php esc_html_e( 'Partial (Retrying)', 'pubcrawls-addon' ); ?></div>
					</div>
					<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;text-align:center;">
						<div style="font-size:22px;font-weight:700;color:#dc2626;"><?php echo (int) ( $queue_counts['failed'] ?? 0 ); ?></div>
						<div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-top:2px;"><?php esc_html_e( 'Failed (Max)', 'pubcrawls-addon' ); ?></div>
					</div>
				</div>

				<!-- Schedule & Runner Status Info -->
				<div style="display:flex;gap:20px;flex-wrap:wrap;background:#f1f5f9;padding:12px 16px;border-radius:6px;font-size:12px;color:#334155;margin-bottom:16px;">
					<div><strong><?php esc_html_e( 'Next Cron Run:', 'pubcrawls-addon' ); ?></strong> <?php echo esc_html( $queue_health['next_cron_human'] ?? 'Unknown' ); ?></div>
					<div><strong><?php esc_html_e( 'Last Worker Run:', 'pubcrawls-addon' ); ?></strong> <?php echo esc_html( $queue_health['last_run_human'] ?? 'Never' ); ?></div>
					<div><strong><?php esc_html_e( 'WP-Cron Disabled (DISABLE_WP_CRON):', 'pubcrawls-addon' ); ?></strong> <?php echo ! empty( $queue_health['wp_cron_disabled'] ) ? '<span style="color:#dc2626;font-weight:600;">' . esc_html__( 'Yes (Requires Server Cron below)', 'pubcrawls-addon' ) . '</span>' : '<span style="color:#16a34a;font-weight:600;">' . esc_html__( 'No (WP-Cron Active)', 'pubcrawls-addon' ) . '</span>'; ?></div>
					<div><strong><?php esc_html_e( 'Action Scheduler:', 'pubcrawls-addon' ); ?></strong> <?php echo ! empty( $queue_health['action_scheduler_active'] ) ? '<span style="color:#16a34a;font-weight:600;">' . esc_html__( 'Active', 'pubcrawls-addon' ) . '</span>' : '<span style="color:#64748b;">' . esc_html__( 'Not Installed', 'pubcrawls-addon' ) . '</span>'; ?></div>
				</div>

				<!-- WP Engine & Server Cron Command -->
				<div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:14px 16px;">
					<label style="display:block;font-size:12px;font-weight:700;color:#1e293b;margin-bottom:6px;">
						<?php esc_html_e( 'Real Server Cron / WP Engine Scheduled Task Command', 'pubcrawls-addon' ); ?>
					</label>
					<div style="display:flex;gap:10px;align-items:center;">
						<input type="text" readonly value="<?php echo esc_attr( $queue_health['server_cron_command'] ?? '' ); ?>" class="large-text code" style="background:#ffffff;font-size:12px;padding:7px 10px;" />
						<button type="button" class="button button-secondary pubcrawls-copy-btn" data-copy="<?php echo esc_attr( $queue_health['server_cron_command'] ?? '' ); ?>" style="white-space:nowrap;">
							<span class="dashicons dashicons-clipboard" style="vertical-align:text-bottom;"></span> <?php esc_html_e( 'Copy Cron Command', 'pubcrawls-addon' ); ?>
						</button>
					</div>
					<p class="description" style="margin-top:6px;font-size:11px;">
						<?php esc_html_e( 'Configure this cron task in WP Engine or Linux crontab. It securely executes the background queue runner every 2 minutes without relying on website visitor traffic.', 'pubcrawls-addon' ); ?>
					</p>
				</div>

				<div id="pubcrawls-queue-action-result" style="display:none;margin-top:14px;"></div>
			</div>
		</div>

			<!-- Card 1: Webhook Endpoint Configuration -->
			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-rest-api"></span> <?php esc_html_e( 'Eventbrite Webhook Endpoint', 'pubcrawls-addon' ); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Configure this endpoint in your Eventbrite Developer account to trigger server-side conversion tracking.', 'pubcrawls-addon' ); ?></span>
				</div>
				<div class="pubcrawls-card__body">

					<div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:16px;margin-bottom:20px;">
						<label style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;color:#475569;margin-bottom:6px;">
							<?php esc_html_e( 'Your Live Eventbrite Webhook URL', 'pubcrawls-addon' ); ?>
						</label>
						<div style="display:flex;gap:10px;align-items:center;">
							<input type="text" readonly value="<?php echo esc_url( $active_webhook_url ); ?>" class="large-text code" id="pubcrawls-webhook-url-input" style="background:#ffffff;font-size:13px;padding:8px 12px;font-weight:600;color:#0f172a;" />
							<button type="button" class="button button-primary pubcrawls-copy-btn" data-copy="<?php echo esc_attr( $active_webhook_url ); ?>" style="white-space:nowrap;padding:4px 14px;height:38px;">
								<span class="dashicons dashicons-clipboard" style="vertical-align:text-bottom;margin-right:4px;"></span><?php esc_html_e( 'Copy URL', 'pubcrawls-addon' ); ?>
							</button>
						</div>
						<p class="description" style="margin-top:8px;">
							<?php
							printf(
								/* translators: %s: Link to Eventbrite Webhooks settings */
								esc_html__( 'Setup: In %s > Webhooks > Add Webhook. Paste this URL, select "All Events" (or specific event), and check the event action "order.placed".', 'pubcrawls-addon' ),
								'<a href="https://www.eventbrite.com/account-settings/webhooks" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Eventbrite Account Settings &gt; Webhooks', 'pubcrawls-addon' ) . '</a>'
							);
							?>
						</p>
					</div>

					<table class="form-table" role="presentation">
						<tbody>
							<!-- Webhook Secret -->
							<tr>
								<th scope="row">
									<label for="eb_webhook_secret"><?php esc_html_e( 'Webhook Secret Key', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_EB_WEBHOOK_SECRET' ) && ! empty( PUBCRAWLS_EB_WEBHOOK_SECRET ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( Pubcrawls_Admin::mask_secret( PUBCRAWLS_EB_WEBHOOK_SECRET ) ); ?>" class="regular-text code" style="background:#f1f5f9;" />
										<span style="margin-left:8px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[eb_webhook_secret]" id="eb_webhook_secret" value="<?php echo esc_attr( $webhook_secret ); ?>" class="regular-text code" placeholder="32+ character secret" />
										<p class="description"><?php esc_html_e( 'Permanent 32+ character secret key. Incoming webhooks without this key in ?key=... are rejected with HTTP 401.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<!-- Enable Server Tracking -->
							<tr>
								<th scope="row"><?php esc_html_e( 'Server-Side Processing', 'pubcrawls-addon' ); ?></th>
								<td>
									<label for="enable_server_tracking">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[enable_server_tracking]" id="enable_server_tracking" value="1" <?php checked( '1', $settings['enable_server_tracking'] ?? '1' ); ?> />
										<?php esc_html_e( 'Master toggle: Enable automated server-side conversion processing for incoming webhooks', 'pubcrawls-addon' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>

				</div>
			</div>

			<!-- Card 2: Eventbrite API Credentials -->
			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-tickets-alt"></span> <?php esc_html_e( 'Eventbrite REST API Credentials', 'pubcrawls-addon' ); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Required to retrieve gross order revenue, currency, event name, and ticket tier breakdown from Eventbrite API.', 'pubcrawls-addon' ); ?></span>
				</div>
				<div class="pubcrawls-card__body">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="eb_api_token"><?php esc_html_e( 'Eventbrite Private API Token', 'pubcrawls-addon' ); ?> <span style="color:#ef4444;">*</span></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_EB_API_TOKEN' ) && ! empty( PUBCRAWLS_EB_API_TOKEN ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( Pubcrawls_Admin::mask_secret( PUBCRAWLS_EB_API_TOKEN ) ); ?>" class="large-text code" style="background:#f1f5f9;" />
										<span style="display:inline-block;margin-top:6px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined via PUBCRAWLS_EB_API_TOKEN in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="password" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[eb_api_token]" id="eb_api_token" value="" class="large-text code" placeholder="<?php echo ! empty( $settings['eb_api_token'] ) ? esc_attr( Pubcrawls_Admin::mask_secret( $settings['eb_api_token'] ) ) : 'Enter Eventbrite Private Token...'; ?>" autocomplete="new-password" />
										<p class="description">
											<?php
											printf(
												/* translators: %s: Link to Eventbrite App Management */
												esc_html__( 'Obtain your Private Token from %s > API Keys > Create API Key / Show Private Token.', 'pubcrawls-addon' ),
												'<a href="https://www.eventbrite.com/account-settings/apps" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Eventbrite App Management', 'pubcrawls-addon' ) . '</a>'
											);
											?>
										</p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="eb_organization_id"><?php esc_html_e( 'PubCrawls Organization ID (Optional)', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_EB_ORG_ID' ) && ! empty( PUBCRAWLS_EB_ORG_ID ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( PUBCRAWLS_EB_ORG_ID ); ?>" class="regular-text code" style="background:#f1f5f9;" />
										<span style="margin-left:8px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined via PUBCRAWLS_EB_ORG_ID in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[eb_organization_id]" id="eb_organization_id" value="<?php echo esc_attr( $settings['eb_organization_id'] ?? '' ); ?>" class="regular-text code" placeholder="e.g. 1234567890" />
										<p class="description"><?php esc_html_e( 'Optional. When set, orders from unauthorized or external Eventbrite organizations are strictly rejected.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Card 3: GA4 Measurement Protocol -->
			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2>
						<span class="dashicons dashicons-chart-area"></span> <?php esc_html_e( 'Google Analytics 4 (GA4) Measurement Protocol', 'pubcrawls-addon' ); ?>
						<?php if ( '1' === (string) ( $settings['ga4_debug_mode'] ?? '0' ) ) : ?>
							<span style="margin-left:12px;font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:4px;border:1px solid #fde68a;">⚠️ <?php esc_html_e( 'DEBUG / TEST MODE ACTIVE (/debug/mp/collect)', 'pubcrawls-addon' ); ?></span>
						<?php endif; ?>
					</h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Sends completed purchase conversions with exact transaction value and ticket breakdown directly to GA4.', 'pubcrawls-addon' ); ?></span>
				</div>
				<div class="pubcrawls-card__body">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Enable GA4 Tracking', 'pubcrawls-addon' ); ?></th>
								<td>
									<label for="enable_ga4_tracking">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[enable_ga4_tracking]" id="enable_ga4_tracking" value="1" <?php checked( '1', $settings['enable_ga4_tracking'] ?? '1' ); ?> />
										<?php esc_html_e( 'Dispatch server-side purchase events to Google Analytics 4', 'pubcrawls-addon' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="ga4_measurement_id"><?php esc_html_e( 'GA4 Measurement ID', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_GA4_MEASUREMENT_ID' ) && ! empty( PUBCRAWLS_GA4_MEASUREMENT_ID ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( PUBCRAWLS_GA4_MEASUREMENT_ID ); ?>" class="regular-text code" style="background:#f1f5f9;" />
										<span style="margin-left:8px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[ga4_measurement_id]" id="ga4_measurement_id" value="<?php echo esc_attr( $settings['ga4_measurement_id'] ?? '' ); ?>" class="regular-text code" placeholder="G-XXXXXXXXXX" />
										<p class="description"><?php esc_html_e( 'Found in Google Analytics 4 > Admin > Data Streams > Web Stream Details.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="ga4_api_secret"><?php esc_html_e( 'GA4 API Secret', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_GA4_API_SECRET' ) && ! empty( PUBCRAWLS_GA4_API_SECRET ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( Pubcrawls_Admin::mask_secret( PUBCRAWLS_GA4_API_SECRET ) ); ?>" class="large-text code" style="background:#f1f5f9;" />
										<span style="display:inline-block;margin-top:6px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="password" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[ga4_api_secret]" id="ga4_api_secret" value="" class="large-text code" placeholder="<?php echo ! empty( $settings['ga4_api_secret'] ) ? esc_attr( Pubcrawls_Admin::mask_secret( $settings['ga4_api_secret'] ) ) : 'Enter GA4 API Secret...'; ?>" autocomplete="new-password" />
										<p class="description"><?php esc_html_e( 'Created in GA4 Admin > Data Streams > select stream > Measurement Protocol API secrets.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'GA4 Debug Mode', 'pubcrawls-addon' ); ?></th>
								<td>
									<label for="ga4_debug_mode">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[ga4_debug_mode]" id="ga4_debug_mode" value="1" <?php checked( '1', $settings['ga4_debug_mode'] ?? '0' ); ?> />
										<?php esc_html_e( 'Send to GA4 validation endpoint (/debug/mp/collect) for live testing', 'pubcrawls-addon' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Card 4: Meta Conversions API (CAPI) -->
			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2>
						<span class="dashicons dashicons-facebook-alt"></span> <?php esc_html_e( 'Meta Conversions API (CAPI)', 'pubcrawls-addon' ); ?>
						<?php if ( ! empty( $settings['meta_test_code'] ) ) : ?>
							<span style="margin-left:12px;font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:4px;border:1px solid #fde68a;">⚠️ <?php
								/* translators: %s: Meta test event code */
								printf( esc_html__( 'TEST MODE ACTIVE (Code: %s)', 'pubcrawls-addon' ), esc_html( $settings['meta_test_code'] ) );
							?></span>
						<?php endif; ?>
					</h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Dispatches server-side Purchase events with SHA-256 hashed customer parameters for ad attribution.', 'pubcrawls-addon' ); ?></span>
				</div>
				<div class="pubcrawls-card__body">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Enable Meta CAPI', 'pubcrawls-addon' ); ?></th>
								<td>
									<label for="enable_meta_tracking">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[enable_meta_tracking]" id="enable_meta_tracking" value="1" <?php checked( '1', $settings['enable_meta_tracking'] ?? '1' ); ?> />
										<?php esc_html_e( 'Dispatch server-side purchase events to Meta Conversions API', 'pubcrawls-addon' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="meta_pixel_id"><?php esc_html_e( 'Meta Pixel / Dataset ID', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_META_PIXEL_ID' ) && ! empty( PUBCRAWLS_META_PIXEL_ID ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( PUBCRAWLS_META_PIXEL_ID ); ?>" class="regular-text code" style="background:#f1f5f9;" />
										<span style="margin-left:8px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[meta_pixel_id]" id="meta_pixel_id" value="<?php echo esc_attr( $settings['meta_pixel_id'] ?? '' ); ?>" class="regular-text code" placeholder="e.g. 123456789012345" />
										<p class="description"><?php esc_html_e( 'Found in Meta Events Manager > Settings > Dataset ID / Pixel ID.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="meta_capi_token"><?php esc_html_e( 'CAPI Access Token', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_META_CAPI_TOKEN' ) && ! empty( PUBCRAWLS_META_CAPI_TOKEN ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( Pubcrawls_Admin::mask_secret( PUBCRAWLS_META_CAPI_TOKEN ) ); ?>" class="large-text code" style="background:#f1f5f9;" />
										<span style="display:inline-block;margin-top:6px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="password" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[meta_capi_token]" id="meta_capi_token" value="" class="large-text code" placeholder="<?php echo ! empty( $settings['meta_capi_token'] ) ? esc_attr( Pubcrawls_Admin::mask_secret( $settings['meta_capi_token'] ) ) : 'Enter Meta CAPI Access Token...'; ?>" autocomplete="new-password" />
										<p class="description"><?php esc_html_e( 'Generated in Meta Events Manager > Settings > Conversions API > Generate access token.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="meta_test_code"><?php esc_html_e( 'Meta Test Event Code (Optional)', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[meta_test_code]" id="meta_test_code" value="<?php echo esc_attr( $settings['meta_test_code'] ?? '' ); ?>" class="regular-text code" placeholder="e.g. TEST12345" />
									<p class="description"><?php esc_html_e( 'Found in Meta Events Manager > Test Events tab. Leave blank for live production tracking.', 'pubcrawls-addon' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Card 5: TikTok Events API -->
			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2>
						<span class="dashicons dashicons-video-alt3"></span> <?php esc_html_e( 'TikTok Events API', 'pubcrawls-addon' ); ?>
						<?php if ( ! empty( $settings['tiktok_test_code'] ) ) : ?>
							<span style="margin-left:12px;font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:4px;border:1px solid #fde68a;">⚠️ <?php
								/* translators: %s: TikTok test event code */
								printf( esc_html__( 'TEST MODE ACTIVE (Code: %s)', 'pubcrawls-addon' ), esc_html( $settings['tiktok_test_code'] ) );
							?></span>
						<?php endif; ?>
					</h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Sends server-side CompletePayment events with SHA-256 hashed customer parameters.', 'pubcrawls-addon' ); ?></span>
				</div>
				<div class="pubcrawls-card__body">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Enable TikTok Tracking', 'pubcrawls-addon' ); ?></th>
								<td>
									<label for="enable_tiktok_tracking">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[enable_tiktok_tracking]" id="enable_tiktok_tracking" value="1" <?php checked( '1', $settings['enable_tiktok_tracking'] ?? '1' ); ?> />
										<?php esc_html_e( 'Dispatch server-side purchase events to TikTok Events API', 'pubcrawls-addon' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="tiktok_pixel_id"><?php esc_html_e( 'TikTok Pixel ID', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_TIKTOK_PIXEL_ID' ) && ! empty( PUBCRAWLS_TIKTOK_PIXEL_ID ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( PUBCRAWLS_TIKTOK_PIXEL_ID ); ?>" class="regular-text code" style="background:#f1f5f9;" />
										<span style="margin-left:8px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[tiktok_pixel_id]" id="tiktok_pixel_id" value="<?php echo esc_attr( $settings['tiktok_pixel_id'] ?? '' ); ?>" class="regular-text code" placeholder="e.g. CXXXXXXXXXXXXXX" />
										<p class="description"><?php esc_html_e( 'Found in TikTok Ads Manager > Assets > Events > Web Events.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="tiktok_access_token"><?php esc_html_e( 'TikTok Access Token', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<?php if ( defined( 'PUBCRAWLS_TIKTOK_ACCESS_TOKEN' ) && ! empty( PUBCRAWLS_TIKTOK_ACCESS_TOKEN ) ) : ?>
										<input type="text" readonly value="<?php echo esc_attr( Pubcrawls_Admin::mask_secret( PUBCRAWLS_TIKTOK_ACCESS_TOKEN ) ); ?>" class="large-text code" style="background:#f1f5f9;" />
										<span style="display:inline-block;margin-top:6px;font-size:12px;color:#16a34a;font-weight:600;"><?php esc_html_e( 'Defined in wp-config.php', 'pubcrawls-addon' ); ?></span>
									<?php else : ?>
										<input type="password" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[tiktok_access_token]" id="tiktok_access_token" value="" class="large-text code" placeholder="<?php echo ! empty( $settings['tiktok_access_token'] ) ? esc_attr( Pubcrawls_Admin::mask_secret( $settings['tiktok_access_token'] ) ) : 'Enter TikTok Access Token...'; ?>" autocomplete="new-password" />
										<p class="description"><?php esc_html_e( 'Generated in TikTok Events Manager > Settings > Events API > Generate Access Token.', 'pubcrawls-addon' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="tiktok_test_code"><?php esc_html_e( 'TikTok Test Event Code (Optional)', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[tiktok_test_code]" id="tiktok_test_code" value="<?php echo esc_attr( $settings['tiktok_test_code'] ?? '' ); ?>" class="regular-text code" placeholder="e.g. TEST..." />
									<p class="description"><?php esc_html_e( 'Found in TikTok Events Manager > Test Events tab. Leave blank for live production tracking.', 'pubcrawls-addon' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Card 6: Order Tester & Simulator -->
		<div class="pubcrawls-card" style="margin-bottom: 24px;">
			<div class="pubcrawls-card__header">
				<h2><span class="dashicons dashicons-hammer"></span> <?php esc_html_e( 'Live Order Simulator & Pipeline Tester', 'pubcrawls-addon' ); ?></h2>
				<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Test the complete pipeline with any existing Eventbrite Order ID to verify API credentials and tracking responses.', 'pubcrawls-addon' ); ?></span>
			</div>
			<div class="pubcrawls-card__body">
				<p style="margin-top:0;color:#475569;">
					<?php esc_html_e( 'Enter a real Eventbrite Order ID (from your Eventbrite Orders list). This test will fetch the order from Eventbrite, extract the revenue, and send test purchase events to your configured platforms.', 'pubcrawls-addon' ); ?>
				</p>
				<div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
					<input type="text" id="pubcrawls-test-order-id" placeholder="Enter Eventbrite Order ID (e.g. 1029384756)" class="regular-text code" style="padding:7px 12px;font-size:14px;min-width:320px;" />
					<button type="button" class="button button-secondary" id="pubcrawls-test-order-btn" style="height:38px;padding:4px 16px;">
						<span class="dashicons dashicons-controls-play" style="vertical-align:text-bottom;margin-right:4px;"></span><?php esc_html_e( 'Test Process Order', 'pubcrawls-addon' ); ?>
					</button>
				</div>
				<div id="pubcrawls-test-order-result" style="display:none;margin-top:16px;"></div>
			</div>
		</div>

		<!-- Card 7: Tracked Orders Database Table -->
		<div class="pubcrawls-card" style="margin-bottom: 24px;">
			<div class="pubcrawls-card__header" style="display:flex;justify-content:space-between;align-items:center;">
				<div>
					<h2><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Tracked Eventbrite Orders (Database Storage)', 'pubcrawls-addon' ); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Persistently stored in custom table with atomic locking, unique order ID constraint, and selective retry support.', 'pubcrawls-addon' ); ?></span>
				</div>
			</div>
			<div class="pubcrawls-card__body">
				<?php if ( empty( $recent_orders ) ) : ?>
					<p style="color:#64748b;font-style:italic;margin:0;">
						<?php esc_html_e( 'No orders tracked yet. Once Eventbrite sends order.placed webhooks or you run a test, tracked records will display here.', 'pubcrawls-addon' ); ?>
					</p>
				<?php else : ?>
					<div style="overflow-x:auto;">
						<table class="wp-list-table widefat fixed striped" style="border:1px solid #e2e8f0;border-radius:6px;">
							<thead>
								<tr>
									<th style="width:145px;"><?php esc_html_e( 'Date / Time', 'pubcrawls-addon' ); ?></th>
									<th style="width:130px;"><?php esc_html_e( 'Order ID', 'pubcrawls-addon' ); ?></th>
									<th style="width:105px;"><?php esc_html_e( 'Revenue', 'pubcrawls-addon' ); ?></th>
									<th style="width:95px;"><?php esc_html_e( 'Overall', 'pubcrawls-addon' ); ?></th>
									<th style="width:115px;"><?php esc_html_e( 'GA4 Delivery', 'pubcrawls-addon' ); ?></th>
									<th style="width:115px;"><?php esc_html_e( 'Meta CAPI', 'pubcrawls-addon' ); ?></th>
									<th style="width:115px;"><?php esc_html_e( 'TikTok Events', 'pubcrawls-addon' ); ?></th>
									<th style="width:90px;"><?php esc_html_e( 'Action', 'pubcrawls-addon' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_orders as $ord ) : ?>
									<tr>
										<td><small><?php echo esc_html( $ord->created_at ); ?></small></td>
										<td>
											<code><?php echo esc_html( $ord->order_id ); ?></code>
											<?php if ( ! empty( $ord->client_id ) ) : ?>
												<br><span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;background:#e0f2fe;color:#0369a1;padding:1px 5px;border-radius:4px;font-weight:600;" title="<?php esc_attr_e( 'Browser attribution captured: client_id and session attached', 'pubcrawls-addon' ); ?>">
													<span class="dashicons dashicons-visibility" style="font-size:12px;width:12px;height:12px;line-height:12px;"></span> <?php esc_html_e( 'Attributed', 'pubcrawls-addon' ); ?>
												</span>
											<?php endif; ?>
											<?php if ( ! empty( $ord->last_error ) ) : ?>
												<br><small style="color:#ef4444;" title="<?php echo esc_attr( $ord->last_error ); ?>"><?php echo esc_html( substr( $ord->last_error, 0, 50 ) ); ?>...</small>
											<?php endif; ?>
										</td>
										<td>
											<strong><?php echo esc_html( $ord->currency ); ?> <?php echo esc_html( number_format( (float) $ord->value, 2 ) ); ?></strong>
										</td>
										<td>
											<?php
											$st = $ord->status ?? 'pending';
											if ( 'completed' === $st ) :
											?>
												<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e( 'Completed', 'pubcrawls-addon' ); ?></span>
											<?php elseif ( 'processing' === $st ) :
												$lock_time = ! empty( $ord->locked_at ) ? strtotime( (string) $ord->locked_at ) : ( ! empty( $ord->updated_at ) ? strtotime( (string) $ord->updated_at ) : 0 );
												$is_stale  = ( time() - $lock_time ) > 600;
											?>
												<?php if ( $is_stale ) : ?>
													<span class="pubcrawls-status-tag" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;" title="<?php esc_attr_e( 'Worker lock has expired (> 10m). Will auto-release on next queue run.', 'pubcrawls-addon' ); ?>">⚠️ <?php esc_html_e( 'Stale Lock', 'pubcrawls-addon' ); ?></span>
												<?php else : ?>
													<span class="pubcrawls-status-tag" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;">⚙️ <?php esc_html_e( 'Processing', 'pubcrawls-addon' ); ?></span>
												<?php endif; ?>
											<?php elseif ( 'partial' === $st ) : ?>
												<span class="pubcrawls-status-tag" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;"><?php esc_html_e( 'Partial', 'pubcrawls-addon' ); ?></span>
											<?php elseif ( 'rejected' === $st ) : ?>
												<span class="pubcrawls-status-tag" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;"><?php esc_html_e( 'Rejected', 'pubcrawls-addon' ); ?></span>
											<?php elseif ( 'pending' === $st ) : ?>
												<span class="pubcrawls-status-tag" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;"><?php esc_html_e( 'Pending', 'pubcrawls-addon' ); ?></span>
											<?php else : ?>
												<span class="pubcrawls-status-tag" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;"><?php esc_html_e( 'Failed', 'pubcrawls-addon' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<?php
											$ga_st   = $ord->ga4_status ?? 'pending';
											$ga_code = isset( $ord->ga4_code ) && null !== $ord->ga4_code ? (int) $ord->ga4_code : null;
											$ga_info = $ga_code ? sprintf( 'Code %d, try %d', $ga_code, (int) $ord->ga4_attempts ) : sprintf( '%d tries', (int) $ord->ga4_attempts );
											if ( 'success' === $ga_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#dcfce7;color:#166534;border-radius:4px;font-weight:600;">Success (' . esc_html( $ga_info ) . ')</span>';
											} elseif ( 'skipped' === $ga_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#f1f5f9;color:#64748b;border-radius:4px;">Skipped</span>';
											} elseif ( 'failed' === $ga_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#fee2e2;color:#991b1b;border-radius:4px;">Failed (' . esc_html( $ga_info ) . ')</span>';
											} else {
												echo '<span style="font-size:11px;padding:2px 6px;background:#e0f2fe;color:#0369a1;border-radius:4px;">Pending</span>';
											}
											?>
										</td>
										<td>
											<?php
											$meta_st   = $ord->meta_status ?? 'pending';
											$meta_code = isset( $ord->meta_code ) && null !== $ord->meta_code ? (int) $ord->meta_code : null;
											$meta_info = $meta_code ? sprintf( 'Code %d, try %d', $meta_code, (int) $ord->meta_attempts ) : sprintf( '%d tries', (int) $ord->meta_attempts );
											if ( 'success' === $meta_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#dcfce7;color:#166534;border-radius:4px;font-weight:600;">Success (' . esc_html( $meta_info ) . ')</span>';
											} elseif ( 'skipped' === $meta_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#f1f5f9;color:#64748b;border-radius:4px;">Skipped</span>';
											} elseif ( 'failed' === $meta_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#fee2e2;color:#991b1b;border-radius:4px;">Failed (' . esc_html( $meta_info ) . ')</span>';
											} else {
												echo '<span style="font-size:11px;padding:2px 6px;background:#e0f2fe;color:#0369a1;border-radius:4px;">Pending</span>';
											}
											?>
										</td>
										<td>
											<?php
											$tt_st   = $ord->tiktok_status ?? 'pending';
											$tt_code = isset( $ord->tiktok_code ) && null !== $ord->tiktok_code ? (int) $ord->tiktok_code : null;
											$tt_info = $tt_code ? sprintf( 'Code %d, try %d', $tt_code, (int) $ord->tiktok_attempts ) : sprintf( '%d tries', (int) $ord->tiktok_attempts );
											if ( 'success' === $tt_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#dcfce7;color:#166534;border-radius:4px;font-weight:600;">Success (' . esc_html( $tt_info ) . ')</span>';
											} elseif ( 'skipped' === $tt_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#f1f5f9;color:#64748b;border-radius:4px;">Skipped</span>';
											} elseif ( 'failed' === $tt_st ) {
												echo '<span style="font-size:11px;padding:2px 6px;background:#fee2e2;color:#991b1b;border-radius:4px;">Failed (' . esc_html( $tt_info ) . ')</span>';
											} else {
												echo '<span style="font-size:11px;padding:2px 6px;background:#e0f2fe;color:#0369a1;border-radius:4px;">Pending</span>';
											}
											?>
										</td>
										<td>
											<?php if ( 'completed' !== $ord->status && 'rejected' !== $ord->status ) : ?>
												<button type="button" class="button button-small pubcrawls-retry-order-btn" data-order-id="<?php echo esc_attr( $ord->order_id ); ?>" title="<?php esc_attr_e( 'Retry only failed platforms for this order', 'pubcrawls-addon' ); ?>">
													<span class="dashicons dashicons-update" style="font-size:12px;width:12px;height:12px;line-height:12px;margin-right:2px;"></span><?php esc_html_e( 'Retry', 'pubcrawls-addon' ); ?>
												</button>
											<?php elseif ( 'rejected' === $ord->status ) : ?>
												<span style="color:#64748b;font-size:12px;">🚫 <?php esc_html_e( 'Rejected', 'pubcrawls-addon' ); ?></span>
											<?php else : ?>
												<span style="color:#16a34a;font-size:12px;">✓ <?php esc_html_e( 'Done', 'pubcrawls-addon' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Card 8: Raw Webhook Activity Log -->
		<div class="pubcrawls-card">
			<div class="pubcrawls-card__header" style="display:flex;justify-content:space-between;align-items:center;">
				<div>
					<h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Recent Webhook Activity & Diagnostic Log', 'pubcrawls-addon' ); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Last 50 raw incoming webhook payloads and dispatch results for debugging.', 'pubcrawls-addon' ); ?></span>
				</div>
				<?php if ( ! empty( $logs ) ) : ?>
					<button type="button" class="button button-link-delete" id="pubcrawls-clear-logs-btn" style="text-decoration:none;">
						<span class="dashicons dashicons-trash" style="vertical-align:text-bottom;"></span> <?php esc_html_e( 'Clear Logs', 'pubcrawls-addon' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<div class="pubcrawls-card__body">
				<?php if ( empty( $logs ) ) : ?>
					<p style="color:#64748b;font-style:italic;margin:0;">
						<?php esc_html_e( 'No diagnostic log entries recorded yet.', 'pubcrawls-addon' ); ?>
					</p>
				<?php else : ?>
					<div style="overflow-x:auto;">
						<table class="wp-list-table widefat fixed striped" style="border:1px solid #e2e8f0;border-radius:6px;">
							<thead>
								<tr>
									<th style="width:150px;"><?php esc_html_e( 'Date & Time', 'pubcrawls-addon' ); ?></th>
									<th style="width:130px;"><?php esc_html_e( 'Order ID', 'pubcrawls-addon' ); ?></th>
									<th><?php esc_html_e( 'Message / Details', 'pubcrawls-addon' ); ?></th>
									<th style="width:110px;"><?php esc_html_e( 'Status', 'pubcrawls-addon' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $logs as $log ) : ?>
									<tr>
										<td><small><?php echo esc_html( $log['timestamp'] ?? 'N/A' ); ?></small></td>
										<td><code><?php echo esc_html( $log['order_id'] ?? 'N/A' ); ?></code></td>
										<td>
											<?php if ( ! empty( $log['event_name'] ) ) : ?>
												<strong><?php echo esc_html( $log['event_name'] ); ?></strong>
											<?php endif; ?>
											<?php if ( ! empty( $log['message'] ) ) : ?>
												<br><small style="color:#475569;"><?php echo esc_html( $log['message'] ); ?></small>
											<?php endif; ?>
											<?php if ( isset( $log['revenue'] ) ) : ?>
												<br><small style="color:#166534;font-weight:600;"><?php echo esc_html( $log['currency'] ?? 'USD' ); ?> <?php echo esc_html( number_format( (float) $log['revenue'], 2 ) ); ?></small>
											<?php endif; ?>
											<?php if ( ! empty( $log['attribution'] ) && is_array( $log['attribution'] ) ) : ?>
												<br><small style="color:#0284c7;font-weight:600;">
													<?php
													printf(
														/* translators: 1: Attribution status, 2: Client ID, 3: Session ID, 4: FBP, 5: FBC, 6: IP, 7: UA */
														esc_html__( 'Attribution: %1$s (Client ID: %2$s, Session: %3$s, FBP: %4$s, FBC: %5$s, IP: %6$s, UA: %7$s)', 'pubcrawls-addon' ),
														esc_html( ucfirst( $log['attribution']['status'] ?? 'N/A' ) ),
														esc_html( $log['attribution']['client_id'] ?? 'absent' ),
														esc_html( $log['attribution']['session_id'] ?? 'absent' ),
														esc_html( $log['attribution']['fbp'] ?? 'absent' ),
														esc_html( $log['attribution']['fbc'] ?? 'absent' ),
														esc_html( $log['attribution']['buyer_ip'] ?? 'absent' ),
														esc_html( $log['attribution']['buyer_ua'] ?? 'absent' )
													);
													?>
												</small>
											<?php endif; ?>
										</td>
										<td>
											<?php
											$st = $log['status'] ?? 'unknown';
											if ( 'success' === $st || 'completed' === $st ) :
											?>
												<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e( 'Success', 'pubcrawls-addon' ); ?></span>
											<?php elseif ( 'skipped' === $st ) : ?>
												<span class="pubcrawls-status-tag" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;"><?php esc_html_e( 'Duplicate', 'pubcrawls-addon' ); ?></span>
											<?php elseif ( 'queued' === $st ) : ?>
												<span class="pubcrawls-status-tag" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;"><?php esc_html_e( 'Queued', 'pubcrawls-addon' ); ?></span>
											<?php else : ?>
												<span class="pubcrawls-status-tag" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;"><?php echo esc_html( ucfirst( $st ) ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 5: HELPERS & TOOLS -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'helpers' === $active_tab ? 'is-active' : ''; ?>" id="tab-helpers">

			<div class="pubcrawls-card" style="margin-bottom: 24px;">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Modular Helper Features & Utilities', 'pubcrawls-addon'); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e('Enable or disable standalone helper features. Disabled helpers are completely unregistered to optimize memory and keep your admin menu clean.', 'pubcrawls-addon'); ?></span>
				</div>
				<div class="pubcrawls-card__body">

					<?php $importer_enabled = ! empty($settings['enable_faq_seo_importer']) && '1' === (string) $settings['enable_faq_seo_importer']; ?>

					<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
						<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
							<div style="display:flex;gap:16px;align-items:flex-start;">
								<div style="width:44px;height:44px;background:#eff6ff;color:#2563eb;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
									<span class="dashicons dashicons-upload" style="font-size:24px;width:24px;height:24px;"></span>
								</div>
								<div>
									<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
										<h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;"><?php esc_html_e('FAQ & SEO Bulk Importer', 'pubcrawls-addon'); ?></h3>
										<?php if ($importer_enabled) : ?>
											<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e('Active', 'pubcrawls-addon'); ?></span>
										<?php else : ?>
											<span class="pubcrawls-status-tag" style="background:#f1f5f9;color:#64748b;"><?php esc_html_e('Disabled', 'pubcrawls-addon'); ?></span>
										<?php endif; ?>
									</div>
									<p style="margin:0 0 14px 0;font-size:13px;color:#475569;line-height:1.5;max-width:700px;">
										<?php esc_html_e('Bulk-fill ACF faqs repeater fields along with Yoast/Rank Math/AIOSEO title & meta description across city and events post types using structured JSON payloads with batching (25 posts/batch), dry-run testing, and direct database counts.', 'pubcrawls-addon'); ?>
									</p>
									<?php if ($importer_enabled) : ?>
										<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
											<a href="<?php echo esc_url(admin_url('admin.php?page=city-faq-import')); ?>" class="button button-primary">
												<span class="dashicons dashicons-location-alt" style="vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e('Open City Importer', 'pubcrawls-addon'); ?>
											</a>
											<a href="<?php echo esc_url(admin_url('admin.php?page=tribe_events-faq-import')); ?>" class="button button-secondary">
												<span class="dashicons dashicons-calendar-alt" style="vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e('Open Events Importer', 'pubcrawls-addon'); ?>
											</a>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<div>
								<label class="pubcrawls-switch pubcrawls-switch--standard" title="<?php esc_attr_e('Toggle Helper Feature', 'pubcrawls-addon'); ?>">
									<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[enable_faq_seo_importer]" value="1" <?php checked(true, $importer_enabled); ?> />
									<span class="pubcrawls-slider"></span>
								</label>
							</div>
						</div>
					</div>

					<?php $title_editor_enabled = ! empty($settings['enable_bulk_title_editor']) && '1' === (string) $settings['enable_bulk_title_editor']; ?>

					<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-top:16px;">
						<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
							<div style="display:flex;gap:16px;align-items:flex-start;">
								<div style="width:44px;height:44px;background:#f0fdf4;color:#16a34a;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
									<span class="dashicons dashicons-editor-textcolor" style="font-size:24px;width:24px;height:24px;"></span>
								</div>
								<div>
									<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
										<h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;"><?php esc_html_e('Bulk Title & Slug Editor', 'pubcrawls-addon'); ?></h3>
										<?php if ($title_editor_enabled) : ?>
											<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e('Active', 'pubcrawls-addon'); ?></span>
										<?php else : ?>
											<span class="pubcrawls-status-tag" style="background:#f1f5f9;color:#64748b;"><?php esc_html_e('Disabled', 'pubcrawls-addon'); ?></span>
										<?php endif; ?>
									</div>
									<p style="margin:0 0 14px 0;font-size:13px;color:#475569;line-height:1.5;max-width:700px;">
										<?php esc_html_e('Adds batch title operations directly to the WordPress post list table bulk actions menu: Append to title (end), Prepend to title (start), Remove from start/end, and Find & Replace. Includes live typing preview, spacing toggle, and optional URL slug synchronization.', 'pubcrawls-addon'); ?>
									</p>
									<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">+ Append</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">+ Prepend</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">- Remove End</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">- Remove Prefix</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">🔄 Find & Replace</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">🔗 Slug Sync</span>
									</div>
									<?php if ($title_editor_enabled) : ?>
										<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
											<a href="<?php echo esc_url(admin_url('edit.php?post_type=tribe_events')); ?>" class="button button-secondary">
												<span class="dashicons dashicons-calendar-alt" style="vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e('Open Events List Table', 'pubcrawls-addon'); ?>
											</a>
											<a href="<?php echo esc_url(admin_url('edit.php?post_type=city')); ?>" class="button button-secondary">
												<span class="dashicons dashicons-location-alt" style="vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e('Open Cities List Table', 'pubcrawls-addon'); ?>
											</a>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<div>
								<label class="pubcrawls-switch pubcrawls-switch--standard" title="<?php esc_attr_e('Toggle Helper Feature', 'pubcrawls-addon'); ?>">
									<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[enable_bulk_title_editor]" value="1" <?php checked(true, $title_editor_enabled); ?> />
									<span class="pubcrawls-slider"></span>
								</label>
							</div>
						</div>
					</div>

					<?php $qefi_enabled = ! empty($settings['enable_quick_featured_image']) && '1' === (string) $settings['enable_quick_featured_image']; ?>

					<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-top:16px;">
						<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
							<div style="display:flex;gap:16px;align-items:flex-start;">
								<div style="width:44px;height:44px;background:#fdf4ff;color:#a855f7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
									<span class="dashicons dashicons-format-image" style="font-size:24px;width:24px;height:24px;"></span>
								</div>
								<div>
									<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
										<h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;"><?php esc_html_e('Quick Edit Featured Image', 'pubcrawls-addon'); ?></h3>
										<?php if ($qefi_enabled) : ?>
											<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e('Active', 'pubcrawls-addon'); ?></span>
										<?php else : ?>
											<span class="pubcrawls-status-tag" style="background:#f1f5f9;color:#64748b;"><?php esc_html_e('Disabled', 'pubcrawls-addon'); ?></span>
										<?php endif; ?>
									</div>
									<p style="margin:0 0 14px 0;font-size:13px;color:#475569;line-height:1.5;max-width:700px;">
										<?php esc_html_e('Displays thumbnail preview column directly in WordPress post tables (Events, Cities, Venues, etc.) and integrates full featured image selection, media library picker, and removal controls directly inside WordPress Quick Edit.', 'pubcrawls-addon'); ?>
									</p>
									<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">🖼️ Table Thumbnail</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">⚡ Quick Edit Picker</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">📁 WP Media Modal</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">🗑️ 1-Click Remove</span>
									</div>
								</div>
							</div>

							<div>
								<label class="pubcrawls-switch pubcrawls-switch--standard" title="<?php esc_attr_e('Toggle Helper Feature', 'pubcrawls-addon'); ?>">
									<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[enable_quick_featured_image]" value="1" <?php checked(true, $qefi_enabled); ?> />
									<span class="pubcrawls-slider"></span>
								</label>
							</div>
						</div>
					</div>

					<?php
					$url_col_enabled = ! empty($settings['enable_post_url_column']) && '1' === (string) $settings['enable_post_url_column'];
					$available_url_pts = class_exists('Pubcrawls_Post_URL_Column')
						? Pubcrawls_Post_URL_Column::get_available_post_types()
						: array(
							'tribe_events' => __('Events', 'pubcrawls-addon'),
							'city'         => __('Cities', 'pubcrawls-addon'),
							'venue'        => __('Venues', 'pubcrawls-addon'),
							'faq'          => __('FAQs', 'pubcrawls-addon'),
							'press'        => __('Press', 'pubcrawls-addon'),
							'city_map'     => __('City Maps', 'pubcrawls-addon'),
							'post'         => __('Posts', 'pubcrawls-addon'),
							'page'         => __('Pages', 'pubcrawls-addon'),
						);
					$enabled_url_pts = ! empty($settings['post_url_column_post_types']) && is_array($settings['post_url_column_post_types'])
						? $settings['post_url_column_post_types']
						: (class_exists('Pubcrawls_Post_URL_Column') ? Pubcrawls_Post_URL_Column::$default_post_types : array('tribe_events', 'city', 'venue', 'post', 'page'));
					?>

					<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-top:16px;">
						<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
							<div style="display:flex;gap:16px;align-items:flex-start;flex:1;min-width:280px;">
								<div style="width:44px;height:44px;background:#ecfdf5;color:#059669;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
									<span class="dashicons dashicons-admin-links" style="font-size:24px;width:24px;height:24px;"></span>
								</div>
								<div style="flex:1;">
									<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
										<h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;"><?php esc_html_e('Post URL Column in Admin Lists', 'pubcrawls-addon'); ?></h3>
										<?php if ($url_col_enabled) : ?>
											<span class="pubcrawls-status-tag pubcrawls-status-tag--ok"><?php esc_html_e('Active', 'pubcrawls-addon'); ?></span>
										<?php else : ?>
											<span class="pubcrawls-status-tag" style="background:#f1f5f9;color:#64748b;"><?php esc_html_e('Disabled', 'pubcrawls-addon'); ?></span>
										<?php endif; ?>
									</div>
									<p style="margin:0 0 14px 0;font-size:13px;color:#475569;line-height:1.5;max-width:700px;">
										<?php esc_html_e('Displays the full clickable permalink URL directly in WordPress admin post list tables (Events, Cities, Venues, Pages, Posts, etc.). Enable or disable the column for specific post types below.', 'pubcrawls-addon'); ?>
									</p>
									<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px;">
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">🔗 Full Permalinks</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">🎯 Per-Post-Type Control</span>
										<span style="font-size:11px;background:#f1f5f9;color:#334155;padding:3px 8px;border-radius:4px;font-weight:600;">🚀 Opens in New Tab</span>
									</div>

									<!-- Per-Post-Type Checkboxes -->
									<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;margin-top:10px;">
										<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
											<span style="font-weight:600;font-size:13px;color:#0f172a;">
												<span class="dashicons dashicons-filter" style="vertical-align:middle;margin-top:-2px;font-size:16px;width:16px;height:16px;color:#64748b;"></span>
												<?php esc_html_e('Enable URL Column on specific post types:', 'pubcrawls-addon'); ?>
											</span>
											<div style="display:flex;gap:6px;">
												<button type="button" class="button button-small" id="pubcrawls-select-all-url-pts"><?php esc_html_e('Select All', 'pubcrawls-addon'); ?></button>
												<button type="button" class="button button-small" id="pubcrawls-deselect-all-url-pts"><?php esc_html_e('Deselect All', 'pubcrawls-addon'); ?></button>
											</div>
										</div>
										<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));gap:10px;">
											<?php foreach ($available_url_pts as $pt_slug => $pt_label) :
												$is_pt_checked = in_array($pt_slug, $enabled_url_pts, true);
											?>
												<label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#334155;background:#ffffff;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;">
													<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[post_url_column_post_types][]" value="<?php echo esc_attr($pt_slug); ?>" <?php checked(true, $is_pt_checked); ?> class="pubcrawls-url-pt-checkbox" />
													<span><strong><?php echo esc_html($pt_label); ?></strong> <code style="font-size:10px;color:#64748b;"><?php echo esc_html($pt_slug); ?></code></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>

								</div>
							</div>

							<div>
								<label class="pubcrawls-switch pubcrawls-switch--standard" title="<?php esc_attr_e('Toggle Helper Feature', 'pubcrawls-addon'); ?>">
									<input type="checkbox" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[enable_post_url_column]" value="1" <?php checked(true, $url_col_enabled); ?> />
									<span class="pubcrawls-slider"></span>
								</label>
							</div>
						</div>
					</div>

				</div>
			</div>

			<!-- Multi-site Portability Notice Card -->
			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e('Multi-Site & Migration Portability', 'pubcrawls-addon'); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e('Architecture details for installing this plugin across different WordPress environments.', 'pubcrawls-addon'); ?></span>
				</div>
				<div class="pubcrawls-card__body">
					<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;">
						<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
							<h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-randomize" style="color:#2563eb;"></span>
								<?php esc_html_e('Dynamic ACF Key Discovery', 'pubcrawls-addon'); ?>
							</h4>
							<p style="margin:0;font-size:12px;color:#475569;line-height:1.5;">
								<?php esc_html_e('On new sites where ACF field keys differ from the original database, the importer dynamically queries acf_get_field("faqs") to discover live field keys with graceful fallback to field names.', 'pubcrawls-addon'); ?>
							</p>
						</div>
						<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
							<h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-search" style="color:#ca8a04;"></span>
								<?php esc_html_e('Universal SEO Compatibility', 'pubcrawls-addon'); ?>
							</h4>
							<p style="margin:0;font-size:12px;color:#475569;line-height:1.5;">
								<?php esc_html_e('Seamlessly writes to Yoast SEO, Rank Math, All In One SEO, and SEOPress postmeta. Whatever SEO plugin is active on the target site will receive the imported title and description.', 'pubcrawls-addon'); ?>
							</p>
						</div>
						<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
							<h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-filter" style="color:#16a34a;"></span>
								<?php esc_html_e('Post Type Detection & Filter', 'pubcrawls-addon'); ?>
							</h4>
							<p style="margin:0;font-size:12px;color:#475569;line-height:1.5;">
								<?php esc_html_e('Verifies post_type_exists() before querying or registering admin pages. You can also filter supported post types via the pubcrawls_faq_importer_post_types hook.', 'pubcrawls-addon'); ?>
							</p>
						</div>
					</div>
				</div>
			</div>


	</section>

				<!-- Single Save bar for every tab -->
				<div class="pubcrawls-savebar" id="pubcrawls-savebar">
					<span class="pubcrawls-savebar__status" id="pubcrawls-save-status" role="status" aria-live="polite">
						<span class="dashicons dashicons-yes-alt"></span> <span class="pubcrawls-savebar__text"><?php esc_html_e('All changes saved', 'pubcrawls-addon'); ?></span>
					</span>
					<button type="submit" class="button button-primary button-large" id="pubcrawls-save-btn"><?php esc_html_e('Save All Changes', 'pubcrawls-addon'); ?></button>
				</div>

			</div><!-- /.pubcrawls-main -->
		</div><!-- /.pubcrawls-layout -->
	</form>

</div>
