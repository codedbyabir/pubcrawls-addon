<?php

/**
 * Elementor Widget: Cities List (Popular / All)
 *
 * Displays a tabbed list of popular and all cities directory.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Cities_List extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-cities-list';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Cities List (Popular / All)', 'pubcrawls-addon');
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-editor-list-ul';
	}

	/**
	 * Get widget categories.
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
		return ['pubcrawl', 'city', 'cities', 'list', 'popular', 'directory', 'filter'];
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__('Content', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_post_type',
			[
				'label'       => esc_html__('Post Type', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'city',
				'options'     => [
					'city'     => esc_html__('City (city)', 'pubcrawls-addon'),
					'city_map' => esc_html__('City Map (city_map)', 'pubcrawls-addon'),
				],
				'description' => esc_html__('Select post type to display.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_popular',
			[
				'label'       => esc_html__('Popular City IDs', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__('Comma-separated City Post IDs for Popular list', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_default_view',
			[
				'label'       => esc_html__('Default View', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'popular',
				'options'     => [
					'popular' => esc_html__('Show Popular First', 'pubcrawls-addon'),
					'all'     => esc_html__('Show All First', 'pubcrawls-addon'),
				],
			]
		);

		$this->end_controls_section();


		// ========================================================
		// Style Section: Popular Cities
		// ========================================================
		$this->start_controls_section(
			'section_style_popular',
			[
				'label'     => esc_html__('Popular Cities', 'pubcrawls-addon'),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'pubcrawl_default_view' => 'popular',
				],
			]
		);

		// Grid & Columns
		$this->add_responsive_control(
			'pubcrawl_popular_columns',
			[
				'label'          => esc_html__('Columns', 'pubcrawls-addon'),
				'type'           => \Elementor\Controls_Manager::SLIDER,
				'size_units'     => ['px'],
				'range'          => [
					'px' => [
						'min'  => 1,
						'max'  => 10,
						'step' => 1,
					],
				],
				'default'        => [
					'unit' => 'px',
					'size' => 5,
				],
				'tablet_default' => [
					'unit' => 'px',
					'size' => 3,
				],
				'mobile_default' => [
					'unit' => 'px',
					'size' => 2,
				],
				'selectors'      => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list' => 'grid-template-columns: repeat({{SIZE}}, 1fr);',
				],
			]
		);

		// Gaps
		$this->add_responsive_control(
			'pubcrawl_popular_gap',
			[
				'label'      => esc_html__('Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px'  => ['min' => 0, 'max' => 100],
					'rem' => ['min' => 0, 'max' => 10, 'step' => 0.1],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_popular_row_gap',
			[
				'label'      => esc_html__('Row Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px'  => ['min' => 0, 'max' => 100],
					'rem' => ['min' => 0, 'max' => 10, 'step' => 0.1],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list' => 'row-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_popular_column_gap',
			[
				'label'      => esc_html__('Column Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px'  => ['min' => 0, 'max' => 100],
					'rem' => ['min' => 0, 'max' => 10, 'step' => 0.1],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list' => 'column-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Items Heading
		$this->add_control(
			'pubcrawl_popular_items_heading',
			[
				'label'     => esc_html__('Item', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_popular_typography',
				'selector' => '{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_popular_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_popular_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Popular Item Tabs (Normal / Hover)
		$this->start_controls_tabs('tabs_popular_item_style');

		// Normal Tab
		$this->start_controls_tab(
			'tab_popular_item_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_popular_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_popular_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_popular_border',
				'selector' => '{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_popular_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a',
			]
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_popular_item_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_popular_hover_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_popular_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_popular_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_popular_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'pubcrawl_popular_transition',
			[
				'label'      => esc_html__('Transition Duration (s)', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['s'],
				'range'      => [
					's' => [
						'min'  => 0.1,
						'max'  => 2,
						'step' => 0.05,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-popular-city-list li a' => 'transition: all {{SIZE}}s ease-in-out;',
				],
			]
		);

		$this->end_controls_section();


		// ========================================================
		// Style Section: All Cities
		// ========================================================
		$this->start_controls_section(
			'section_style_all',
			[
				'label'     => esc_html__('All Cities', 'pubcrawls-addon'),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'pubcrawl_default_view' => 'all',
				],
			]
		);

		// Container Controls (pubcrawls-city-list)
		$this->add_control(
			'pubcrawl_all_container_heading',
			[
				'label' => esc_html__('Container', 'pubcrawls-addon'),
				'type'  => \Elementor\Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_all_columns',
			[
				'label'          => esc_html__('Columns', 'pubcrawls-addon'),
				'type'           => \Elementor\Controls_Manager::SLIDER,
				'size_units'     => ['px'],
				'range'          => [
					'px' => [
						'min'  => 1,
						'max'  => 10,
						'step' => 1,
					],
				],
				'default'        => [
					'unit' => 'px',
					'size' => 5,
				],
				'tablet_default' => [
					'unit' => 'px',
					'size' => 3,
				],
				'mobile_default' => [
					'unit' => 'px',
					'size' => 2,
				],
				'selectors'      => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list' => '-webkit-column-count: {{SIZE}}; -moz-column-count: {{SIZE}}; -o-column-count: {{SIZE}}; column-count: {{SIZE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_all_column_gap',
			[
				'label'      => esc_html__('Column Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px'  => ['min' => 0, 'max' => 100],
					'rem' => ['min' => 0, 'max' => 10, 'step' => 0.1],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list' => '-webkit-column-gap: {{SIZE}}{{UNIT}}; -moz-column-gap: {{SIZE}}{{UNIT}}; column-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_all_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_all_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_all_border',
				'selector' => '{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_all_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_all_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list',
			]
		);

		// List Items Controls (pubcrawls-city-list li & a)
		$this->add_control(
			'pubcrawl_all_items_heading',
			[
				'label'     => esc_html__('List Items', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_all_item_typography',
				'selector' => '{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li, {{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li a',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_all_item_padding',
			[
				'label'      => esc_html__('Padding (li)', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_all_item_spacing',
			[
				'label'      => esc_html__('Item Spacing', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px'  => ['min' => 0, 'max' => 100],
					'rem' => ['min' => 0, 'max' => 10, 'step' => 0.1],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_all_list_style_type',
			[
				'label'     => esc_html__('List Style Type', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''        => esc_html__('Default', 'pubcrawls-addon'),
					'disc'    => esc_html__('Disc', 'pubcrawls-addon'),
					'circle'  => esc_html__('Circle', 'pubcrawls-addon'),
					'square'  => esc_html__('Square', 'pubcrawls-addon'),
					'decimal' => esc_html__('Decimal', 'pubcrawls-addon'),
					'none'    => esc_html__('None', 'pubcrawls-addon'),
				],
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list' => 'list-style-type: {{VALUE}};',
				],
			]
		);

		// Item Colors Tabs (Normal / Hover)
		$this->start_controls_tabs('tabs_all_item_style');

		// Normal Tab
		$this->start_controls_tab(
			'tab_all_item_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_all_item_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_all_marker_color',
			[
				'label'     => esc_html__('Marker Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li::marker' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_all_item_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_all_item_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li a:hover, {{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li:hover > a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_all_marker_hover_color',
			[
				'label'     => esc_html__('Hover Marker Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li a:hover::marker, {{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li:hover::marker' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'pubcrawl_all_transition',
			[
				'label'      => esc_html__('Transition Duration (s)', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['s'],
				'range'      => [
					's' => [
						'min'  => 0.1,
						'max'  => 2,
						'step' => 0.05,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li a, {{WRAPPER}} .pubcrawls-city-wrap .pubcrawls-city-list li::marker' => 'transition: color {{SIZE}}s ease-in-out;',
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
		$settings  = $this->get_settings_for_display();

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
		$post_type = ! empty($settings['post_type']) ? sanitize_key($settings['post_type']) : 'city';
		$popular   = isset($settings['popular']) ? $settings['popular'] : '';
		$default   = isset($settings['default_view']) ? $settings['default_view'] : 'popular';

		$show_all_by_default = (strtolower(trim($default)) === 'all');

		$query = new \WP_Query([
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		]);

		if (! $query->have_posts()) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-list-view"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Cities List (No items found)', 'pubcrawls-addon') . '</span></div>';
			} else {
				echo '<p class="pubcrawls-ce-none">' . esc_html__('No items found.', 'pubcrawls-addon') . '</p>';
			}
			return;
		}

		$city_map   = [];
		$all_cities = [];

		while ($query->have_posts()) {
			$query->the_post();
			$city = [
				'id'        => get_the_ID(),
				'title'     => get_the_title(),
				'permalink' => get_permalink(),
			];
			$all_cities[]             = $city;
			$city_map[get_the_ID()] = $city;
		}
		wp_reset_postdata();

		$popular_ids = ! empty($popular)
			? array_map('intval', explode(',', $popular))
			: [];

		$popular_cities = [];
		foreach ($popular_ids as $id) {
			if (isset($city_map[$id])) {
				$popular_cities[] = $city_map[$id];
			}
		}

		$popular_style = $show_all_by_default ? 'display:none;' : '';
		$all_style     = $show_all_by_default ? '' : 'display:none;';

		wp_localize_script('pub-crawls-js', 'pubcrawlsCityData', [
			'showingAll' => $show_all_by_default ? '1' : '0',
		]);
?>
		<div class="pubcrawls-city-wrap">
			<!-- Popular list -->
			<div class="pubcrawls-city-grid" id="pubcrawls-popular-list" style="<?php echo esc_attr($popular_style); ?>">
				<ul class="pubcrawls-popular-city-list">
					<?php foreach ($popular_cities as $city) : ?>
						<li>
							<a href="<?php echo esc_url($city['permalink']); ?>">
								<?php echo esc_html($city['title']); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Full list -->
			<div class="pubcrawls-city-grid" id="pubcrawls-all-list" style="<?php echo esc_attr($all_style); ?>">
				<ul class="pubcrawls-city-list">
					<?php foreach ($all_cities as $city) : ?>
						<li>
							<a href="<?php echo esc_url($city['permalink']); ?>">
								<?php echo esc_html($city['title']); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
<?php
	}
}
