<?php

/**
 * Elementor Widget: Event Venues
 *
 * Displays participating venues for the current event from ACF 'event_venue'.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Event_Venues extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-event-venues';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Event Venues', 'pubcrawls-addon');
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
		return ['pubcrawl', 'event', 'venues', 'bars', 'locations', 'stops', 'crawl'];
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{
		$this->start_controls_section(
			'section_content',
			[
				'label' => __('Content', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_info_notice',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __('This widget displays dynamic data from the ACF <code>event_venue</code> field of the current post. No configuration required.', 'pubcrawls-addon'),
				'content_classes' => 'elementor-descriptor',
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
				'max'     => 4,
				'default' => 3,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-event-venues' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
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
					'{{WRAPPER}} .pubcrawl-event-venues' => 'gap: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .pubcrawl-venue-item' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_card_border',
				'selector' => '{{WRAPPER}} .pubcrawl-venue-item',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => __('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_content_padding',
			[
				'label'      => __('Content Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'size_units' => ['px', 'vh'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 1000],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-venue-thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_image_object_fit',
			[
				'label'   => __('Object Fit', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'cover',
				'options' => [
					'cover'   => __('Cover', 'pubcrawls-addon'),
					'contain' => __('Contain', 'pubcrawls-addon'),
				],
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-thumb img' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

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
				'selector' => '{{WRAPPER}} .pubcrawl-venue-title a',
			]
		);

		$this->add_control(
			'pubcrawl_title_color',
			[
				'label'     => __('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_title_hover_color',
			[
				'label'     => __('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-title a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_location',
			[
				'label' => __('Location', 'pubcrawls-addon'),
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
				'label'     => __('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-venue-location' => 'color: {{VALUE}};',
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
		$venues = function_exists('get_field') ? get_field('event_venue') : [];

		if ($venues && is_array($venues)) : ?>
			<div class="pubcrawl-event-venues">
				<?php foreach ($venues as $venue) :
					$venue_id = is_object($venue) ? $venue->ID : intval($venue);
					$permalink = get_permalink($venue_id);
					$title     = get_the_title($venue_id);
					$address   = function_exists('get_field') ? get_field('address', $venue_id) : '';
				?>
					<div class="pubcrawl-venue-item">
						<div class="pubcrawl-venue-thumb">
							<a href="<?php echo esc_url($permalink); ?>">
								<?php if (has_post_thumbnail($venue_id)) : ?>
									<?php echo wp_kses_post(get_the_post_thumbnail($venue_id, 'large')); ?>
								<?php else : ?>
									<img src="<?php echo esc_url(PUBCRAWLS_ADDON_URL . 'assets/img/placeholder.png'); ?>" alt="<?php echo esc_attr($title); ?>">
								<?php endif; ?>
							</a>
						</div>
						<div class="pubcrawl-venue-content">
							<h3>
								<a class="pubcrawl-venue-title" href="<?php echo esc_url($permalink); ?>">
									<?php echo esc_html($title); ?>
								</a>
							</h3>
							<?php if ($address) : ?>
								<p class="pubcrawl-venue-location">
									<svg xmlns="http://www.w3.org/2000/svg" height="14" width="10.5" viewBox="0 0 384 512">
										<path fill="#e6b325" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z" />
									</svg>
									<?php echo esc_html($address); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php elseif (\Elementor\Plugin::$instance->editor->is_edit_mode()) : ?>
			<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-text"><?php esc_html_e('No Venues for this Event', 'pubcrawls-addon'); ?></span></div>
<?php endif;
	}
}
