<?php
/**
 * PubCrawls Admin Dashboard View Template
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
$health     = Pubcrawls_Admin::get_system_health();
$counts     = Pubcrawls_Admin::get_overview_counts();
$post_types = Pubcrawls_Admin::get_post_types_manifest();
$taxonomies = Pubcrawls_Admin::get_taxonomies_manifest();
$widgets    = Pubcrawls_Admin::get_widget_manifest();
$settings   = get_option( Pubcrawls_Admin::SETTINGS_KEY, Pubcrawls_Admin::get_default_settings() );

$disabled_w = get_option( Pubcrawls_Admin::WIDGETS_KEY, array() );
if ( ! is_array( $disabled_w ) ) {
	$disabled_w = array();
}

$disabled_pt = get_option( Pubcrawls_Admin::POST_TYPES_KEY, array() );
if ( ! is_array( $disabled_pt ) ) {
	$disabled_pt = array();
}

$disabled_tax = get_option( Pubcrawls_Admin::TAXONOMIES_KEY, array() );
if ( ! is_array( $disabled_tax ) ) {
	$disabled_tax = array();
}
?>

<div class="wrap pubcrawls-dashboard-wrap">

	<!-- Header Banner -->
	<header class="pubcrawls-header">
		<div class="pubcrawls-header__logo">
			<span class="dashicons dashicons-tickets-alt"></span>
			<div>
				<h1><?php esc_html_e( 'PubCrawls Addon', 'pubcrawls-addon' ); ?></h1>
				<span class="pubcrawls-version">v<?php echo esc_html( PUBCRAWLS_ADDON_VERSION ); ?></span>
			</div>
		</div>
		<div class="pubcrawls-header__meta">
			<span class="pubcrawls-badge pubcrawls-badge--elementor">
				<i class="dashicons dashicons-welcome-widgets-menus"></i> <?php echo esc_html( count( $widgets ) - count( $disabled_w ) ); ?>/<?php echo esc_html( count( $widgets ) ); ?> <?php esc_html_e( 'Widgets Active', 'pubcrawls-addon' ); ?>
			</span>
			<?php if ( ! in_array( 'tribe_events', $disabled_pt, true ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tribe_events' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Events', 'pubcrawls-addon' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! in_array( 'city', $disabled_pt, true ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=city' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-location-alt"></span> <?php esc_html_e( 'Cities', 'pubcrawls-addon' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</header>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$settings_updated = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];
	if ( $settings_updated ) :
		?>
		<div class="notice notice-success is-dismissible pubcrawls-notice">
			<p><strong><?php esc_html_e( 'Settings saved successfully.', 'pubcrawls-addon' ); ?></strong></p>
		</div>
	<?php endif; ?>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper pubcrawls-tabs" id="pubcrawls-tab-nav">
		<a href="?page=pubcrawls-addon&tab=overview" class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="overview">
			<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Overview & Health', 'pubcrawls-addon' ); ?>
		</a>
		<a href="?page=pubcrawls-addon&tab=post-types" class="nav-tab <?php echo 'post-types' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="post-types">
			<span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Post Types & Taxonomies', 'pubcrawls-addon' ); ?>
			<span class="pubcrawls-tab-count"><?php echo esc_html( count( $post_types ) - count( $disabled_pt ) ); ?>/<?php echo esc_html( count( $post_types ) ); ?></span>
		</a>
		<a href="?page=pubcrawls-addon&tab=widgets" class="nav-tab <?php echo 'widgets' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="widgets">
			<span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Widgets Manager', 'pubcrawls-addon' ); ?>
			<span class="pubcrawls-tab-count"><?php echo esc_html( count( $widgets ) - count( $disabled_w ) ); ?>/<?php echo esc_html( count( $widgets ) ); ?></span>
		</a>
		<a href="?page=pubcrawls-addon&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="settings">
			<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Ticket Settings', 'pubcrawls-addon' ); ?>
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
					<span class="pubcrawls-stat-num"><?php echo esc_html( $counts['total_cities'] ); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e( 'Published Cities', 'pubcrawls-addon' ); ?></span>
				</div>
				<?php if ( ! in_array( 'city', $disabled_pt, true ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=city' ) ); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add City', 'pubcrawls-addon' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="pubcrawls-stat-card">
				<div class="pubcrawls-stat-icon pubcrawls-stat-icon--event">
					<span class="dashicons dashicons-calendar-alt"></span>
				</div>
				<div class="pubcrawls-stat-data">
					<span class="pubcrawls-stat-num"><?php echo esc_html( $counts['total_events'] ); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e( 'Events Listed', 'pubcrawls-addon' ); ?></span>
				</div>
				<?php if ( ! in_array( 'tribe_events', $disabled_pt, true ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=tribe_events' ) ); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Event', 'pubcrawls-addon' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="pubcrawls-stat-card">
				<div class="pubcrawls-stat-icon pubcrawls-stat-icon--venue">
					<span class="dashicons dashicons-store"></span>
				</div>
				<div class="pubcrawls-stat-data">
					<span class="pubcrawls-stat-num"><?php echo esc_html( $counts['total_venues'] ); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e( 'Participating Venues', 'pubcrawls-addon' ); ?></span>
				</div>
				<?php if ( ! in_array( 'venue', $disabled_pt, true ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=venue' ) ); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Venue', 'pubcrawls-addon' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="pubcrawls-stat-card">
				<div class="pubcrawls-stat-icon pubcrawls-stat-icon--faq">
					<span class="dashicons dashicons-editor-help"></span>
				</div>
				<div class="pubcrawls-stat-data">
					<span class="pubcrawls-stat-num"><?php echo esc_html( $counts['total_faqs'] ); ?></span>
					<span class="pubcrawls-stat-label"><?php esc_html_e( 'Published FAQs', 'pubcrawls-addon' ); ?></span>
				</div>
				<?php if ( ! in_array( 'faq', $disabled_pt, true ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=faq' ) ); ?>" class="pubcrawls-stat-action">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add FAQ', 'pubcrawls-addon' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- System Diagnostics -->
		<div class="pubcrawls-card">
			<div class="pubcrawls-card__header">
				<h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'System Integration Health', 'pubcrawls-addon' ); ?></h2>
				<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Real-time check of all dependencies and required environments.', 'pubcrawls-addon' ); ?></span>
			</div>
			<div class="pubcrawls-card__body">
				<table class="widefat pubcrawls-health-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Component', 'pubcrawls-addon' ); ?></th>
							<th><?php esc_html_e( 'Status', 'pubcrawls-addon' ); ?></th>
							<th><?php esc_html_e( 'Version / State', 'pubcrawls-addon' ); ?></th>
							<th><?php esc_html_e( 'Details', 'pubcrawls-addon' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $health as $comp ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $comp['label'] ); ?></strong></td>
								<td>
									<?php if ( 'ok' === $comp['status'] ) : ?>
										<span class="pubcrawls-status-tag pubcrawls-status-tag--ok">
											<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Operational', 'pubcrawls-addon' ); ?>
										</span>
									<?php elseif ( 'warning' === $comp['status'] ) : ?>
										<span class="pubcrawls-status-tag pubcrawls-status-tag--warning">
											<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Notice', 'pubcrawls-addon' ); ?>
										</span>
									<?php else : ?>
										<span class="pubcrawls-status-tag pubcrawls-status-tag--error">
											<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Missing / Inactive', 'pubcrawls-addon' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td><code><?php echo esc_html( $comp['version'] ); ?></code></td>
								<td class="description"><?php echo esc_html( $comp['note'] ); ?></td>
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
					<h3><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Post Types & Taxonomies', 'pubcrawls-addon' ); ?></h3>
				</div>
				<div class="pubcrawls-card__body">
					<p><?php esc_html_e( 'Manage custom post types (Cities, Events, Venues, FAQs, Press, City Maps) and taxonomies directly from dashboard toggles.', 'pubcrawls-addon' ); ?></p>
					<a href="?page=pubcrawls-addon&tab=post-types" class="button button-secondary"><?php esc_html_e( 'Configure Post Types', 'pubcrawls-addon' ); ?> &rarr;</a>
				</div>
			</div>

			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h3><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Widgets Manager', 'pubcrawls-addon' ); ?></h3>
				</div>
				<div class="pubcrawls-card__body">
					<p><?php esc_html_e( 'Toggle off unused Elementor widgets to keep the editor panel tidy and boost editor loading speeds.', 'pubcrawls-addon' ); ?></p>
					<a href="?page=pubcrawls-addon&tab=widgets" class="button button-secondary"><?php esc_html_e( 'Configure Widgets', 'pubcrawls-addon' ); ?> &rarr;</a>
				</div>
			</div>
		</div>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 2: POST TYPES & TAXONOMIES -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'post-types' === $active_tab ? 'is-active' : ''; ?>" id="tab-post-types">
		
		<form method="post" action="options.php">
			<?php settings_fields( 'pubcrawls_settings_group' ); ?>

			<!-- Custom Post Types Section -->
			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header pubcrawls-card__header--flex">
					<div>
						<h2><span class="dashicons dashicons-admin-post"></span> <?php esc_html_e( 'Custom Post Types', 'pubcrawls-addon' ); ?></h2>
						<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Enable or disable custom post types registered by the plugin.', 'pubcrawls-addon' ); ?></span>
					</div>
					<div class="pubcrawls-bulk-actions">
						<button type="button" class="button" id="pubcrawls-enable-all-pt"><?php esc_html_e( 'Enable All', 'pubcrawls-addon' ); ?></button>
						<button type="button" class="button" id="pubcrawls-disable-all-pt"><?php esc_html_e( 'Disable All', 'pubcrawls-addon' ); ?></button>
					</div>
				</div>

				<div class="pubcrawls-card__body">
					<div class="pubcrawls-widgets-grid">
						<?php
						foreach ( $post_types as $slug => $pt ) :
							$is_disabled = in_array( $slug, $disabled_pt, true );
							?>
							<div class="pubcrawls-widget-card <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>">
								<div class="pubcrawls-widget-card__top">
									<div class="pubcrawls-widget-card__icon">
										<span class="dashicons <?php echo esc_attr( $pt['icon'] ); ?>"></span>
									</div>
									<label class="pubcrawls-switch">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::POST_TYPES_KEY ); ?>[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_disabled ); ?> class="pubcrawls-pt-toggle" />
										<span class="pubcrawls-slider"></span>
									</label>
								</div>
								
								<h3 class="pubcrawls-widget-card__title"><?php echo esc_html( $pt['title'] ); ?></h3>
								<p class="pubcrawls-widget-card__desc"><?php echo esc_html( $pt['description'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Custom Taxonomies Section -->
			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header pubcrawls-card__header--flex">
					<div>
						<h2><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Custom Taxonomies', 'pubcrawls-addon' ); ?></h2>
						<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Enable or disable custom taxonomies registered by the plugin.', 'pubcrawls-addon' ); ?></span>
					</div>
					<div class="pubcrawls-bulk-actions">
						<button type="button" class="button" id="pubcrawls-enable-all-tax"><?php esc_html_e( 'Enable All', 'pubcrawls-addon' ); ?></button>
						<button type="button" class="button" id="pubcrawls-disable-all-tax"><?php esc_html_e( 'Disable All', 'pubcrawls-addon' ); ?></button>
					</div>
				</div>

				<div class="pubcrawls-card__body">
					<div class="pubcrawls-widgets-grid">
						<?php
						foreach ( $taxonomies as $slug => $tax_item ) :
							$is_disabled = in_array( $slug, $disabled_tax, true );
							?>
							<div class="pubcrawls-widget-card <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>">
								<div class="pubcrawls-widget-card__top">
									<div class="pubcrawls-widget-card__icon">
										<span class="dashicons <?php echo esc_attr( $tax_item['icon'] ); ?>"></span>
									</div>
									<label class="pubcrawls-switch">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::TAXONOMIES_KEY ); ?>[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_disabled ); ?> class="pubcrawls-tax-toggle" />
										<span class="pubcrawls-slider"></span>
									</label>
								</div>
								
								<h3 class="pubcrawls-widget-card__title"><?php echo esc_html( $tax_item['title'] ); ?></h3>
								<p class="pubcrawls-widget-card__desc"><?php echo esc_html( $tax_item['description'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				
				<div class="pubcrawls-card__footer">
					<?php submit_button( esc_html__( 'Save Post Types & Taxonomies', 'pubcrawls-addon' ), 'primary', 'submit', false ); ?>
				</div>
			</div>

		</form>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 3: WIDGETS MANAGER -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'widgets' === $active_tab ? 'is-active' : ''; ?>" id="tab-widgets">
		
		<form method="post" action="options.php">
			<?php settings_fields( 'pubcrawls_settings_group' ); ?>

			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header pubcrawls-card__header--flex">
					<div>
						<h2><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Elementor Widgets Manager', 'pubcrawls-addon' ); ?></h2>
						<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Toggle off widgets you are not using to speed up Elementor editor load times and keep your widgets panel tidy.', 'pubcrawls-addon' ); ?></span>
					</div>
					<div class="pubcrawls-bulk-actions">
						<button type="button" class="button" id="pubcrawls-enable-all"><?php esc_html_e( 'Enable All', 'pubcrawls-addon' ); ?></button>
						<button type="button" class="button" id="pubcrawls-disable-all"><?php esc_html_e( 'Disable All', 'pubcrawls-addon' ); ?></button>
					</div>
				</div>

				<div class="pubcrawls-card__body">
					
					<div class="pubcrawls-widgets-grid">
						<?php
						foreach ( $widgets as $slug => $widget ) :
							$is_disabled = in_array( $slug, $disabled_w, true );
							?>
							<div class="pubcrawls-widget-card <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>">
								<div class="pubcrawls-widget-card__top">
									<div class="pubcrawls-widget-card__icon">
										<span class="dashicons dashicons-screenoptions"></span>
									</div>
									<label class="pubcrawls-switch">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::WIDGETS_KEY ); ?>[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_disabled ); ?> class="pubcrawls-widget-toggle" />
										<span class="pubcrawls-slider"></span>
									</label>
								</div>
								
								<h3 class="pubcrawls-widget-card__title"><?php echo esc_html( $widget['title'] ); ?></h3>
								<p class="pubcrawls-widget-card__desc"><?php echo esc_html( $widget['description'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>

				</div>
				<div class="pubcrawls-card__footer">
					<?php submit_button( esc_html__( 'Save Widget Preferences', 'pubcrawls-addon' ), 'primary', 'submit', false ); ?>
				</div>
			</div>

		</form>

	</section>

	<!-- ============================================================ -->
	<!-- TAB 4: TICKET SETTINGS -->
	<!-- ============================================================ -->
	<section class="pubcrawls-tab-pane <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>" id="tab-settings">
		
		<form method="post" action="options.php">
			<?php settings_fields( 'pubcrawls_settings_group' ); ?>

			<div class="pubcrawls-card">
				<div class="pubcrawls-card__header">
					<h2><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Ticket Provider & Checkout Options', 'pubcrawls-addon' ); ?></h2>
					<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Configure default checkout behavior and SquadUp checkout parameters.', 'pubcrawls-addon' ); ?></span>
				</div>
				<div class="pubcrawls-card__body">
					
					<table class="form-table" role="presentation">
						<tbody>
							<!-- Default Ticket Provider -->
							<tr>
								<th scope="row">
									<label for="default_ticket_provider"><?php esc_html_e( 'Default Ticket Provider', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<select name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[default_ticket_provider]" id="default_ticket_provider" class="regular-text">
										<option value="squadup" <?php selected( $settings['default_ticket_provider'], 'squadup' ); ?>>
											<?php esc_html_e( 'SquadUp (Modal Checkout)', 'pubcrawls-addon' ); ?>
										</option>
										<option value="eventbrite" <?php selected( $settings['default_ticket_provider'], 'eventbrite' ); ?>>
											<?php esc_html_e( 'Eventbrite (Widget Modal)', 'pubcrawls-addon' ); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e( 'Default fallback ticket provider when an individual event date does not specify one.', 'pubcrawls-addon' ); ?></p>
								</td>
							</tr>

							<!-- SquadUp Confirmation URL -->
							<tr>
								<th scope="row">
									<label for="squadup_confirmation_url"><?php esc_html_e( 'SquadUp Confirmation URL', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<input type="url" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[squadup_confirmation_url]" id="squadup_confirmation_url" value="<?php echo esc_attr( $settings['squadup_confirmation_url'] ); ?>" class="large-text code" />
									<p class="description"><?php esc_html_e( 'The page users are redirected to after a successful ticket purchase in the SquadUp checkout modal.', 'pubcrawls-addon' ); ?></p>
								</td>
							</tr>

							<!-- SquadUp SMS Question -->
							<tr>
								<th scope="row">
									<label for="squadup_sms_question"><?php esc_html_e( 'SquadUp SMS Prompt Question', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<input type="text" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[squadup_sms_question]" id="squadup_sms_question" value="<?php echo esc_attr( $settings['squadup_sms_question'] ); ?>" class="large-text" />
									<p class="description"><?php esc_html_e( 'The order question displayed inside SquadUp checkout requesting SMS phone updates.', 'pubcrawls-addon' ); ?></p>
								</td>
							</tr>

							<!-- SquadUp SMS Enable -->
							<tr>
								<th scope="row"><?php esc_html_e( 'SMS Question Prompt', 'pubcrawls-addon' ); ?></th>
								<td>
									<label for="squadup_enable_sms">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[squadup_enable_sms]" id="squadup_enable_sms" value="1" <?php checked( '1', $settings['squadup_enable_sms'] ); ?> />
										<?php esc_html_e( 'Include SMS phone prompt question in SquadUp modal checkout', 'pubcrawls-addon' ); ?>
									</label>
								</td>
							</tr>

							<!-- Eventbrite Confirmation URL -->
							<tr>
								<th scope="row">
									<label for="eventbrite_confirmation_url"><?php esc_html_e( 'Eventbrite Confirmation URL', 'pubcrawls-addon' ); ?></label>
								</th>
								<td>
									<input type="url" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[eventbrite_confirmation_url]" id="eventbrite_confirmation_url" value="<?php echo esc_attr( isset( $settings['eventbrite_confirmation_url'] ) ? $settings['eventbrite_confirmation_url'] : 'https://pubcrawls.com/purchase-confirmation/' ); ?>" class="large-text code" />
									<p class="description"><?php esc_html_e( 'The page users are redirected to after a successful ticket purchase in the Eventbrite checkout modal.', 'pubcrawls-addon' ); ?></p>
								</td>
							</tr>

							<!-- Eventbrite Fallback Behavior -->
							<tr>
								<th scope="row"><?php esc_html_e( 'Eventbrite Ad-blocker Fallback', 'pubcrawls-addon' ); ?></th>
								<td>
									<label for="eventbrite_open_fallback">
										<input type="checkbox" name="<?php echo esc_attr( Pubcrawls_Admin::SETTINGS_KEY ); ?>[eventbrite_open_fallback]" id="eventbrite_open_fallback" value="1" <?php checked( '1', $settings['eventbrite_open_fallback'] ); ?> />
										<?php esc_html_e( 'Automatically open Eventbrite URL in new tab if modal script is blocked by user ad-blockers', 'pubcrawls-addon' ); ?>
									</label>
								</td>
							</tr>

						</tbody>
					</table>

				</div>
				<div class="pubcrawls-card__footer">
					<?php submit_button( esc_html__( 'Save Ticket Settings', 'pubcrawls-addon' ), 'primary', 'submit', false ); ?>
				</div>
			</div>

		</form>

	</section>

</div>
