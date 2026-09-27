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


		$this->start_controls_section(
			'section_style_list',
			[
				'label' => __('List', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_list_typography',
				'selector' => '{{WRAPPER}} .pubcrawls-city-list a, {{WRAPPER}} .pubcrawls-popular-city-list a',
			]
		);

		$this->add_control(
			'pubcrawl_list_color',
			[
				'label'     => __('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-list a, {{WRAPPER}} .pubcrawls-popular-city-list a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_list_hover_color',
			[
				'label'     => __('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-city-list a:hover, {{WRAPPER}} .pubcrawls-popular-city-list a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_list_item_spacing',
			[
				'label'      => __('Item Spacing', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 100],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-city-list li, {{WRAPPER}} .pubcrawls-popular-city-list li' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
