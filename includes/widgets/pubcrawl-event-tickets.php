<?php

/**
 * Elementor Widget: Event Tickets Card
 *
 * Displays the Eventbrite & SquadUp ticket purchase card with date/time info,
 * date picker dropdown for multi-date events, and modal checkout triggers.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Event_Tickets extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-event-tickets';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Event Tickets Card', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-ticket';
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
		return ['pubcrawl', 'event', 'tickets', 'card', 'buy', 'checkout', 'squadup', 'eventbrite'];
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
				'description' => esc_html__('Leave empty to automatically use the current post ID.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_date_layout',
			[
				'label'   => esc_html__('Event Date Selection Layout', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'v1',
				'options' => [
					'v1' => esc_html__('V1 — Dropdown (Classic)', 'pubcrawls-addon'),
					'v2' => esc_html__('V2 — Date Buttons', 'pubcrawls-addon'),
				],
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

		$this->add_control(
			'pubcrawl_secure_text',
			[
				'label'       => esc_html__('Footer Security Text', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('Secure checkout • Instant confirmation', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_show_time',
			[
				'label'        => esc_html__('Show Time Row', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_show_hr',
			[
				'label'        => esc_html__('Show Divider Line', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_show_secure',
			[
				'label'        => esc_html__('Show Security Text', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Card ──────────────────────────────────
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => esc_html__('Card Box', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_card_bg',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-card' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .pcet-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_card_border',
				'selector' => '{{WRAPPER}} .pcet-card',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_box_shadow',
				'selector' => '{{WRAPPER}} .pcet-card',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Date & Time ───────────────────────────
		$this->start_controls_section(
			'section_style_datetime',
			[
				'label' => esc_html__('Date & Time Info', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-card__icon svg' => 'stroke: {{VALUE}}; fill: none;',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => ['min' => 12, 'max' => 48],
				],
				'selectors'  => [
					'{{WRAPPER}} .pcet-card__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-card__detail strong' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_datetime_typography',
				'selector' => '{{WRAPPER}} .pcet-card__detail strong',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Date Select / Buttons ────────────────
		$this->start_controls_section(
			'section_style_select',
			[
				'label' => esc_html__('Date Selector / Buttons', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_select_typography',
				'selector' => '{{WRAPPER}} .pcet-date-select, {{WRAPPER}} .pcet-date-btn',
			]
		);

		$this->start_controls_tabs('tabs_select_style');

		$this->start_controls_tab(
			'tab_select_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_select_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-select, {{WRAPPER}} .pcet-date-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-select, {{WRAPPER}} .pcet-date-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-select, {{WRAPPER}} .pcet-date-btn' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_select_hover',
			[
				'label' => esc_html__('Hover / Focus', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_select_hover_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-select:hover, {{WRAPPER}} .pcet-date-select:focus, {{WRAPPER}} .pcet-date-btn:hover, {{WRAPPER}} .pcet-date-btn:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-select:hover, {{WRAPPER}} .pcet-date-select:focus, {{WRAPPER}} .pcet-date-btn:hover, {{WRAPPER}} .pcet-date-btn:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-select:hover, {{WRAPPER}} .pcet-date-select:focus, {{WRAPPER}} .pcet-date-btn:hover, {{WRAPPER}} .pcet-date-btn:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_select_selected',
			[
				'label' => esc_html__('Selected', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_select_selected_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-btn.is-selected' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_selected_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-btn.is-selected' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_select_selected_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-date-btn.is-selected' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pubcrawl_select_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-date-select, {{WRAPPER}} .pcet-date-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
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
				'selector' => '{{WRAPPER}} .pcet-btn',
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
					'{{WRAPPER}} .pcet-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_btn_bg',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_btn_box_shadow',
				'selector' => '{{WRAPPER}} .pcet-btn',
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
					'{{WRAPPER}} .pcet-btn:hover, {{WRAPPER}} .pcet-btn:focus-visible' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_btn_bg_hover',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-btn:hover, {{WRAPPER}} .pcet-btn:focus-visible' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_btn_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pcet-btn:hover, {{WRAPPER}} .pcet-btn:focus-visible' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_btn_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pcet-btn:hover, {{WRAPPER}} .pcet-btn:focus-visible',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'pubcrawl_btn_border',
				'selector'  => '{{WRAPPER}} .pcet-btn',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_btn_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_btn_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .pcet-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Security Text ─────────────────────────
		$this->start_controls_section(
			'section_style_secure',
			[
				'label' => esc_html__('Security Text & Divider', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_hr_color',
			[
				'label'     => esc_html__('Divider Line Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-hr-line' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'pubcrawl_secure_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-secure-text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_secure_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-secure-text',
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

		$date_layout = ! empty($settings['date_layout']) ? sanitize_key($settings['date_layout']) : 'v1';
		if ($date_layout !== 'v2') {
			$date_layout = 'v1';
		}



		$dates = function_exists('get_field') ? get_field('event_dates', $event_id) : null;

		if (empty($dates) || ! is_array($dates)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-text">' . esc_html__('No dates available for this event', 'pubcrawls-addon') . '</span></div>';
			} else {
				echo '<p class="pcet-no-dates">' . esc_html__('No dates available.', 'pubcrawls-addon') . '</p>';
			}
			return;
		}

		// Enqueue scripts and styles
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

		$id_date   = esc_attr($uid . '_date');
		$id_time   = esc_attr($uid . '_time');
		$id_select = esc_attr($uid . '_select');
		$id_btn    = esc_attr($uid . '_btn');
		$id_wrap   = esc_attr($uid . '_wrap');
		$data_uid  = esc_attr($uid);

		$btn_text  = ! empty($settings['btn_text']) ? $settings['btn_text'] : esc_html__('Buy Tickets', 'pubcrawls-addon');
		$sec_text  = ! empty($settings['secure_text']) ? $settings['secure_text'] : esc_html__('Secure checkout • Instant confirmation', 'pubcrawls-addon');
		$show_time = ($settings['show_time'] ?? 'yes') === 'yes';
		$show_sec  = ($settings['show_secure'] ?? 'yes') === 'yes';
		$show_hr   = ($settings['show_hr'] ?? 'yes') === 'yes';

		$has_ticket = false;
		foreach ($rows as $row) {
			if (! empty($row['eb_id']) || ! empty($row['eb_link']) || ! empty($row['su_id'])) {
				$has_ticket = true;
				break;
			}
		}
?>

		<div class="pcet-sidebar" data-pcet-instance="<?php echo esc_attr($data_uid); ?>">
			<div class="pcet-card">

				<?php // ── Date display ──────────────────────────── 
				?>
				<div class="pcet-card__detail">
					<span class="pcet-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E6B325" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-icon lucide-calendar">
							<path d="M8 2v4" />
							<path d="M16 2v4" />
							<rect width="18" height="18" x="3" y="4" rx="2" />
							<path d="M3 10h18" />
						</svg></span>
					<div>
						<strong
							id="<?php echo esc_attr($id_date); ?>"
							class="pcet-display-date"
							data-pcet-field="date">
							<?php echo esc_html($first['date_full']); ?>
						</strong>
					</div>
				</div>

				<?php // ── Time display ──────────────────────────── 
				?>
				<?php if ($show_time && ! empty($first['time'])) : ?>
					<div class="pcet-card__detail">
						<span class="pcet-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E6B325" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock-icon lucide-clock">
								<circle cx="12" cy="12" r="10" />
								<path d="M12 6v6l4 2" />
							</svg></span>
						<div>
							<strong
								id="<?php echo esc_attr($id_time); ?>"
								class="pcet-display-time"
								data-pcet-field="time">
								<?php echo esc_html($first['time']); ?>
							</strong>
						</div>
					</div>
				<?php endif; ?>

				<?php // ── Date selector (multi-date only) ──────── 
				?>
				<?php if ($multi) : ?>
					<?php if ('v2' === $date_layout) : ?>
						<div
							class="pcet-date-buttons"
							role="group"
							aria-label="<?php esc_attr_e('Select event date', 'pubcrawls-addon'); ?>"
							data-pcet-buttons="<?php echo esc_attr($data_uid); ?>">

							<?php foreach ($rows as $index => $row) :
								$is_selected = (0 === $index);
							?>
								<button
									type="button"
									class="pcet-date-btn <?php echo $is_selected ? 'is-selected' : ''; ?>"
									data-index="<?php echo absint($index); ?>"
									data-pcet-btn-date="<?php echo esc_attr($data_uid); ?>"
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
									aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">
									<span class="pcet-date-btn__date"><?php echo esc_html(strtoupper($row['date_label'])); ?></span>
									<?php if (! empty($row['time'])) : ?>
										<span class="pcet-date-btn__time"><?php echo esc_html($row['time']); ?></span>
									<?php endif; ?>
								</button>
							<?php endforeach; ?>

						</div>
					<?php else : ?>
						<div class="pcet-select-wrap">
							<label for="<?php echo esc_attr($id_select); ?>" class="screen-reader-text">
								<?php esc_html_e('Select event date', 'pubcrawls-addon'); ?>
							</label>
							<select
								id="<?php echo esc_attr($id_select); ?>"
								class="pcet-date-select"
								aria-label="<?php esc_attr_e('Select event date', 'pubcrawls-addon'); ?>"
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
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<?php // ── Buy Tickets button area ────────────────── 
				?>
				<?php if ($has_ticket) : ?>
					<div
						id="<?php echo esc_attr($id_wrap); ?>"
						class="pcet-btn-wrap"
						data-pcet-btn-wrap="<?php echo esc_attr($data_uid); ?>">

						<?php if ($provider !== 'squadup' && ! empty($first['eb_link'])) : ?>
							<noscript>
								<a href="<?php echo esc_url($first['eb_link']); ?>"
									class="pcet-btn pcet-btn--primary"
									rel="noopener noreferrer" target="_blank">
									<?php echo esc_html($btn_text); ?>
								</a>
							</noscript>
						<?php endif; ?>

						<button
							id="<?php echo esc_attr($id_btn); ?>"
							class="pcet-btn pcet-btn--primary"
							type="button"
							data-pcet-trigger="<?php echo esc_attr($data_uid); ?>"
							data-provider="<?php echo esc_attr($first['provider']); ?>"
							data-eb-id="<?php echo esc_attr($first['eb_id']); ?>"
							data-eb-link="<?php echo esc_url($first['eb_link']); ?>"
							data-su-id="<?php echo esc_attr($first['su_id']); ?>">
							<?php echo esc_html($btn_text); ?>
						</button>

					</div>
				<?php endif; ?>

				<?php if ($show_hr) : ?>
					<hr class="pubcrawl-hr-line">
				<?php endif; ?>

				<?php if ($show_sec) : ?>
					<p class="pubcrawl-secure-text">
						<?php echo esc_html($sec_text); ?>
					</p>
				<?php endif; ?>

			</div><!-- /.pcet-card -->
		</div><!-- /.pcet-sidebar -->

<?php
	}
}
