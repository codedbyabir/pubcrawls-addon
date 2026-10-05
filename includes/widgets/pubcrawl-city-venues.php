<?php

/**
 * Elementor Widget: City Venues
 *
 * Displays venues for the current city from ACF 'city_venues'.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_City_Venues extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-city-venues';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('City Venues', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-map-pin';
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
		return ['pubcrawl', 'city', 'venues', 'bars', 'clubs', 'places', 'locations'];
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
			]
		);

		$this->add_control(
			'pubcrawl_info_notice',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __('This widget displays dynamic data from the ACF <code>city_venues</code> field of the current post. No configuration required.', 'pubcrawls-addon'),
				'content_classes' => 'elementor-descriptor',
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
				'min'     => 1,
				'max'     => 4,
				'default' => 3,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
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
					'{{WRAPPER}} .pubcrawl-city-venues' => 'gap: {{SIZE}}{{UNIT}};',
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

		$this->add_control(
			'pubcrawl_card_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-item' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_card_border',
				'selector' => '{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-item',
			]
		);

		$this->add_control(
			'pubcrawl_card_hover_border_color',
			[
				'label'     => esc_html__('Hover Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-item:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'size_units' => ['px', 'vh'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 1000],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 250,
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-thumb img' => 'height: {{SIZE}}{{UNIT}};',
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
				'selector' => '{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-title a',
			]
		);

		$this->add_control(
			'pubcrawl_title_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_title_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-title a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

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
				'selector' => '{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-location',
			]
		);

		$this->add_control(
			'pubcrawl_location_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-location' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_location_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-location svg'      => 'fill: {{VALUE}}; color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-location svg path' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_directions_btn',
			[
				'label' => esc_html__('Directions Button', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_directions_btn_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination',
			]
		);

		$this->start_controls_tabs('tabs_directions_btn_style');

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
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination, {{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-item .pubcrawl-venue-destination' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination svg' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

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
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination:hover, {{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-item .pubcrawl-venue-destination:hover, {{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination:hover span' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination:hover svg' => 'fill: {{VALUE}} !important; color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_directions_btn_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_directions_btn_border',
				'selector' => '{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination',
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
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-destination, {{WRAPPER}} .pubcrawl-city-venues .pubcrawl-venue-item .pubcrawl-venue-destination' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
		$featured_posts = function_exists('get_field') ? get_field('city_venues') : [];

		if ($featured_posts && is_array($featured_posts)) : ?>
			<div class="pubcrawl-city-venues">
				<?php foreach ($featured_posts as $featured_post) :
					$venue_id   = is_object($featured_post) ? $featured_post->ID : intval($featured_post);
					$permalink  = get_permalink($venue_id);
					$thumb_id   = get_post_thumbnail_id($venue_id);
					$thumbnail  = $thumb_id
						? wp_get_attachment_image_url($thumb_id, 'full')
						: PUBCRAWLS_ADDON_URL . 'assets/img/placeholder.png';
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
								<a target="_blank" href="<?php echo esc_url($destination_url); ?>" class="pubcrawl-venue-destination">
									<svg xmlns="http://www.w3.org/2000/svg" height="14" width="14" viewBox="0 0 576 512">
										<path fill="currentColor" d="M536.4-26.3c9.8-3.5 20.6-1 28 6.3s9.8 18.2 6.3 28l-178 496.9c-5 13.9-18.1 23.1-32.8 23.1-14.2 0-27-8.6-32.3-21.7l-64.2-158c-4.5-11-2.5-23.6 5.2-32.6l94.5-112.4c5.1-6.1 4.7-15-.9-20.6s-14.6-6-20.6-.9L229.2 276.1c-9.1 7.6-21.6 9.6-32.6 5.2L38.1 216.8c-13.1-5.3-21.7-18.1-21.7-32.3 0-14.7 9.2-27.8 23.1-32.8l496.9-178z"></path>
									</svg>
									<span style="margin-left:5px;"><?php esc_html_e('Get Directions', 'pubcrawls-addon'); ?></span>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="pubcrawl-no-venues">
				<p>
					<?php esc_html_e('No venues are currently available for this city.', 'pubcrawls-addon'); ?><br>
					<?php esc_html_e('Want to see your venue featured here? Contact us today!', 'pubcrawls-addon'); ?>
				</p>
				<a target="_blank" href="<?php echo esc_url(home_url('/contact/')); ?>" class="pubcrawl-contact">
					<?php esc_html_e('Contact Us', 'pubcrawls-addon'); ?>
				</a>
			</div>
<?php endif;
	}
}
