<?php

/**
 * Elementor Widget: Steps Timeline
 *
 * Displays a vertical interactive steps process timeline with step numbers,
 * titles, descriptions, icons, and a dynamic scroll-triggered line fill effect.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Timeline extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-timeline';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Steps Timeline', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-time-line';
	}

	/**
	 * Retrieve widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories()
	{
		return ['pubcrawls'];
	}

	/**
	 * Retrieve widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords()
	{
		return ['timeline', 'steps', 'process', 'how it works', 'roadmap', 'schedule', 'pubcrawl', 'guide'];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['pc-timeline-css'];
	}

	/**
	 * Script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends()
	{
		return ['pc-timeline-js'];
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{

		// ════════════════════════════════════════════════════════════
		// ── CONTENT TAB: TIMELINE ITEMS ─────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_timeline_items',
			[
				'label' => esc_html__('Timeline Items', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'step_icon',
			[
				'label'   => esc_html__('Icon', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-shopping-cart',
					'library' => 'fa-solid',
				],
			]
		);

		$repeater->add_control(
			'step_number',
			[
				'label'       => esc_html__('Step Label', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('STEP 01', 'pubcrawls-addon'),
				'placeholder' => esc_html__('STEP 01', 'pubcrawls-addon'),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'step_title',
			[
				'label'       => esc_html__('Title', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('Step Title', 'pubcrawls-addon'),
				'placeholder' => esc_html__('Enter title...', 'pubcrawls-addon'),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'step_description',
			[
				'label'       => esc_html__('Description', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => esc_html__('Step description text explaining what happens here.', 'pubcrawls-addon'),
				'placeholder' => esc_html__('Enter description...', 'pubcrawls-addon'),
				'rows'        => 3,
			]
		);

		$repeater->add_control(
			'customize_item_colors',
			[
				'label'        => esc_html__('Custom Item Colors', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			]
		);

		$repeater->add_control(
			'item_badge_border_color',
			[
				'label'     => esc_html__('Badge Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_item_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .pc-timeline__badge' => 'border-color: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'item_badge_bg_color',
			[
				'label'     => esc_html__('Badge Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_item_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .pc-timeline__badge' => 'background-color: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'item_badge_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_item_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .pc-timeline__icon, {{WRAPPER}} {{CURRENT_ITEM}} .pc-timeline__icon i' => 'color: {{VALUE}};',
					'{{WRAPPER}} {{CURRENT_ITEM}} .pc-timeline__icon svg' => 'stroke: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'item_step_color',
			[
				'label'     => esc_html__('Step Label Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_item_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .pc-timeline__step' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_items',
			[
				'label'       => esc_html__('Items', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ step_number }}}: {{{ step_title }}}',
				'default'     => [
					[
						'step_number'      => esc_html__('STEP 01', 'pubcrawls-addon'),
						'step_title'       => esc_html__('Buy Your Ticket', 'pubcrawls-addon'),
						'step_description' => esc_html__('Choose your city and event, then purchase your Santacon ticket online.', 'pubcrawls-addon'),
						'step_icon'        => [
							'value'   => 'fas fa-shopping-cart',
							'library' => 'fa-solid',
						],
					],
					[
						'step_number'      => esc_html__('STEP 02', 'pubcrawls-addon'),
						'step_title'       => esc_html__('Check In & Get Your Wristband', 'pubcrawls-addon'),
						'step_description' => esc_html__('Meet event staff at the check-in spot to receive your event wristband and digital venue map.', 'pubcrawls-addon'),
						'step_icon'        => [
							'value'   => 'fas fa-check-circle',
							'library' => 'fa-solid',
						],
					],
					[
						'step_number'      => esc_html__('STEP 03', 'pubcrawls-addon'),
						'step_title'       => esc_html__('Visit the Bars & Enjoy', 'pubcrawls-addon'),
						'step_description' => esc_html__('Hop between participating bars, enjoy drink specials, and experience the nightlife with fellow Santacon guests.', 'pubcrawls-addon'),
						'step_icon'        => [
							'value'   => 'fas fa-map-marker-alt',
							'library' => 'fa-solid',
						],
					],
					[
						'step_number'      => esc_html__('STEP 04', 'pubcrawls-addon'),
						'step_title'       => esc_html__('After-Party Access', 'pubcrawls-addon'),
						'step_description' => esc_html__('End the night at the final venue with music, dancing, and the full Santacon group celebration.', 'pubcrawls-addon'),
						'step_icon'        => [
							'value'   => 'fas fa-cocktail',
							'library' => 'fa-solid',
						],
					],
				],
			]
		);

		$this->add_control(
			'pubcrawl_title_html_tag',
			[
				'label'     => esc_html__('Title HTML Tag', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── CONTENT TAB: SCROLL & ANIMATION SETTINGS ────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_scroll_settings',
			[
				'label' => esc_html__('Scroll & Line Fill', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_enable_scroll_fill',
			[
				'label'        => esc_html__('Scroll Line Fill Effect', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__('Line fills dynamically as the user scrolls through the timeline.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_scroll_trigger_offset',
			[
				'label'       => esc_html__('Viewport Trigger Position', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => ['%'],
				'default'     => [
					'unit' => '%',
					'size' => 50,
				],
				'range'       => [
					'%' => ['min' => 10, 'max' => 90, 'step' => 5],
				],
				'condition'   => [
					'pubcrawl_enable_scroll_fill' => 'yes',
				],
				'description' => esc_html__('Percentage from top of screen where line fill and active states trigger (50% = middle of screen).', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_editor_fill_preview',
			[
				'label'       => esc_html__('Editor Preview Fill', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'live',
				'options'     => [
					'live' => esc_html__('Live Scroll', 'pubcrawls-addon'),
					'0%'   => esc_html__('0% (Empty Line)', 'pubcrawls-addon'),
					'50%'  => esc_html__('50% (Half Filled)', 'pubcrawls-addon'),
					'100%' => esc_html__('100% (Fully Filled)', 'pubcrawls-addon'),
				],
				'description' => esc_html__('Test active styling in Elementor editor without scrolling.', 'pubcrawls-addon'),
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: LAYOUT & SPACING ─────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => esc_html__('Layout & Spacing', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_items_gap',
			[
				'label'      => esc_html__('Gap Between Steps', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'rem'],
				'default'    => [
					'unit' => 'px',
					'size' => 48,
				],
				'range'      => [
					'px' => ['min' => 12, 'max' => 140],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-items-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_content_gap',
			[
				'label'      => esc_html__('Icon to Content Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'rem'],
				'default'    => [
					'unit' => 'px',
					'size' => 28,
				],
				'range'      => [
					'px' => ['min' => 8, 'max' => 80],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-content-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_content_padding',
			[
				'label'      => esc_html__('Content Box Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_content_bg_color',
			[
				'label'     => esc_html__('Content Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-timeline__content' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_content_border_radius',
			[
				'label'      => esc_html__('Content Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline__content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_content_box_shadow',
				'selector' => '{{WRAPPER}} .pc-timeline__content',
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: CONNECTING LINE ──────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_line',
			[
				'label' => esc_html__('Connecting Line', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_line_width',
			[
				'label'      => esc_html__('Line Width', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 3,
				],
				'range'      => [
					'px' => ['min' => 1, 'max' => 16],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-line-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_line_base_color',
			[
				'label'     => esc_html__('Normal (Unfilled) Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#d1d5db',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-line-base-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_line_fill_color',
			[
				'label'     => esc_html__('Scroll Fill Color (Active)', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF0028',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-line-fill-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_line_radius',
			[
				'label'      => esc_html__('Line Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 2,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 10],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-line-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: ICON & BADGE ─────────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_badge',
			[
				'label' => esc_html__('Icon & Badge', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_badge_size',
			[
				'label'      => esc_html__('Badge Diameter', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 56,
				],
				'range'      => [
					'px' => ['min' => 32, 'max' => 120],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 20,
				],
				'range'      => [
					'px' => ['min' => 10, 'max' => 60],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-icon-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_badge_border_width',
			[
				'label'      => esc_html__('Border Thickness', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 3,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 12],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_badge_radius',
			[
				'label'      => esc_html__('Badge Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'default'    => [
					'unit' => '%',
					'size' => 50,
				],
				'range'      => [
					'%'  => ['min' => 0, 'max' => 50],
					'px' => ['min' => 0, 'max' => 60],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs('tabs_badge_state');

		// ── Normal State ──
		$this->start_controls_tab(
			'tab_badge_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_badge_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF0028',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_badge_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-bg-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_badge_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0F1115',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-icon-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_badge_shadow',
				'selector' => '{{WRAPPER}} .pc-timeline__badge',
			]
		);

		$this->end_controls_tab();

		// ── Active State (On View / Scrolled) ──
		$this->start_controls_tab(
			'tab_badge_active',
			[
				'label' => esc_html__('Active (On View)', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_badge_active_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF0028',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-active-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_badge_active_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-active-bg-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_badge_active_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0F1115',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-active-icon-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_badge_active_scale',
			[
				'label'      => esc_html__('Active Scale', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'size' => 1.05,
				],
				'range'      => [
					'px' => ['min' => 1.0, 'max' => 1.3, 'step' => 0.01],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-badge-active-scale: {{SIZE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_badge_active_shadow',
				'selector' => '{{WRAPPER}} .pc-timeline__item--active .pc-timeline__badge',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: STEP LABEL ("STEP 01") ───────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_step',
			[
				'label' => esc_html__('Step Label', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_step_typography',
				'selector' => '{{WRAPPER}} .pc-timeline__step',
			]
		);

		$this->start_controls_tabs('tabs_step_color');

		$this->start_controls_tab(
			'tab_step_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_step_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF0028',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-step-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_step_active',
			[
				'label' => esc_html__('Active (On View)', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_step_active_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF0028',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-step-active-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pubcrawl_step_bottom_space',
			[
				'label'      => esc_html__('Bottom Space', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'default'    => [
					'unit' => 'px',
					'size' => 6,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 40],
				],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-step-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: TITLE ────────────────────────────────────────
		// ════════════════════════════════════════════════════════════
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
				'selector' => '{{WRAPPER}} .pc-timeline__title',
			]
		);

		$this->start_controls_tabs('tabs_title_color');

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
				'default'   => '#0F1115',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-title-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_title_active',
			[
				'label' => esc_html__('Active (On View)', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_title_active_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0F1115',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-title-active-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pubcrawl_title_bottom_space',
			[
				'label'      => esc_html__('Bottom Space', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'default'    => [
					'unit' => 'px',
					'size' => 8,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 50],
				],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .pc-timeline' => '--pc-title-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: DESCRIPTION ──────────────────────────────────
		// ════════════════════════════════════════════════════════════
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
				'selector' => '{{WRAPPER}} .pc-timeline__description',
			]
		);

		$this->start_controls_tabs('tabs_desc_color');

		$this->start_controls_tab(
			'tab_desc_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_desc_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#4B5563',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-desc-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_desc_active',
			[
				'label' => esc_html__('Active (On View)', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_desc_active_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#4B5563',
				'selectors' => [
					'{{WRAPPER}} .pc-timeline' => '--pc-desc-active-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render()
	{
		$settings = $this->get_settings_for_display();

		// Enqueue scripts & styles for fallback.
		wp_enqueue_style('pc-timeline-css');
		wp_enqueue_script('pc-timeline-js');

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

		$items = ! empty($settings['pubcrawl_items']) && is_array($settings['pubcrawl_items'])
			? $settings['pubcrawl_items']
			: (! empty($settings['items']) && is_array($settings['items']) ? $settings['items'] : []);

		if (empty($items)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-clock"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Steps Timeline (No items added)', 'pubcrawls-addon') . '</span></div>';
			}
			return;
		}

		$title_tag      = ! empty($settings['pubcrawl_title_html_tag'])
			? \Elementor\Utils::validate_html_tag($settings['pubcrawl_title_html_tag'])
			: (! empty($settings['title_html_tag']) ? \Elementor\Utils::validate_html_tag($settings['title_html_tag']) : 'h3');
		$enable_scroll  = (! isset($settings['pubcrawl_enable_scroll_fill']) || 'yes' === $settings['pubcrawl_enable_scroll_fill']) ? 'yes' : 'no';
		$trigger_offset = isset($settings['pubcrawl_scroll_trigger_offset']['size'])
			? intval($settings['pubcrawl_scroll_trigger_offset']['size'])
			: (isset($settings['scroll_trigger_offset']['size']) ? intval($settings['scroll_trigger_offset']['size']) : 50);
		$editor_preview = ! empty($settings['pubcrawl_editor_fill_preview'])
			? sanitize_text_field($settings['pubcrawl_editor_fill_preview'])
			: (! empty($settings['editor_fill_preview']) ? sanitize_text_field($settings['editor_fill_preview']) : 'live');

		$timeline_id = 'pc-timeline-' . esc_attr($this->get_id());
		?>
		<div id="<?php echo esc_attr($timeline_id); ?>"
			 class="pc-timeline"
			 data-scroll-fill="<?php echo esc_attr($enable_scroll); ?>"
			 data-trigger-offset="<?php echo esc_attr($trigger_offset); ?>"
			 data-editor-preview="<?php echo esc_attr($editor_preview); ?>">

			<!-- Vertical Line Track and Dynamic Fill Bar -->
			<div class="pc-timeline__line-track" aria-hidden="true">
				<div class="pc-timeline__line-fill"></div>
			</div>

			<!-- Timeline Items -->
			<div class="pc-timeline__items" role="list">
				<?php foreach ($items as $index => $item) :
					$step_num  = ! empty($item['step_number']) ? $item['step_number'] : '';
					$title     = ! empty($item['step_title']) ? $item['step_title'] : '';
					$desc      = ! empty($item['step_description']) ? $item['step_description'] : '';
					$item_id   = ! empty($item['_id']) ? $item['_id'] : 'item-' . $index;
				?>
					<div class="pc-timeline__item elementor-repeater-item-<?php echo esc_attr($item_id); ?>" role="listitem">
						
						<!-- Badge / Node Column -->
						<div class="pc-timeline__node">
							<div class="pc-timeline__badge">
								<span class="pc-timeline__icon" aria-hidden="true">
									<?php
									if (! empty($item['step_icon']) && is_array($item['step_icon']) && ! empty($item['step_icon']['value'])) {
										\Elementor\Icons_Manager::render_icon($item['step_icon'], ['aria-hidden' => 'true']);
									} else {
										echo '<i class="fas fa-circle" aria-hidden="true"></i>';
									}
									?>
								</span>
							</div>
						</div>

						<!-- Content Column -->
						<div class="pc-timeline__content">
							<?php if (! empty($step_num)) : ?>
								<span class="pc-timeline__step"><?php echo esc_html($step_num); ?></span>
							<?php endif; ?>

							<?php if (! empty($title)) : ?>
								<<?php echo esc_html($title_tag); ?> class="pc-timeline__title">
									<?php echo esc_html($title); ?>
								</<?php echo esc_html($title_tag); ?>>
							<?php endif; ?>

							<?php if (! empty($desc)) : ?>
								<div class="pc-timeline__description">
									<?php echo wp_kses_post(wpautop($desc)); ?>
								</div>
							<?php endif; ?>
						</div>

					</div>
				<?php endforeach; ?>
			</div>

		</div>
		<?php
	}
}
