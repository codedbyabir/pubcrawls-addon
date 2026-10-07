<?php

/**
 * Elementor Widget: City Archive Grid
 *
 * Displays event cities archive grid with optional category and city ID filters.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_City_Archive extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-city-archive';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('City Archive Grid', 'pubcrawls-addon');
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-posts-grid';
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
		return ['pubcrawl', 'city', 'cities', 'archive', 'grid', 'taxonomy', 'category'];
	}

	/**
	 * Get Event Categories for select control options.
	 *
	 * @return array
	 */
	protected function get_event_categories()
	{
		$options = [];

		$terms = get_terms([
			'taxonomy'   => 'tribe_events_cat',
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
				'label'       => esc_html__('Category', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => [
					'' => esc_html__('Auto-Detect (Taxonomy Archive)', 'pubcrawls-addon'),
				] + $this->get_event_categories(),
				'default'     => '',
				'description' => esc_html__('Select event category, or leave as Auto-Detect to inherit from current taxonomy archive or ?crawl_cat.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_city_ids',
			[
				'label'       => esc_html__('City IDs', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__('Comma-separated City Post IDs', 'pubcrawls-addon'),
			]
		);

		$this->end_controls_section();

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
				'label'   => esc_html__('Columns', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
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
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

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

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_card_border',
				'selector' => '{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card, {{WRAPPER}} .pubcrawl-city-card',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card, {{WRAPPER}} .pubcrawl-city-card',
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
			'pubcrawl_card_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card:hover, {{WRAPPER}} .pubcrawl-city-card:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card:hover, {{WRAPPER}} .pubcrawl-city-card:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card, {{WRAPPER}} .pubcrawl-city-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_card_transition',
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
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card, {{WRAPPER}} .pubcrawl-city-card' => 'transition: all {{SIZE}}s ease-in-out;',
				],
			]
		);

		// Card Body Controls (.pubcrawl-city-card__body)
		$this->add_control(
			'pubcrawl_card_body_heading',
			[
				'label'     => esc_html__('Card Body', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_body_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card__body, {{WRAPPER}} .pubcrawl-city-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_body_gap',
			[
				'label'      => esc_html__('Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px'  => ['min' => 0, 'max' => 100],
					'rem' => ['min' => 0, 'max' => 10, 'step' => 0.1],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card__body, {{WRAPPER}} .pubcrawl-city-card__body' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'     => 'pubcrawl_card_body_background',
				'label'    => esc_html__('Background Overlay', 'pubcrawls-addon'),
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card__body, {{WRAPPER}} .pubcrawl-city-card__body',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_body_vertical_align',
			[
				'label'     => esc_html__('Vertical Alignment', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__('Top', 'pubcrawls-addon'),
						'icon'  => 'eicon-v-align-top',
					],
					'center'     => [
						'title' => esc_html__('Center', 'pubcrawls-addon'),
						'icon'  => 'eicon-v-align-middle',
					],
					'flex-end'   => [
						'title' => esc_html__('Bottom', 'pubcrawls-addon'),
						'icon'  => 'eicon-v-align-bottom',
					],
					'space-between' => [
						'title' => esc_html__('Space Between', 'pubcrawls-addon'),
						'icon'  => 'eicon-v-align-stretch',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card__body, {{WRAPPER}} .pubcrawl-city-card__body' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_body_horizontal_align',
			[
				'label'     => esc_html__('Horizontal Alignment', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__('Left', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => esc_html__('Center', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => esc_html__('Right', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card__body, {{WRAPPER}} .pubcrawl-city-card__body' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

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
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 300,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 1000],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-city-card__thumb img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_count_badge',
			[
				'label' => esc_html__('Count Badge', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_count_badge_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-city-event-count',
			]
		);

		$this->add_control(
			'pubcrawl_count_badge_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-event-count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_count_badge_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-event-count' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_count_badge_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-city-event-count' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_count_badge_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-city-event-count' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

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
				'selector' => '{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card__body .pubcrawl-city-card__title, {{WRAPPER}} .pubcrawl-city-card__title',
			]
		);

		$this->start_controls_tabs('tabs_title_style');

		// Normal Tab
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
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card__body .pubcrawl-city-card__title, {{WRAPPER}} .pubcrawl-city-card__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover Tab
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
					'{{WRAPPER}} .pubcrawl-archive-cities-grid .pubcrawl-city-card:hover .pubcrawl-city-card__title, {{WRAPPER}} .pubcrawl-city-card:hover .pubcrawl-city-card__title, {{WRAPPER}} .pubcrawl-city-card__title:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'pubcrawl_title_transition',
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
					'{{WRAPPER}} .pubcrawl-city-card__title' => 'transition: color {{SIZE}}s ease-in-out;',
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
		$category = isset($settings['category']) ? $settings['category'] : '';
		if (is_array($category)) {
			$category = reset($category);
		}
		$category = sanitize_text_field($category);
		$city_ids = isset($settings['city_ids']) ? $settings['city_ids'] : '';

		$today = gmdate('Ymd');

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$url_crawl_cat = isset($_GET['crawl_cat']) ? sanitize_text_field(wp_unslash($_GET['crawl_cat'])) : '';

		$category_slug = '';
		if (! empty($category)) {
			$category_slug = $category;
		} elseif (! empty($url_crawl_cat)) {
			$category_slug = $url_crawl_cat;
		} elseif (is_tax('tribe_events_cat')) {
			$queried_obj   = get_queried_object();
			$category_slug = ($queried_obj instanceof \WP_Term) ? $queried_obj->slug : '';
		} elseif (get_query_var('tribe_events_cat')) {
			$category_slug = get_query_var('tribe_events_cat');
		}

		$include_ids = [];
		if (! empty($city_ids)) {
			$include_ids = array_map('intval', explode(',', $city_ids));
		}

		$city_args = [
			'post_type'      => 'city',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		if (! empty($include_ids)) {
			$city_args['post__in'] = $include_ids;
			$city_args['orderby']  = 'post__in';
		}

		$cities = get_posts($city_args);
		if (empty($cities)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-grid-view"></span><span class="pubcrawls-placeholder-text">' . esc_html__('City Archive Grid (No cities found)', 'pubcrawls-addon') . '</span></div>';
			} else {
				echo '<p class="pubcrawls-ce-none">' . esc_html__('No cities found.', 'pubcrawls-addon') . '</p>';
			}
			return;
		}
?>
		<div class="pubcrawl-archive-cities-grid">
			<?php foreach ($cities as $city) :
				$city_id    = $city->ID;
				$city_title = $city->post_title;
				$city_url   = ! empty($category_slug)
					? add_query_arg('crawl_cat', $category_slug, get_permalink($city_id))
					: get_permalink($city_id);
				$thumb      = get_the_post_thumbnail($city_id, 'full');

				$event_args = [
					'post_type'      => 'tribe_events',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'AND',
						[
							'key'     => 'event_city',
							'value'   => '"' . $city_id . '"',
							'compare' => 'LIKE',
						],
					],
				];

				if (! empty($category_slug)) {
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					$event_args['tax_query'] = [[
						'taxonomy' => 'tribe_events_cat',
						'field'    => 'slug',
						'terms'    => $category_slug,
					]];
				}

				$event_query = new \WP_Query($event_args);
				$count = 0;

				if (! empty($event_query->posts)) {
					foreach ($event_query->posts as $event_id) {
						$event_dates = function_exists('get_field') ? get_field('event_dates', $event_id) : [];
						if (empty($event_dates) || ! is_array($event_dates)) continue;

						foreach ($event_dates as $row) {
							if (! empty($row['eventdate']) && gmdate('Ymd', strtotime($row['eventdate'])) >= $today) {
								$count++;
								break;
							}
						}
					}
				}
				wp_reset_postdata();
			?>
				<a href="<?php echo esc_url($city_url); ?>" class="pubcrawl-city-card">
					<?php if ($thumb) : ?>
						<div class="pubcrawl-city-card__thumb">
							<?php echo wp_kses_post($thumb); ?>
						</div>
					<?php endif; ?>

					<div class="pubcrawl-city-card__body">
						<span class="pubcrawl-city-event-count">
							<?php if ($count > 0) : ?>
								<?php
								/* translators: %d: Number of events. */
								echo esc_html(sprintf(_n('%d Event', '%d Events', $count, 'pubcrawls-addon'), $count));
								?>
							<?php else : ?>
								<?php esc_html_e('No Events', 'pubcrawls-addon'); ?>
							<?php endif; ?>
						</span>
						<h3 class="pubcrawl-city-card__title"><?php echo esc_html($city_title); ?></h3>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
<?php
	}
}
