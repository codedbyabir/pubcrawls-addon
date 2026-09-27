<?php

/**
 * Elementor Widget: Cities Card Grid
 *
 * Displays a grid of cities with their upcoming event counts.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Cities_Card extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-cities-card';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Cities Card Grid', 'pubcrawls-addon');
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-gallery-grid';
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
		return ['pubcrawl', 'city', 'cities', 'card', 'grid', 'destination', 'events'];
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
			'pubcrawl_limit',
			[
				'label'       => esc_html__('Limit', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => -1,
				'description' => esc_html__('Number of cities to show (-1 for all)', 'pubcrawls-addon'),
			]
		);

		$this->end_controls_section();

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
				'label'   => __('Columns', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 5,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-card-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
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
					'{{WRAPPER}} .pubcrawls-city-card-grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

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
					'{{WRAPPER}} .pubcrawls-city-item' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_card_border',
				'selector' => '{{WRAPPER}} .pubcrawls-city-item',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => __('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .pubcrawls-city-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

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
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 220,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 1000],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-item img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
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
					'{{WRAPPER}} .pubcrawls-city-item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_city_name',
			[
				'label' => __('City Name', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_city_name_typography',
				'selector' => '{{WRAPPER}} .pubcrawls-city-name, {{WRAPPER}} .pubcrawls-city-name a',
			]
		);

		$this->start_controls_tabs('tabs_city_name_style');
		$this->start_controls_tab(
			'tab_city_name_normal',
			[
				'label' => __('Normal', 'pubcrawls-addon'),
			]
		);
		$this->add_control(
			'pubcrawl_city_name_color',
			[
				'label'     => __('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-name' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawls-city-name a' => 'color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_city_name_hover',
			[
				'label' => __('Hover', 'pubcrawls-addon'),
			]
		);
		$this->add_control(
			'pubcrawl_city_name_hover_color',
			[
				'label'     => __('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-name a:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_event_count',
			[
				'label' => __('Event Count', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_event_count_typography',
				'selector' => '{{WRAPPER}} .pubcrawls-city-count',
			]
		);

		$this->add_control(
			'pubcrawl_event_count_color',
			[
				'label'     => __('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_event_count_icon_color',
			[
				'label'     => __('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-count svg path' => 'fill: {{VALUE}};',
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
		$limit    = ! empty($settings['limit']) ? intval($settings['limit']) : -1;

		$cities = new \WP_Query([
			'post_type'      => 'city',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		]);

		if (! $cities->have_posts()) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-location-alt"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Cities Card Grid (No cities found)', 'pubcrawls-addon') . '</span></div>';
			} else {
				echo '<p class="pc-no-cities">' . esc_html__('No cities found.', 'pubcrawls-addon') . '</p>';
			}
			return;
		}

		$today = gmdate('Ymd');
?>
		<div class="pubcrawls-city-card-grid">
			<?php foreach ($cities->posts as $city) :
				$city_id = $city->ID;
				$image   = get_the_post_thumbnail_url($city_id, 'large');
				$link    = get_permalink($city_id);

				$categories = function_exists('get_field') ? get_field('select_event_category', $city_id) : [];
				$term_ids   = [];

				if ($categories) {
					foreach ($categories as $term) {
						$term_ids[] = is_object($term) ? $term->term_id : (int) $term;
					}
				}

				$event_count = 0;
				if (! empty($term_ids)) {
					$events_query = new \WP_Query([
						'post_type'      => 'tribe_events',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							[
								'taxonomy' => 'tribe_events_cat',
								'field'    => 'term_id',
								'terms'    => $term_ids,
								'operator' => 'IN',
							],
						],
						'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							[
								'key'     => 'eventdate',
								'value'   => $today,
								'compare' => '>=',
								'type'    => 'DATE',
							],
						],
					]);

					$event_count = $events_query->found_posts;
					wp_reset_postdata();
				}
			?>
				<div class="pubcrawls-city-item">
					<?php if (has_post_thumbnail($city_id)) : ?>
						<a href="<?php echo esc_url($link); ?>">
							<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($city->post_title); ?>">
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url($link); ?>">
							<img src="<?php echo esc_url(PUBCRAWLS_ADDON_URL . 'assets/img/placeholder.png'); ?>" alt="<?php echo esc_attr($city->post_title); ?>">
						</a>
					<?php endif; ?>

					<div class="pubcrawls-city-content">
						<h3 class="pubcrawls-city-name">
							<a href="<?php echo esc_url($link); ?>">
								<?php echo esc_html($city->post_title); ?>
							</a>
						</h3>
						<p class="pubcrawls-city-count">
							<svg aria-hidden="true" class="e-font-icon-svg e-fas-map-marker-alt" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
								<path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path>
							</svg>
							<?php echo esc_html($event_count); ?>
							<?php echo esc_html($event_count === 1 ? __('Upcoming Event', 'pubcrawls-addon') : __('Upcoming Events', 'pubcrawls-addon')); ?>
						</p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
<?php
	}
}
