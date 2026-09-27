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

	<!-- Header Banner -->
	<header class="pubcrawls-header">
		<div class="pubcrawls-header__logo">
			<span class="dashicons dashicons-tickets-alt"></span>
			<div>
				<h1><?php esc_html_e('PubCrawls', 'pubcrawls-addon'); ?></h1>
				<span class="pubcrawls-version">v<?php echo esc_html(PUBCRAWLS_ADDON_VERSION); ?></span>
			</div>
		</div>
		<div class="pubcrawls-header__meta">
			<span class="pubcrawls-badge pubcrawls-badge--elementor">
				<i class="dashicons dashicons-welcome-widgets-menus"></i> <?php echo esc_html(count($widgets) - count($disabled_w)); ?>/<?php echo esc_html(count($widgets)); ?> <?php esc_html_e('Widgets Active', 'pubcrawls-addon'); ?>
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

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$settings_updated = isset($_GET['settings-updated']) && 'true' === $_GET['settings-updated'];
	if ($settings_updated) :
	?>
		<div class="notice notice-success is-dismissible pubcrawls-notice">
			<p><strong><?php esc_html_e('Settings saved successfully.', 'pubcrawls-addon'); ?></strong></p>
		</div>
	<?php endif; ?>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper pubcrawls-tabs" id="pubcrawls-tab-nav">
		<a href="?page=pubcrawls-addon&tab=overview" class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="overview">
			<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e('Overview & Health', 'pubcrawls-addon'); ?>
		</a>
		<a href="?page=pubcrawls-addon&tab=post-types" class="nav-tab <?php echo 'post-types' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="post-types">
			<span class="dashicons dashicons-category"></span> <?php esc_html_e('Post Types & Taxonomies', 'pubcrawls-addon'); ?>
			<span class="pubcrawls-tab-count"><?php echo esc_html(count($post_types) - count($disabled_pt)); ?>/<?php echo esc_html(count($post_types)); ?></span>
		</a>
		<a href="?page=pubcrawls-addon&tab=widgets" class="nav-tab <?php echo 'widgets' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="widgets">
			<span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e('Widgets Manager', 'pubcrawls-addon'); ?>
			<span class="pubcrawls-tab-count"><?php echo esc_html(count($widgets) - count($disabled_w)); ?>/<?php echo esc_html(count($widgets)); ?></span>
		</a>
		<a href="?page=pubcrawls-addon&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="settings">
			<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Ticket Settings', 'pubcrawls-addon'); ?>
		</a>
		<a href="?page=pubcrawls-addon&tab=helpers" class="nav-tab <?php echo 'helpers' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="helpers">
			<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Helpers & Tools', 'pubcrawls-addon'); ?>
			<?php
			$active_helpers_count = (! empty($settings['enable_faq_seo_importer']) && '1' === (string) $settings['enable_faq_seo_importer'] ? 1 : 0)
				+ (! empty($settings['enable_bulk_title_editor']) && '1' === (string) $settings['enable_bulk_title_editor'] ? 1 : 0)
				+ (! empty($settings['enable_quick_featured_image']) && '1' === (string) $settings['enable_quick_featured_image'] ? 1 : 0)
				+ (! empty($settings['enable_post_url_column']) && '1' === (string) $settings['enable_post_url_column'] ? 1 : 0);
			if ($active_helpers_count > 0) : ?>
				<span class="pubcrawls-tab-count" style="background:#22c55e;color:#fff;"><?php echo esc_html($active_helpers_count); ?></span>
			<?php endif; ?>
		</a>
	</nav>

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
					<a href="?page=pubcrawls-addon&tab=post-types" class="button button-secondary"><?php esc_html_e('Configure Post Types', 'pubcrawls-addon'); ?> &rarr;</a>
				</div>
			</div>

			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h3><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e('Widgets Manager', 'pubcrawls-addon'); ?></h3>
				</div>
				<div class="pubcrawls-card__body">
					<p><?php esc_html_e('Toggle off unused Elementor widgets to keep the editor panel tidy and boost editor loading speeds.', 'pubcrawls-addon'); ?></p>
					<a href="?page=pubcrawls-addon&tab=widgets" class="button button-secondary"><?php esc_html_e('Configure Widgets', 'pubcrawls-addon'); ?> &rarr;</a>
				</div>
			</div>
		</div>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 2: POST TYPES & TAXONOMIES -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'post-types' === $active_tab ? 'is-active' : ''; ?>" id="tab-post-types">

		<form method="post" action="options.php">
			<?php settings_fields('pubcrawls_settings_group'); ?>

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
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="pubcrawls-card__footer">
					<?php submit_button(esc_html__('Save Post Types & Taxonomies', 'pubcrawls-addon'), 'primary', 'submit', false); ?>
				</div>
			</div>

		</form>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 3: WIDGETS MANAGER -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'widgets' === $active_tab ? 'is-active' : ''; ?>" id="tab-widgets">

		<form method="post" action="options.php">
			<?php settings_fields('pubcrawls_settings_group'); ?>

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
				<div class="pubcrawls-card__footer">
					<?php submit_button(esc_html__('Save Widget Preferences', 'pubcrawls-addon'), 'primary', 'submit', false); ?>
				</div>
			</div>

		</form>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 4: TICKET SETTINGS -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>" id="tab-settings">

		<form method="post" action="options.php">
			<?php settings_fields('pubcrawls_settings_group'); ?>
			<input type="hidden" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[ticket_settings_submitted]" value="1" />

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
				<div class="pubcrawls-card__footer">
					<?php submit_button(esc_html__('Save Ticket Settings', 'pubcrawls-addon'), 'primary', 'submit', false); ?>
				</div>
			</div>

		</form>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 5: HELPERS & TOOLS -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'helpers' === $active_tab ? 'is-active' : ''; ?>" id="tab-helpers">

		<form method="post" action="options.php">
			<?php settings_fields('pubcrawls_settings_group'); ?>
			<input type="hidden" name="<?php echo esc_attr(Pubcrawls_Admin::SETTINGS_KEY); ?>[helpers_settings_submitted]" value="1" />

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
				<div class="pubcrawls-card__footer">
					<?php submit_button(esc_html__('Save Helpers Settings', 'pubcrawls-addon'), 'primary', 'submit', false); ?>
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

		</form>

	</section>

</div>