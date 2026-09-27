<?php

/**
 * Elementor Widget: Job Cities
 *
 * Displays job cities taxonomy terms for a job post.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Job_Cities extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-job-cities';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Job Cities', 'pubcrawls-addon');
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-map-pin';
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
		return ['pubcrawl', 'job', 'careers', 'cities', 'hiring', 'employment', 'positions'];
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
				'label'      => esc_html__('Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 100],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-job-cities' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_badge',
			[
				'label' => esc_html__('Badge', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_badge_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-job-city',
			]
		);

		$this->add_control(
			'pubcrawl_badge_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-job-city' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_badge_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-job-city' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_badge_border',
				'selector' => '{{WRAPPER}} .pubcrawl-job-city',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_badge_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-job-city' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_badge_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-job-city' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
		$post_id  = ! empty($settings['post_id']) ? intval($settings['post_id']) : get_the_ID();

		$terms = function_exists('get_field') ? get_field('job_cities', $post_id) : [];

		if (empty($terms) || ! is_array($terms)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-location-alt"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Job Cities (No job_cities field on this post)', 'pubcrawls-addon') . '</span></div>';
			}
			return;
		}
?>
		<div class="pubcrawl-job-cities">
			<?php foreach ($terms as $term) :
				$term_name = is_object($term) ? $term->name : strval($term);
			?>
				<span class="pubcrawl-job-city"><?php echo esc_html($term_name); ?></span>
			<?php endforeach; ?>
		</div>
<?php
	}
}
