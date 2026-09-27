<?php

/**
 * Elementor Widget: City Events Grid
 *
 * Displays upcoming events for a city in a grid layout.
 * Directly based on [pubcrawls_city_events] shortcode.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit;
}

class Pubcrawls_City_Events extends \Elementor\Widget_Base
{

	public function get_name()
	{
		return 'pc-city-events';
	}

	public function get_title()
	{
		return esc_html__('City Events Grid', 'pubcrawls-addon');
	}

	public function get_icon()
	{
		return 'eicon-post-list';
	}

	public function get_categories()
	{
		return ['pubcrawls'];
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords()
	{
		return ['pubcrawl', 'city', 'events', 'grid', 'calendar', 'tickets', 'schedule'];
	}

	public function get_script_depends()
	{
		return ['squadup-widget', 'eventbrite-widget', 'pubcrawls-ticket-js'];
	}

	public function get_style_depends()
	{
		return ['squadup-embed-css'];
	}

	protected function register_controls()
	{

		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__('Content Settings', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_city_id',
			[
				'label'       => esc_html__('City ID', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__('City Post ID — leave empty to auto-detect from current city post', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_category',
			[
				'label'       => esc_html__('Category', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__('Event category slug (optional, auto-detects from crawl_cat or taxonomy archive)', 'pubcrawls-addon'),
			]
		);

		$this->end_controls_section();

		// ── Style Section: Grid ──────────────────────────────────
		$this->start_controls_section(
			'section_style_grid',
			[
				'label' => __('Grid', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_grid_columns',
			[
				'label'     => __('Columns', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 4,
				'default'   => 3,
				'mobile_default' => 1,
				'tablet_default' => 2,
				'selectors' => [
					'{{WRAPPER}} .pc-events-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_grid_gap',
			[
				'label'      => __('Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 100],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-events-grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Card ──────────────────────────────────
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => __('Card', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_card_bg_color',
			[
				'label'     => __('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_card_border',
				'selector' => '{{WRAPPER}} .pc-event-card',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => __('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_padding',
			[
				'label'      => __('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_box_shadow',
				'selector' => '{{WRAPPER}} .pc-event-card',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Thumbnail Image ───────────────────────
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => __('Image', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_image_height',
			[
				'label'      => __('Height', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'vh'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 1000],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-card-thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_image_border_radius',
			[
				'label'      => __('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-card-thumb, {{WRAPPER}} .pc-event-card-thumb img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Date Badge ────────────────────────────
		$this->start_controls_section(
			'section_style_date_badge',
			[
				'label' => __('Date Badge', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_date_badge_bg_color',
			[
				'label'     => __('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-date-badge' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_date_badge_border_radius',
			[
				'label'      => __('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-date-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_date_badge_day_color',
			[
				'label'     => __('Day Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-day' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_date_badge_day_typography',
				'label'    => __('Day Typography', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pc-event-day',
			]
		);

		$this->add_control(
			'pubcrawl_date_badge_month_color',
			[
				'label'     => __('Month Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-month' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_date_badge_month_typography',
				'label'    => __('Month Typography', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pc-event-month',
			]
		);

		$this->add_control(
			'pubcrawl_date_badge_year_color',
			[
				'label'     => __('Year Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-year' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_date_badge_year_typography',
				'label'    => __('Year Typography', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pc-event-year',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Title ─────────────────────────────────
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => __('Title', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_title_typography',
				'selector' => '{{WRAPPER}} .pc-event-card-title, {{WRAPPER}} .pc-event-card-title a',
			]
		);

		$this->add_control(
			'pubcrawl_title_color',
			[
				'label'     => __('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-card-title, {{WRAPPER}} .pc-event-card-title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_title_hover_color',
			[
				'label'     => __('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-card-title a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Buy Button ────────────────────────────
		$this->start_controls_section(
			'section_style_buy_btn',
			[
				'label' => __('Buy Button', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_buy_btn_typography',
				'selector' => '{{WRAPPER}} .pc-event-btn--buy',
			]
		);

		$this->start_controls_tabs('tabs_buy_btn_style');

		$this->start_controls_tab(
			'tab_buy_btn_normal',
			[
				'label' => __('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_buy_btn_text_color',
			[
				'label'     => __('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--buy' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_buy_btn_bg_color',
			[
				'label'     => __('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--buy' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_buy_btn_hover',
			[
				'label' => __('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_buy_btn_hover_text_color',
			[
				'label'     => __('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--buy:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_buy_btn_hover_bg_color',
			[
				'label'     => __('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--buy:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_buy_btn_hover_border_color',
			[
				'label'     => __('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--buy:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'pubcrawl_buy_btn_border',
				'selector'  => '{{WRAPPER}} .pc-event-btn--buy',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_buy_btn_border_radius',
			[
				'label'      => __('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-btn--buy' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_buy_btn_padding',
			[
				'label'      => __('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-btn--buy' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Info Button ───────────────────────────
		$this->start_controls_section(
			'section_style_info_btn',
			[
				'label' => __('Info Button', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_info_btn_typography',
				'selector' => '{{WRAPPER}} .pc-event-btn--info',
			]
		);

		$this->start_controls_tabs('tabs_info_btn_style');

		$this->start_controls_tab(
			'tab_info_btn_normal',
			[
				'label' => __('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_info_btn_text_color',
			[
				'label'     => __('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--info' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_info_btn_bg_color',
			[
				'label'     => __('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--info' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_info_btn_hover',
			[
				'label' => __('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_info_btn_hover_text_color',
			[
				'label'     => __('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--info:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_info_btn_hover_bg_color',
			[
				'label'     => __('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--info:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_info_btn_hover_border_color',
			[
				'label'     => __('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-event-btn--info:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'pubcrawl_info_btn_border',
				'selector'  => '{{WRAPPER}} .pc-event-btn--info',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_info_btn_border_radius',
			[
				'label'      => __('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-btn--info' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_info_btn_padding',
			[
				'label'      => __('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-event-btn--info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 * Exact copy of pubcrawls_city_events_shortcode HTML and attributes.
	 */
	protected function render()
	{
		$settings = $this->get_settings_for_display();

		// Ensure backward compatibility between prefixed and non-prefixed setting keys.
		foreach (array_keys($settings) as $setting_key) {
			if (0 === strpos($setting_key, 'pubcrawl_')) {
				$base_key = substr($setting_key, 9);
				if (! isset($settings[$base_key])) {
					$settings[$base_key] = $settings[$setting_key];
				}
			} elseif (! isset($settings['pubcrawl_' . $setting_key])) {
				$settings['pubcrawl_' . $setting_key] = $settings[$setting_key];
			}
		}
		$city_id  = ! empty($settings['city_id']) ? intval($settings['city_id']) : 0;
		$category = isset($settings['category']) ? sanitize_text_field($settings['category']) : '';

		$today = gmdate('Ymd');

		// ── City ID — attribute, URL param, or current post ───
		if (empty($city_id)) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url_city_id = isset($_GET['city_id']) ? absint(wp_unslash($_GET['city_id'])) : 0;
			if (! empty($url_city_id)) {
				$city_id = $url_city_id;
			} elseif (get_post_type() === 'city') {
				$city_id = get_the_ID();
			}
		}

		// ── Category — attribute, URL param, or tax archive ───
		$category_slug = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$url_crawl_cat = isset($_GET['crawl_cat']) ? sanitize_text_field(wp_unslash($_GET['crawl_cat'])) : '';

		if (! empty($category)) {
			$category_slug = sanitize_text_field($category);
		} elseif (! empty($url_crawl_cat)) {
			$category_slug = $url_crawl_cat;
		} elseif (is_tax('tribe_events_cat')) {
			$category_slug = get_queried_object()->slug;
		} elseif (get_query_var('tribe_events_cat')) {
			$category_slug = get_query_var('tribe_events_cat');
		}

		// ── Build event query ──────────────────────────────────
		$event_args = [
			'post_type'      => 'tribe_events',
			'posts_per_page' => -1,
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation'          => 'AND',
				'event_date_clause' => [
					'key'     => 'event_dates_0_eventdate',
					'compare' => 'EXISTS',
					'type'    => 'DATE',
				],
			],
			'orderby' => [
				'event_date_clause' => 'ASC',
			],
		];

		if (! empty($city_id)) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$event_args['meta_query'][] = [
				'key'     => 'event_city',
				'value'   => '"' . $city_id . '"',
				'compare' => 'LIKE',
			];
		}

		if (! empty($category_slug)) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$event_args['tax_query'] = [[
				'taxonomy' => 'tribe_events_cat',
				'field'    => 'slug',
				'terms'    => $category_slug,
			]];
		}

		$events = new \WP_Query($event_args);

		if (! $events->have_posts()) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-calendar-alt"></span><span class="pubcrawls-placeholder-text">' . esc_html__('City Events Grid (No upcoming events found)', 'pubcrawls-addon') . '</span></div>';
			} else {
				echo '<p class="pubcrawls-ce-none">' . esc_html__('No upcoming events found.', 'pubcrawls-addon') . '</p>';
			}
			return;
		}

		// ── Enqueue all scripts before HTML ────────────────────
		if (function_exists('pubcrawls_enqueue_city_ticket_assets')) {
			pubcrawls_enqueue_city_ticket_assets();
		} else {
			wp_enqueue_style('squadup-embed-css');
			wp_enqueue_script('eventbrite-widget');
			wp_enqueue_script('squadup-widget');
			wp_enqueue_script('pubcrawls-ticket-js');
		}

		$placeholder_img = defined('PUBCRAWLS_ADDON_URL')
			? PUBCRAWLS_ADDON_URL . 'assets/img/placeholder.png'
			: (defined('PC_PLUGIN_URL') ? PC_PLUGIN_URL . '/assets/img/placeholder.png' : '');
