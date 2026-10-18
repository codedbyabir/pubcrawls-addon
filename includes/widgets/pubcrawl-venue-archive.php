<?php

/**
 * Elementor Widget: Venue Archive Grid
 *
 * Displays a grid of venues with venue_category filter and directions links,
 * matching the design layout and controls of City Venues for archive and category pages.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Venue_Archive extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-venue-archive';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Venue Archive Grid', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-gallery-grid';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * @return array Widget categories.
	 */
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
		return ['pubcrawl', 'venue', 'venues', 'archive', 'category', 'taxonomy', 'bars', 'clubs', 'grid'];
	}

	/**
	 * Get Venue Categories for select control options.
	 *
	 * @return array
	 */
	protected function get_venue_categories()
	{
		$options = [];

		$terms = get_terms([
			'taxonomy'   => 'venue_category',
			'hide_empty' => false,
		]);

		if (! empty($terms) && ! is_wp_error($terms)) {
			foreach ($terms as $term) {
				$options[$term->slug] = html_entity_decode($term->name, ENT_QUOTES, 'UTF-8');
			}
		}

		return $options;
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{
		// ── Content Section ──────────────────────────────────────
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__('Content', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_category',
			[
				'label'       => esc_html__('Venue Category', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => [
					'' => esc_html__('Auto-Detect (Taxonomy Archive)', 'pubcrawls-addon'),
				] + $this->get_venue_categories(),
				'default'     => '',
				'description' => esc_html__('Select venue category, or leave as Auto-Detect to inherit from current category archive page or ?venue_cat parameter.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_posts_per_page',
			[
				'label'       => esc_html__('Posts Per Page', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => -1,
				'min'         => -1,
				'max'         => 100,
				'step'        => 1,
				'description' => esc_html__('Number of venues to display. Enter -1 to show all.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_orderby',
			[
				'label'   => esc_html__('Order By', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'title',
				'options' => [
					'title'      => esc_html__('Title', 'pubcrawls-addon'),
					'date'       => esc_html__('Date', 'pubcrawls-addon'),
					'menu_order' => esc_html__('Menu Order', 'pubcrawls-addon'),
					'rand'       => esc_html__('Random', 'pubcrawls-addon'),
					'ID'         => esc_html__('Post ID', 'pubcrawls-addon'),
				],
			]
		);

		$this->add_control(
			'pubcrawl_order',
			[
				'label'   => esc_html__('Order', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'ASC',
				'options' => [
					'ASC'  => esc_html__('Ascending (ASC)', 'pubcrawls-addon'),
					'DESC' => esc_html__('Descending (DESC)', 'pubcrawls-addon'),
				],
			]
		);

		$this->add_control(
			'pubcrawl_venue_ids',
			[
				'label'       => esc_html__('Include Specific Venue IDs', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__('Comma-separated Venue Post IDs to include (optional).', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_default_image',
			[
				'label'       => esc_html__('Default Thumbnail Image', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'default'     => [
					'url' => class_exists('\Elementor\Utils') && method_exists('\Elementor\Utils', 'get_placeholder_image_src')
						? \Elementor\Utils::get_placeholder_image_src()
						: PUBCRAWLS_ADDON_URL . 'assets/img/placeholder.png',
				],
				'description' => esc_html__('Fallback image used when a venue does not have a featured image.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_show_directions_btn',
			[
				'label'        => esc_html__('Show Directions Button', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_text',
			[
				'label'     => esc_html__('Directions Button Text', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__('Get Directions', 'pubcrawls-addon'),
				'condition' => [
					'pubcrawl_show_directions_btn' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_no_venues_text',
			[
				'label'       => esc_html__('Empty State Text', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => esc_html__('No venues are currently available.', 'pubcrawls-addon'),
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'pubcrawl_show_contact_btn',
			[
				'label'        => esc_html__('Show Contact Button on Empty', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_contact_btn_text',
			[
				'label'     => esc_html__('Contact Button Text', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__('Contact Us', 'pubcrawls-addon'),
				'condition' => [
					'pubcrawl_show_contact_btn' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_contact_btn_url',
			[
				'label'     => esc_html__('Contact Button URL', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::URL,
				'default'   => [
					'url'         => home_url('/contact/'),
					'is_external' => true,
					'nofollow'    => false,
				],
				'condition' => [
					'pubcrawl_show_contact_btn' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Grid ──────────────────────────────────
		$this->start_controls_section(
			'section_style_grid',
			[
				'label' => esc_html__('Grid', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_grid_columns',
			[
				'label'     => esc_html__('Columns', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 4,
				'default'   => 3,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues, {{WRAPPER}} .pubcrawl-venue-archive-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_grid_gap',
			[
				'label'      => esc_html__('Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 100],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 24,
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-city-venues, {{WRAPPER}} .pubcrawl-venue-archive-grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Card ──────────────────────────────────
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => esc_html__('Card', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs('tabs_card_style');

		// Normal Tab
		$this->start_controls_tab(
			'tab_card_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_card_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-item' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawl-venue-item',
			]
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_card_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_card_bg_color_hover',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-item:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_card_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-item:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawl-venue-item:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'pubcrawl_card_border',
				'selector'  => '{{WRAPPER}} .pubcrawl-venue-item',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-item .pubcrawl-venue-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Image ─────────────────────────────────
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__('Image', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_image_height',
			[
				'label'      => esc_html__('Height', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'vh'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 1000],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 250,
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_image_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-thumb img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Title ─────────────────────────────────
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__('Title', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_title_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-venue-title, {{WRAPPER}} .pubcrawl-venue-title a',
			]
		);

		$this->start_controls_tabs('tabs_title_style');

		$this->start_controls_tab(
			'tab_title_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_title_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_title_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_title_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-title a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// ── Style Section: Location ──────────────────────────────
		$this->start_controls_section(
			'section_style_location',
			[
				'label' => esc_html__('Location', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_location_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-venue-location',
			]
		);

		$this->add_control(
			'pubcrawl_location_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-location' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_location_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-location svg'      => 'fill: {{VALUE}}; color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-venue-location svg path' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_location_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => ['min' => 8, 'max' => 40],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-location svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Directions Button ─────────────────────
		$this->start_controls_section(
			'section_style_directions_btn',
			[
				'label'     => esc_html__('Directions Button', 'pubcrawls-addon'),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'pubcrawl_show_directions_btn' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_directions_btn_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-venue-destination',
			]
		);

		$this->start_controls_tabs('tabs_directions_btn_style');

		// Normal Tab
		$this->start_controls_tab(
			'tab_directions_btn_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-destination, {{WRAPPER}} .pubcrawl-venue-destination span' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-venue-destination svg' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-destination' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-destination' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_directions_btn_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_hover_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-destination:hover, {{WRAPPER}} .pubcrawl-venue-destination:hover span' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .pubcrawl-venue-destination:hover svg' => 'fill: {{VALUE}} !important; color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-destination:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-destination:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'pubcrawl_directions_btn_border',
				'selector'  => '{{WRAPPER}} .pubcrawl-venue-destination',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_directions_btn_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-destination' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_directions_btn_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%', 'rem'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-destination' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render()
	{
		$settings = $this->get_settings_for_display();

		// Backward compatibility for setting keys without pubcrawl_ prefix.
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

		$category = isset($settings['pubcrawl_category']) ? $settings['pubcrawl_category'] : '';
		if (is_array($category)) {
			$category = reset($category);
		}
		$category_slug = sanitize_text_field($category);

		// Auto-detect venue category from taxonomy archive or query params.
		if (empty($category_slug)) {
			if (isset($_GET['venue_cat'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$category_slug = sanitize_text_field(wp_unslash($_GET['venue_cat'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif (isset($_GET['crawl_cat'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$category_slug = sanitize_text_field(wp_unslash($_GET['crawl_cat'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif (is_tax('venue_category')) {
				$queried_obj = get_queried_object();
				if ($queried_obj instanceof \WP_Term && 'venue_category' === $queried_obj->taxonomy) {
					$category_slug = $queried_obj->slug;
				}
			} elseif (get_query_var('venue_category')) {
				$category_slug = get_query_var('venue_category');
			} else {
				$queried_obj = get_queried_object();
				if ($queried_obj instanceof \WP_Term && 'venue_category' === $queried_obj->taxonomy) {
					$category_slug = $queried_obj->slug;
				}
			}
		}

		// Fallback image.
		$default_image_url = ! empty($settings['pubcrawl_default_image']['url']) ? $settings['pubcrawl_default_image']['url'] : '';
		if (empty($default_image_url) && ! empty($settings['pubcrawl_default_image']['id'])) {
			$default_image_url = wp_get_attachment_image_url(intval($settings['pubcrawl_default_image']['id']), 'full');
		}
		if (empty($default_image_url)) {
			$default_image_url = PUBCRAWLS_ADDON_URL . 'assets/img/placeholder.png';
		}

		// Specific Venue IDs.
		$include_ids = [];
		if (! empty($settings['pubcrawl_venue_ids'])) {
			$include_ids = array_filter(array_map('intval', explode(',', $settings['pubcrawl_venue_ids'])));
		}

		$posts_per_page = isset($settings['pubcrawl_posts_per_page']) && '' !== $settings['pubcrawl_posts_per_page']
			? intval($settings['pubcrawl_posts_per_page'])
			: -1;
		$orderby = ! empty($settings['pubcrawl_orderby']) ? sanitize_key($settings['pubcrawl_orderby']) : 'title';
		$order   = ! empty($settings['pubcrawl_order']) ? sanitize_key($settings['pubcrawl_order']) : 'ASC';

		$query_args = [
			'post_type'      => 'venue',
			'posts_per_page' => $posts_per_page,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => 'publish',
		];

		if (! empty($include_ids)) {
			$query_args['post__in'] = $include_ids;
			if (empty($settings['pubcrawl_orderby'])) {
				$query_args['orderby'] = 'post__in';
			}
		}

		if (! empty($category_slug)) {
			$query_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'venue_category',
					'field'    => 'slug',
					'terms'    => $category_slug,
				],
			];
		}

		$venues = get_posts($query_args);

		$show_directions_btn = 'yes' === ($settings['pubcrawl_show_directions_btn'] ?? 'yes');
		$directions_btn_text = ! empty($settings['pubcrawl_directions_btn_text']) ? $settings['pubcrawl_directions_btn_text'] : __('Get Directions', 'pubcrawls-addon');
		$no_venues_text      = ! empty($settings['pubcrawl_no_venues_text']) ? $settings['pubcrawl_no_venues_text'] : __('No venues are currently available.', 'pubcrawls-addon');
		$show_contact_btn    = 'yes' === ($settings['pubcrawl_show_contact_btn'] ?? 'yes');
		$contact_btn_text    = ! empty($settings['pubcrawl_contact_btn_text']) ? $settings['pubcrawl_contact_btn_text'] : __('Contact Us', 'pubcrawls-addon');
		$contact_url         = ! empty($settings['pubcrawl_contact_btn_url']['url']) ? $settings['pubcrawl_contact_btn_url']['url'] : home_url('/contact/');

		if (! empty($venues)) : ?>
			<div class="pubcrawl-venue-archive-grid pubcrawl-city-venues">
				<?php foreach ($venues as $venue) :
					$venue_id   = $venue->ID;
					$permalink  = get_permalink($venue_id);
					$thumb_id   = get_post_thumbnail_id($venue_id);
					$thumbnail  = $thumb_id
						? wp_get_attachment_image_url($thumb_id, 'full')
						: $default_image_url;
					$title      = get_the_title($venue_id);
					$address    = function_exists('get_field') ? get_field('address', $venue_id) : '';
					$destination_url = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($address);
				?>
					<div class="pubcrawl-venue-item">
						<div class="pubcrawl-venue-thumb">
							<a href="<?php echo esc_url($permalink); ?>">
								<img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>">
							</a>
						</div>
						<div class="pubcrawl-venue-content">
							<h3 class="pubcrawl-venue-title">
								<a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
							</h3>
							<?php if ($address) : ?>
								<p class="pubcrawl-venue-location">
									<svg xmlns="http://www.w3.org/2000/svg" height="14" width="10.5" viewBox="0 0 384 512">
										<path fill="#e6b325" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z" />
									</svg>
									<?php echo esc_html($address); ?>
								</p>
								<?php if ($show_directions_btn) : ?>
									<a target="_blank" href="<?php echo esc_url($destination_url); ?>" class="pubcrawl-venue-destination">
										<svg xmlns="http://www.w3.org/2000/svg" height="14" width="14" viewBox="0 0 576 512">
											<path fill="currentColor" d="M536.4-26.3c9.8-3.5 20.6-1 28 6.3s9.8 18.2 6.3 28l-178 496.9c-5 13.9-18.1 23.1-32.8 23.1-14.2 0-27-8.6-32.3-21.7l-64.2-158c-4.5-11-2.5-23.6 5.2-32.6l94.5-112.4c5.1-6.1 4.7-15-.9-20.6s-14.6-6-20.6-.9L229.2 276.1c-9.1 7.6-21.6 9.6-32.6 5.2L38.1 216.8c-13.1-5.3-21.7-18.1-21.7-32.3 0-14.7 9.2-27.8 23.1-32.8l496.9-178z"></path>
										</svg>
										<span style="margin-left:5px;"><?php echo esc_html($directions_btn_text); ?></span>
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php if (\Elementor\Plugin::$instance->editor->is_edit_mode()) : ?>
				<div class="pubcrawls-editor-placeholder">
					<span class="pubcrawls-placeholder-icon dashicons dashicons-grid-view"></span>
					<span class="pubcrawls-placeholder-text"><?php esc_html_e('Venue Archive Grid (No venues found)', 'pubcrawls-addon'); ?></span>
				</div>
			<?php else : ?>
				<div class="pubcrawl-no-venues">
					<p>
						<?php echo esc_html($no_venues_text); ?><br>
						<?php esc_html_e('Want to see your venue featured here? Contact us today!', 'pubcrawls-addon'); ?>
					</p>
					<?php if ($show_contact_btn && ! empty($contact_url)) : ?>
						<a target="_blank" href="<?php echo esc_url($contact_url); ?>" class="pubcrawl-contact">
							<?php echo esc_html($contact_btn_text); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif;
	}
}
