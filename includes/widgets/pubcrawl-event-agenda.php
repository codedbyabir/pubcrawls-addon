<?php

/**
 * Elementor Widget: Event Agenda
 *
 * Displays the agenda steps for an event from ACF 'event_agenda'.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Event_Agenda extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-event-agenda';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Event Agenda', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-table-of-contents';
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
		return ['pubcrawl', 'event', 'agenda', 'schedule', 'timeline', 'itinerary', 'stops'];
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
			'pubcrawl_post_id',
			[
				'label'       => esc_html__('Post ID', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__('Post ID — leave empty to use current post', 'pubcrawls-addon'),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => esc_html__('Layout', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_layout_gap',
			[
				'label'      => esc_html__('Gap between steps', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 100],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-agenda' => 'gap: {{SIZE}}{{UNIT}}; display: grid;',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_step_card',
			[
				'label' => esc_html__('Step Card', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_step_card_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-step' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_step_card_border',
				'selector' => '{{WRAPPER}} .pubcrawl-step',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_step_card_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-step' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_step_card_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-step' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_time_badge',
			[
				'label' => esc_html__('Time Badge', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_time_badge_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-time-badge',
			]
		);

		$this->add_control(
			'pubcrawl_time_badge_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-time-badge' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_time_badge_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-time-badge' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_time_badge_border',
				'selector' => '{{WRAPPER}} .pubcrawl-time-badge',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_time_badge_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-time-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_time_badge_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-time-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'selector' => '{{WRAPPER}} .pubcrawl-card-title',
			]
		);

		$this->add_control(
			'pubcrawl_title_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-card-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_description',
			[
				'label' => esc_html__('Description', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_description_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-card-desc',
			]
		);

		$this->add_control(
			'pubcrawl_description_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-card-desc' => 'color: {{VALUE}};',
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
		$post_id_val = ! empty($settings['post_id']) ? $settings['post_id'] : ($settings['id'] ?? '');
		$post_id     = ! empty($post_id_val) ? intval($post_id_val) : get_the_ID();

		if (! function_exists('have_rows') || ! have_rows('event_agenda', $post_id)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-text">' . esc_html__('Event Agenda (No event_agenda repeater rows found)', 'pubcrawls-addon') . '</span></div>';
			}
			return;
		}
?>
		<div class="pubcrawl-agenda">
			<?php while (have_rows('event_agenda', $post_id)) : the_row(); ?>
				<div class="pubcrawl-step">
					<div class="pubcrawl-time-badge">
						<?php echo esc_html(get_sub_field('time')); ?>
					</div>
					<div class="pubcrawl-card">
						<h3 class="pubcrawl-card-title"><?php echo esc_html(get_sub_field('title')); ?></h3>
						<p class="pubcrawl-card-desc"><?php echo wp_kses_post(get_sub_field('description')); ?></p>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
<?php
	}
}