?>

		<div class="pc-events-grid">

			<?php while ($events->have_posts()) : $events->the_post();

				$event     = get_post();
				$event_id  = get_the_ID();
				$permalink = get_permalink();
				$thumb_id  = get_post_thumbnail_id();
				$thumbnail = $thumb_id
					? wp_get_attachment_image_url($thumb_id, 'full')
					: $placeholder_img;

				// ── ACF repeater ───────────────────────────────
				$event_dates   = function_exists('get_field') ? get_field('event_dates', $event_id) : [];
				$has_dates     = ! empty($event_dates) && is_array($event_dates);
				$post_provider = function_exists('get_field') ? sanitize_key(get_field('ticket_provider', $event_id) ?: 'squadup') : 'squadup';

				// ── Skip if no upcoming dates ──────────────────
				$has_upcoming_date = false;
				if ($has_dates) {
					foreach ($event_dates as $row) {
						if (
							! empty($row['eventdate']) &&
							gmdate('Ymd', strtotime($row['eventdate'])) >= $today
						) {
							$has_upcoming_date = true;
							break;
						}
					}
				}

				if (! $has_upcoming_date) continue;

				// ── Build normalised date rows ─────────────────
				$date_rows = [];
				if ($has_dates) {
					foreach ($event_dates as $row) {
						$date_raw  = $row['eventdate']        ?? '';
						$start_raw = $row['start_time']       ?? '';
						$end_raw   = $row['end_time']         ?? '';
						$row_prov  = ! empty($row['ticket_provider']) ? $row['ticket_provider'] : $post_provider;
						$platform  = sanitize_key($row_prov ?: 'squadup');
						$eb_id     = trim($row['eventbrite_id']   ?? '');
						$eb_link   = trim($row['eventbrite_link'] ?? '');
						$su_id     = trim($row['squadup_id']      ?? '');

						$ts      = ! empty($date_raw) ? strtotime($date_raw) : null;
						$d_label = $ts ? gmdate('D, M j', $ts) : '';

						$time_range = '';
						if (! empty($start_raw)) {
							$time_range = gmdate('g:i A', strtotime($start_raw));
							if (! empty($end_raw)) {
								$time_range .= ' – ' . gmdate('g:i A', strtotime($end_raw));
							}
						}

						$date_rows[] = [
							'label'    => $d_label,
							'time'     => $time_range,
							'platform' => $platform,
							'eb_id'    => $eb_id,
							'eb_link'  => $eb_link,
							'su_id'    => $su_id,
							'day'      => $ts ? gmdate('d', $ts) : '',
							'month'    => $ts ? gmdate('M', $ts) : '',
							'year'     => $ts ? gmdate('Y', $ts) : '',
						];
					}
				}

				$first_row  = $date_rows[0] ?? null;
				$card_uid   = 'pce_' . $event_id . '_' . uniqid();
				$has_ticket = false;

				foreach ($date_rows as $date_row) {
					if (! empty($date_row['eb_id']) || ! empty($date_row['su_id'])) {
						$has_ticket = true;
						break;
					}
				}

				// ── Combine all days into the badge ─────────────
				$all_days   = [];
				$all_months = [];
				$all_years  = [];

				foreach ($date_rows as $dr) {
					if (! empty($dr['day']))   $all_days[]   = $dr['day'];
					if (! empty($dr['month'])) $all_months[] = $dr['month'];
					if (! empty($dr['year']))  $all_years[]  = $dr['year'];
				}

				$unique_months = array_values(array_unique($all_months));
				$unique_years  = array_values(array_unique($all_years));

				if (count($unique_months) === 1 && count($unique_years) === 1) {
					// All dates share the same month and year, show every day together
					$day   = implode(', ', array_unique($all_days));
					$month = $unique_months[0];
					$year  = $unique_years[0];
				} else {
					// Dates span different months or years, fall back to the first date only
					$day   = $first_row['day']   ?? '';
					$month = $first_row['month'] ?? '';
					$year  = $first_row['year']  ?? '';
				}
			?>

				<div class="pc-event-card">

					<div class="pc-event-card-thumb">
						<a href="<?php echo esc_url($permalink); ?>">
							<img src="<?php echo esc_url($thumbnail); ?>"
								alt="<?php echo esc_attr($event->post_title); ?>">
						</a>
						<div class="pc-event-date-badge">
							<span class="pc-event-day"><?php echo esc_html($day); ?></span>
							<span class="pc-event-month"><?php echo esc_html($month); ?></span>
							<span class="pc-event-year"><?php echo esc_html($year); ?></span>
						</div>
					</div>

					<div class="pc-event-card-body">
						<h3 class="pc-event-card-title">
							<a href="<?php echo esc_url($permalink); ?>">
								<?php echo esc_html($event->post_title); ?>
							</a>
						</h3>
					</div>

					<div class="pcet-event-date-select-wrapper">

						<?php if ($has_dates && count($date_rows) > 1) : ?>
							<p class="pc-event-select-label"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E6B325" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar">
									<path d="M8 2v3" />
									<path d="M16 2v3" />
									<rect x="3" y="3" width="18" height="18" rx="2" />
									<path d="M3 9h18" />
								</svg> <span><?php esc_html_e('Select Your Date :', 'pubcrawls-addon'); ?></span></p>
						<?php endif; ?>

						<?php if ($has_dates && $first_row) : ?>

							<?php $single_date_class = count($date_rows) === 1 ? 'single-date' : ''; ?>

							<select
								class="pc-event-date-select <?php echo esc_attr($single_date_class); ?>"
								data-card-uid="<?php echo esc_attr($card_uid); ?>"
								aria-label="<?php esc_attr_e('Select event date', 'pubcrawls-addon'); ?>">

								<?php foreach ($date_rows as $i => $dr) : ?>
									<option
										value="<?php echo absint($i); ?>"
										data-platform="<?php echo esc_attr($dr['platform']); ?>"
										data-eb-link="<?php echo esc_url($dr['eb_link']); ?>"
										data-eb-id="<?php echo esc_attr($dr['eb_id']); ?>"
										data-su-id="<?php echo esc_attr($dr['su_id']); ?>"
										data-day="<?php echo esc_attr($dr['day']); ?>"
										data-month="<?php echo esc_attr($dr['month']); ?>"
										data-year="<?php echo esc_attr($dr['year']); ?>">
										<?php echo esc_html($dr['label'] . ($dr['time'] ? ' — ' . $dr['time'] : '')); ?>
									</option>
								<?php endforeach; ?>

							</select>

							<?php if ($has_ticket) : ?>
								<div class="pc-event-card-buttons">

									<noscript>
										<a href="<?php echo esc_url($first_row['eb_link']); ?>"
											rel="noopener noreferrer"
											target="_blank"><?php esc_html_e('Buy Tickets on Eventbrite', 'pubcrawls-addon'); ?></a>
									</noscript>

									<!-- Eventbrite button -->
									<button
										id="eb-trigger-<?php echo esc_attr($card_uid); ?>"
										class="pc-event-btn pc-event-btn--buy"
										type="button"
										data-card-uid="<?php echo esc_attr($card_uid); ?>"
										data-eb-id="<?php echo esc_attr($first_row['eb_id']); ?>"
										data-eb-link="<?php echo esc_url($first_row['eb_link']); ?>"
										<?php if ($first_row['platform'] !== 'eventbrite' || empty($first_row['eb_id'])) : ?>style="display:none;" <?php endif; ?>>
										<?php esc_html_e('Buy Tickets', 'pubcrawls-addon'); ?>
									</button>

									<!-- Squad Up button -->
									<button
										class="pc-event-btn pc-event-btn--buy pc-su-trigger"
										type="button"
										data-card-uid="<?php echo esc_attr($card_uid); ?>"
										data-su-id="<?php echo esc_attr($first_row['su_id']); ?>"
										<?php if ($first_row['platform'] !== 'squadup' || empty($first_row['su_id'])) : ?>style="display:none;" <?php endif; ?>>
										<?php esc_html_e('Buy Tickets', 'pubcrawls-addon'); ?>
									</button>

									<a href="<?php echo esc_url($permalink); ?>"
										class="pc-event-btn pc-event-btn--info">
										<?php esc_html_e('More Info', 'pubcrawls-addon'); ?>
									</a>

								</div>
							<?php endif; ?>

						<?php endif; ?>

					</div>

				</div>

			<?php endwhile;
			wp_reset_postdata(); ?>

		</div>

		<?php
		if (function_exists('pubcrawls_render_squadup_modal')) {
			pubcrawls_render_squadup_modal();
		}
		?>

<?php
	}
}
