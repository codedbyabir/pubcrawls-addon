<?php

/**
 * Elementor Widget: Event Sticky Ticket Bar
 *
 * Displays the sticky bottom/floating bar with date selector and ticket purchase button.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Event_Sticky extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-event-sticky';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Event Sticky Ticket Bar', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-banner';
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
		return ['pubcrawl', 'event', 'sticky', 'ticket', 'bar', 'buy', 'floating'];
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
			]
		);

		$this->add_control(
			'pubcrawl_event_id',
			[
				'label'       => esc_html__('Event Post ID', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__('Leave empty to automatically use current post ID.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_btn_text',
			[
				'label'       => esc_html__('Button Text', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('Buy Tickets', 'pubcrawls-addon'),
				'placeholder' => esc_html__('Buy Tickets', 'pubcrawls-addon'),
			]
		);

		$this->end_controls_section();

		// ── Style Section: Sticky Bar Container ───────────────────
		$this->start_controls_section(
			'section_style_bar',
			[
				'label' => esc_html__('Bar Container', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_bar_bg',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-sticky-bar' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_bar_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-sticky-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_bar_gap',
			[
				'label'      => esc_html__('Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 60],
				],
				'selectors'  => [
					'{{WRAPPER}} .pcet-sticky-bar' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_bar_border',
				'selector' => '{{WRAPPER}} .pcet-sticky-bar',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_bar_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-sticky-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_bar_box_shadow',
				'selector' => '{{WRAPPER}} .pcet-sticky-bar',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Single Date Display ───────────────────
		$this->start_controls_section(
			'section_style_single_date',
			[
				'label' => esc_html__('Single Date Display', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_single_date_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-single-date' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_single_date_typography',
				'selector' => '{{WRAPPER}} .pcet-single-date',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Dropdown ──────────────────────────────
		$this->start_controls_section(
			'section_style_select',
			[
				'label' => esc_html__('Date Select Dropdown', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_select_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-sticky-bar select' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-sticky-bar select' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-sticky-bar select' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_select_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-sticky-bar select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_select_typography',
				'selector' => '{{WRAPPER}} .pcet-sticky-bar select',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Button ────────────────────────────────
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => esc_html__('Button', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_btn_typography',
				'selector' => '{{WRAPPER}} .pcet-btn--sticky',
			]
		);

		$this->start_controls_tabs('tabs_btn_style');

		// Normal Tab
		$this->start_controls_tab(
			'tab_btn_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_btn_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-btn--sticky' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_btn_bg',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-btn--sticky' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_btn_box_shadow',
				'selector' => '{{WRAPPER}} .pcet-btn--sticky',
			]
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_btn_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_btn_color_hover',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-btn--sticky:hover, {{WRAPPER}} .pcet-btn--sticky:focus-visible' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_btn_bg_hover',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-btn--sticky:hover, {{WRAPPER}} .pcet-btn--sticky:focus-visible' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_btn_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pcet-btn--sticky:hover, {{WRAPPER}} .pcet-btn--sticky:focus-visible',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pubcrawl_btn_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-btn--sticky' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_btn_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-btn--sticky' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
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
		$event_id = ! empty($settings['event_id']) ? absint($settings['event_id']) : get_the_ID();

		if (! $event_id) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-sticky"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Event Sticky Bar (No event selected)', 'pubcrawls-addon') . '</span></div>';
			}
			return;
		}

		$dates = function_exists('get_field') ? get_field('event_dates', $event_id) : null;

		if (empty($dates) || ! is_array($dates)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-sticky"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Event Sticky Bar (No dates available for this event)', 'pubcrawls-addon') . '</span></div>';
			}
			return;
		}

		if (function_exists('pubcrawls_enqueue_ticket_assets')) {
			pubcrawls_enqueue_ticket_assets();
		} elseif (class_exists('PCET_Enqueue')) {
			PCET_Enqueue::enqueue_assets();
		}

		$rows     = function_exists('pcet_build_date_rows') ? pcet_build_date_rows($dates) : [];
		if (empty($rows)) {
			return;
		}

		$first    = $rows[0];
		$multi    = count($rows) > 1;
		$provider = $first['provider'];
		$uid      = function_exists('pcet_unique_id') ? pcet_unique_id() : ('pcet_' . substr(uniqid('', false), -6));

		if (function_exists('pcet_localize_instance')) {
			pcet_localize_instance($uid, $rows);
		}

		$id_select = esc_attr($uid . '_sticky_select');
		$id_btn    = esc_attr($uid . '_sticky_btn');
		$data_uid  = esc_attr($uid);
		$btn_text  = ! empty($settings['btn_text']) ? $settings['btn_text'] : esc_html__('Buy Tickets', 'pubcrawls-addon');
?>

		<div class="pcet-sticky-bar" data-pcet-instance="<?php echo esc_attr($data_uid); ?>">

			<?php if ($multi) : ?>
				<select
					id="<?php echo esc_attr($id_select); ?>"
					class="pcet-date-select"
					data-pcet-select="<?php echo esc_attr($data_uid); ?>">
					<?php foreach ($rows as $index => $row) : ?>
						<option
							value="<?php echo absint($index); ?>"
							data-eb-id="<?php echo esc_attr($row['eb_id']); ?>"
							data-eb-link="<?php echo esc_url($row['eb_link']); ?>"
							data-date="<?php echo esc_attr($row['date_full']); ?>"
							data-time="<?php echo esc_attr($row['time']); ?>"
							data-day="<?php echo esc_attr($row['date_day']); ?>"
							data-month="<?php echo esc_attr($row['date_month']); ?>"
							data-year="<?php echo esc_attr($row['date_year']); ?>"
							data-date-short="<?php echo esc_attr($row['date_short']); ?>"
							data-date-iso="<?php echo esc_attr($row['date_iso']); ?>"
							data-su-id="<?php echo esc_attr($row['su_id']); ?>"
							data-provider="<?php echo esc_attr($row['provider']); ?>"
							<?php selected($index, 0); ?>>
							<?php echo esc_html($row['date_label'] . ' — ' . $row['time']); ?>
						</option>
					<?php endforeach; ?>
				</select>

			<?php else : ?>
				<div class="pcet-single-date">
					<?php echo esc_html($first['date_label'] . ' — ' . $first['time']); ?>
				</div>
			<?php endif; ?>

			<div
				class="pcet-btn-wrap"
				data-pcet-btn-wrap="<?php echo esc_attr($data_uid); ?>">

				<button
					id="<?php echo esc_attr($id_btn); ?>"
					class="pcet-btn pcet-btn--sticky"
					type="button"
					data-pcet-trigger="<?php echo esc_attr($data_uid); ?>"
					data-provider="<?php echo esc_attr($first['provider']); ?>"
					data-eb-id="<?php echo esc_attr($first['eb_id']); ?>"
					data-eb-link="<?php echo esc_url($first['eb_link']); ?>"
					data-su-id="<?php echo esc_attr($first['su_id']); ?>">
					<?php echo esc_html($btn_text); ?>
				</button>

			</div>

		</div>

<?php
	}
}
