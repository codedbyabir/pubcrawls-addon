<?php

/**
 * Elementor Widget: Gravity Forms
 *
 * Embeds any active Gravity Form with full Elementor controls over all aspects of styling:
 * form container, header title & description, field labels, inputs, selects, textareas,
 * checkboxes & radios, section breaks, field descriptions, submit buttons, multi-page steps,
 * and validation/confirmation messages.
 * Compatible with legacy Gravity Forms and modern Theme Framework / Orbital (GF 2.5+ / 2.9+).
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Gravity_Forms extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-gravity-forms';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Gravity Forms', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-form-horizontal';
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
		return ['pubcrawl', 'gravity forms', 'form', 'contact', 'gform', 'email', 'lead', 'fields', 'submit'];
	}

	/**
	 * Retrieve widget style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['pc-gravity-forms'];
	}

	/**
	 * Retrieve active Gravity Forms for the dropdown control.
	 *
	 * @return array
	 */
	protected function get_gravity_forms_list()
	{
		$options = [
			'' => esc_html__('— Select a Form —', 'pubcrawls-addon'),
		];

		if (class_exists('GFAPI')) {
			$forms = \GFAPI::get_forms(true, false, 'title', 'ASC');
			if (! empty($forms) && is_array($forms)) {
				foreach ($forms as $form) {
					$options[(string) $form['id']] = sprintf('%s (ID: %d)', $form['title'], $form['id']);
				}
			}
		} elseif (class_exists('RGFormsModel')) {
			$forms = \RGFormsModel::get_forms(null, 'title');
			if (! empty($forms) && is_array($forms)) {
				foreach ($forms as $form) {
					$options[(string) $form->id()] = sprintf('%s (ID: %d)', $form->title, $form->id);
				}
			}
		} else {
			$options[''] = esc_html__('Gravity Forms is not installed or active', 'pubcrawls-addon');
		}

		return $options;
	}

	/**
	 * Comprehensive selector for inputs, selects, and textareas.
	 *
	 * @return string
	 */
	protected function get_input_selectors()
	{
		return '{{WRAPPER}} .gform_wrapper input[type="text"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="email"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="tel"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="url"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="number"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="password"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="date"], ' .
			'{{WRAPPER}} .gform_wrapper select, ' .
			'{{WRAPPER}} .gform_wrapper textarea, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="text"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="email"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="tel"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="url"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="number"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="password"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="date"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme select, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme textarea, ' .
			'{{WRAPPER}} .gform-theme--framework input[type]:not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), ' .
			'{{WRAPPER}} .gform-theme--framework select, ' .
			'{{WRAPPER}} .gform-theme--framework textarea';
	}

	/**
	 * Selector for single-line inputs and selects (for height control).
	 *
	 * @return string
	 */
	protected function get_input_single_selectors()
	{
		return '{{WRAPPER}} .gform_wrapper input[type="text"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="email"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="tel"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="url"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="number"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="password"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="date"], ' .
			'{{WRAPPER}} .gform_wrapper select, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="text"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="email"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="tel"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="url"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="number"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="password"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input[type="date"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme select, ' .
			'{{WRAPPER}} .gform-theme--framework input[type]:not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), ' .
			'{{WRAPPER}} .gform-theme--framework select';
	}

	/**
	 * Selector for focused inputs, selects, and textareas.
	 *
	 * @return string
	 */
	protected function get_input_focus_selectors()
	{
		return '{{WRAPPER}} .gform_wrapper input[type="text"]:focus, ' .
			'{{WRAPPER}} .gform_wrapper input[type="email"]:focus, ' .
			'{{WRAPPER}} .gform_wrapper input[type="tel"]:focus, ' .
			'{{WRAPPER}} .gform_wrapper input[type="url"]:focus, ' .
			'{{WRAPPER}} .gform_wrapper input[type="number"]:focus, ' .
			'{{WRAPPER}} .gform_wrapper input[type="password"]:focus, ' .
			'{{WRAPPER}} .gform_wrapper input[type="date"]:focus, ' .
			'{{WRAPPER}} .gform_wrapper select:focus, ' .
			'{{WRAPPER}} .gform_wrapper textarea:focus, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme input:focus, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme select:focus, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme textarea:focus, ' .
			'{{WRAPPER}} .gform-theme--framework input:focus, ' .
			'{{WRAPPER}} .gform-theme--framework select:focus, ' .
			'{{WRAPPER}} .gform-theme--framework textarea:focus';
	}

	/**
	 * Selector for submit and action buttons.
	 *
	 * @return string
	 */
	protected function get_button_selectors()
	{
		return '{{WRAPPER}} .gform_button, ' .
			'{{WRAPPER}} .gform_footer input[type="submit"], ' .
			'{{WRAPPER}} .gform-footer input[type="submit"], ' .
			'{{WRAPPER}} .gform_footer button[type="submit"], ' .
			'{{WRAPPER}} .gform-footer button[type="submit"], ' .
			'{{WRAPPER}} .gform_page_footer input[type="submit"], ' .
			'{{WRAPPER}} .gform_page_footer button[type="submit"], ' .
			'{{WRAPPER}} .gform_wrapper input[type="submit"], ' .
			'{{WRAPPER}} .gform_wrapper button[type="submit"], ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer input, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer button, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_page_footer input, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_page_footer button, ' .
			'{{WRAPPER}} .gform-theme--framework.gform_wrapper input[type="submit"].button.gform_button, ' .
			'{{WRAPPER}} .gform-theme--framework.gform_wrapper .button, ' .
			'{{WRAPPER}} .gform-theme--framework.gform_wrapper .gform_button, ' .
			'{{WRAPPER}} .gform_next_button, ' .
			'{{WRAPPER}} .gform_previous_button';
	}

	/**
	 * Selector for button hover states.
	 *
	 * @return string
	 */
	protected function get_button_hover_selectors()
	{
		return '{{WRAPPER}} .gform_button:hover, ' .
			'{{WRAPPER}} .gform_footer input[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform-footer input[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform_footer button[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform-footer button[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform_page_footer input[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform_page_footer button[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform_wrapper input[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform_wrapper button[type="submit"]:hover, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer input:hover, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer button:hover, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_page_footer input:hover, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gform_page_footer button:hover, ' .
			'{{WRAPPER}} .gform-theme--framework.gform_wrapper input[type="submit"].button.gform_button:hover, ' .
			'{{WRAPPER}} .gform-theme--framework.gform_wrapper .button:hover, ' .
			'{{WRAPPER}} .gform-theme--framework.gform_wrapper .gform_button:hover, ' .
			'{{WRAPPER}} .gform_next_button:hover, ' .
			'{{WRAPPER}} .gform_previous_button:hover';
	}

	/**
	 * Selector for field labels.
	 *
	 * @return string
	 */
	protected function get_label_selectors()
	{
		return '{{WRAPPER}} .gfield_label, ' .
			'{{WRAPPER}} .gform-field-label, ' .
			'{{WRAPPER}} label.gfield_label, ' .
			'{{WRAPPER}} legend.gfield_label, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_label, ' .
			'{{WRAPPER}} .gform-theme--framework .gform-field-label';
	}

	/**
	 * Selector for sub-labels.
	 *
	 * @return string
	 */
	protected function get_sub_label_selectors()
	{
		return '{{WRAPPER}} .gfield_sub_label, ' .
			'{{WRAPPER}} .ginput_complex label, ' .
			'{{WRAPPER}} .field_sublabel_below label, ' .
			'{{WRAPPER}} .field_sublabel_above label, ' .
			'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_sub_label, ' .
			'{{WRAPPER}} .gform-theme--framework .gform-field-label--type-sub';
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{

		// =========================================================
		// CONTENT TAB — FORM SETTINGS
		// =========================================================
		$this->start_controls_section(
			'section_form_content',
			[
				'label' => esc_html__('Form Settings', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'form_id',
			[
				'label'       => esc_html__('Select Form', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_gravity_forms_list(),
				'default'     => '',
				'label_block' => true,
			]
		);

		$this->add_control(
			'display_title',
			[
				'label'        => esc_html__('Display Form Title', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'custom_title',
			[
				'label'       => esc_html__('Custom Title (Override)', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__('Leave empty to use Gravity Forms title', 'pubcrawls-addon'),
				'condition'   => ['display_title' => 'yes'],
				'label_block' => true,
			]
		);

		$this->add_control(
			'display_description',
			[
				'label'        => esc_html__('Display Form Description', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'custom_description',
			[
				'label'       => esc_html__('Custom Description (Override)', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'placeholder' => esc_html__('Leave empty to use Gravity Forms description', 'pubcrawls-addon'),
				'condition'   => ['display_description' => 'yes'],
			]
		);

		$this->add_control(
			'ajax',
			[
				'label'        => esc_html__('Enable AJAX Submission', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__('Submits the form without reloading the entire page.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'tabindex',
			[
				'label'       => esc_html__('Tab Index', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => esc_html__('Specify the starting tab index for form input navigation.', 'pubcrawls-addon'),
			]
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — FORM CONTAINER
		// =========================================================
		$this->start_controls_section(
			'section_style_form_container',
			[
				'label' => esc_html__('Form Container', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'form_max_width',
			[
				'label'      => esc_html__('Max Width', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'vw'],
				'range'      => [
					'px' => ['min' => 200, 'max' => 1400],
					'%'  => ['min' => 20, 'max' => 100],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-gravity-form-wrap' => 'max-width: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'form_alignment',
			[
				'label'     => esc_html__('Alignment', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => ['title' => esc_html__('Left', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-left'],
					'center' => ['title' => esc_html__('Center', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-center'],
					'right'  => ['title' => esc_html__('Right', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-right'],
				],
				'selectors_dictionary' => [
					'left'   => 'margin-left: 0 !important; margin-right: auto !important;',
					'center' => 'margin-left: auto !important; margin-right: auto !important;',
					'right'  => 'margin-left: auto !important; margin-right: 0 !important;',
				],
				'selectors' => [
					'{{WRAPPER}} .pc-gravity-form-wrap' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'form_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-gravity-form-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'form_margin',
			[
				'label'      => esc_html__('Margin', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-gravity-form-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'form_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-gravity-form-wrap' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'form_border',
				'selector' => '{{WRAPPER}} .pc-gravity-form-wrap',
			]
		);

		$this->add_responsive_control(
			'form_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .pc-gravity-form-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'form_box_shadow',
				'selector' => '{{WRAPPER}} .pc-gravity-form-wrap',
			]
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — FORM TITLE & DESCRIPTION
		// =========================================================
		$this->start_controls_section(
			'section_style_header',
			[
				'label' => esc_html__('Title & Description', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'header_alignment',
			[
				'label'     => esc_html__('Alignment', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => ['title' => esc_html__('Left', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-left'],
					'center' => ['title' => esc_html__('Center', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-center'],
					'right'  => ['title' => esc_html__('Right', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-right'],
				],
				'selectors' => [
					'{{WRAPPER}} .gform_heading, {{WRAPPER}} .gform-heading, {{WRAPPER}} .pc-gf-custom-header, {{WRAPPER}} .gform_wrapper .gform_heading' => 'text-align: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'header_margin_bottom',
			[
				'label'      => esc_html__('Header Bottom Spacing', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => ['px' => ['min' => 0, 'max' => 60]],
				'default'    => ['size' => 20, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gform_heading, {{WRAPPER}} .gform-heading, {{WRAPPER}} .pc-gf-custom-header, {{WRAPPER}} .gform_wrapper .gform_heading' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'heading_title_style',
			[
				'label'     => esc_html__('Title', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__('Title Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .gform_title, {{WRAPPER}} .gform-title, {{WRAPPER}} .pc-gf-custom-title, {{WRAPPER}} .gform_heading .gform_title, {{WRAPPER}} .gform-heading .gform_title' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .gform_title, {{WRAPPER}} .gform-title, {{WRAPPER}} .pc-gf-custom-title, {{WRAPPER}} .gform_heading .gform_title, {{WRAPPER}} .gform-heading .gform_title',
			]
		);

		$this->add_responsive_control(
			'title_margin_bottom',
			[
				'label'      => esc_html__('Title Spacing Below', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 0, 'max' => 30]],
				'default'    => ['size' => 8, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gform_title, {{WRAPPER}} .gform-title, {{WRAPPER}} .pc-gf-custom-title, {{WRAPPER}} .gform_heading .gform_title' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'heading_desc_style',
			[
				'label'     => esc_html__('Description', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__('Description Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6b7280',
				'selectors' => [
					'{{WRAPPER}} .gform_description, {{WRAPPER}} .gform-description, {{WRAPPER}} .pc-gf-custom-description, {{WRAPPER}} .gform_heading .gform_description, {{WRAPPER}} .gform-heading .gform_description' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .gform_description, {{WRAPPER}} .gform-description, {{WRAPPER}} .pc-gf-custom-description, {{WRAPPER}} .gform_heading .gform_description, {{WRAPPER}} .gform-heading .gform_description',
			]
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — FIELD LABELS & SUB-LABELS
		// =========================================================
		$this->start_controls_section(
			'section_style_labels',
			[
				'label' => esc_html__('Field Labels', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => esc_html__('Label Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#374151',
				'selectors' => [
					$this->get_label_selectors() => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-label-color-primary: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'label_typography',
				'selector' => $this->get_label_selectors(),
			]
		);

		$this->add_responsive_control(
			'label_margin_bottom',
			[
				'label'      => esc_html__('Label Spacing Below', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 0, 'max' => 24]],
				'default'    => ['size' => 6, 'unit' => 'px'],
				'selectors'  => [
					$this->get_label_selectors() => 'margin-bottom: {{SIZE}}{{UNIT}} !important; margin-block-end: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-label-space-primary: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'heading_required_mark',
			[
				'label'     => esc_html__('Required Asterisk (*)', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'required_color',
			[
				'label'     => esc_html__('Asterisk Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ef4444',
				'selectors' => [
					'{{WRAPPER}} .gfield_required, {{WRAPPER}} .gfield_required_asterisk, {{WRAPPER}} .gform-field-label .gfield_required, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_required' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-label-color-req: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'required_size',
			[
				'label'      => esc_html__('Asterisk Size', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .gfield_required, {{WRAPPER}} .gfield_required_asterisk, {{WRAPPER}} .gform-field-label .gfield_required, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_required' => 'font-size: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'heading_sub_labels',
			[
				'label'     => esc_html__('Sub Labels (Name / Address Fields)', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'sub_label_color',
			[
				'label'     => esc_html__('Sub Label Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => [
					$this->get_sub_label_selectors() => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'     => '--gf-ctrl-label-color-tertiary: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'sub_label_typography',
				'selector' => $this->get_sub_label_selectors(),
			]
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — INPUT FIELDS & TEXTAREAS
		// =========================================================
		$this->start_controls_section(
			'section_style_inputs',
			[
				'label' => esc_html__('Inputs & Textareas', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'input_typography',
				'selector' => $this->get_input_selectors(),
			]
		);

		$this->add_responsive_control(
			'input_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem'],
				'default'    => [
					'top'      => '12',
					'right'    => '14',
					'bottom'   => '12',
					'left'     => '14',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					$this->get_input_selectors() => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper'  => '--gf-ctrl-padding-y: {{TOP}}{{UNIT}} !important; --gf-ctrl-padding-x: {{RIGHT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'input_height',
			[
				'label'      => esc_html__('Input Height', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 30, 'max' => 80]],
				'selectors'  => [
					$this->get_input_single_selectors() => 'height: {{SIZE}}{{UNIT}} !important; min-height: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper'         => '--gf-ctrl-size: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'textarea_height',
			[
				'label'      => esc_html__('Textarea Min Height', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 60, 'max' => 300]],
				'default'    => ['size' => 130, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gform_wrapper textarea, {{WRAPPER}} .gform_wrapper.gravity-theme textarea, {{WRAPPER}} .gform-theme--framework textarea' => 'min-height: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-textarea-height: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'field_spacing',
			[
				'label'      => esc_html__('Field Row Spacing', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 0, 'max' => 50]],
				'default'    => ['size' => 18, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gfield, {{WRAPPER}} .gform_wrapper .gfield, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-form-gap-y: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'placeholder_color',
			[
				'label'     => esc_html__('Placeholder Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#9ca3af',
				'selectors' => [
					'{{WRAPPER}} .gform_wrapper input::placeholder, {{WRAPPER}} .gform_wrapper textarea::placeholder, {{WRAPPER}} .gform_wrapper.gravity-theme input::placeholder, {{WRAPPER}} .gform_wrapper.gravity-theme textarea::placeholder, {{WRAPPER}} .gform-theme--framework input::placeholder, {{WRAPPER}} .gform-theme--framework textarea::placeholder' => 'color: {{VALUE}} !important; opacity: 1 !important; -webkit-text-fill-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-placeholder-color: {{VALUE}} !important;',
				],
			]
		);

		// Normal vs Focus Tabs
		$this->start_controls_tabs('tabs_input_states');

		$this->start_controls_tab(
			'tab_input_normal',
			['label' => esc_html__('Normal', 'pubcrawls-addon')]
		);

		$this->add_control(
			'input_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1f2937',
				'selectors' => [
					$this->get_input_selectors() => 'color: {{VALUE}} !important; -webkit-text-fill-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'input_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					$this->get_input_selectors() => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-bg-color: {{VALUE}} !important; --gf-color-in-ctrl: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'input_border',
				'selector' => $this->get_input_selectors(),
			]
		);

		$this->add_responsive_control(
			'input_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'default'    => [
					'top'      => '6',
					'right'    => '6',
					'bottom'   => '6',
					'left'     => '6',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					$this->get_input_selectors() => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-radius: {{TOP}}{{UNIT}} !important; --gf-radius: {{TOP}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'input_box_shadow',
				'selector' => $this->get_input_selectors(),
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_input_focus',
			['label' => esc_html__('Focus', 'pubcrawls-addon')]
		);

		$this->add_control(
			'input_focus_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					$this->get_input_focus_selectors() => 'color: {{VALUE}} !important; -webkit-text-fill-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'        => '--gf-ctrl-color-focus: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'input_focus_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					$this->get_input_focus_selectors() => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'        => '--gf-ctrl-bg-color-focus: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'input_focus_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => [
					$this->get_input_focus_selectors() => 'border-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'        => '--gf-ctrl-border-color-focus: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'input_focus_box_shadow',
				'selector' => $this->get_input_focus_selectors(),
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — RADIO & CHECKBOXES
		// =========================================================
		$this->start_controls_section(
			'section_style_radio_checkbox',
			[
				'label' => esc_html__('Radio & Checkboxes', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'choice_label_color',
			[
				'label'     => esc_html__('Label Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#374151',
				'selectors' => [
					'{{WRAPPER}} .gchoice label, {{WRAPPER}} .gfield_checkbox label, {{WRAPPER}} .gfield_radio label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'choice_label_typography',
				'selector' => '{{WRAPPER}} .gchoice label, {{WRAPPER}} .gfield_checkbox label, {{WRAPPER}} .gfield_radio label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label',
			]
		);

		$this->add_control(
			'choice_accent_color',
			[
				'label'     => esc_html__('Checkbox / Radio Accent Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => [
					'{{WRAPPER}} .gchoice input[type="checkbox"], {{WRAPPER}} .gchoice input[type="radio"], {{WRAPPER}} .gfield_checkbox input, {{WRAPPER}} .gfield_radio input, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input' => 'accent-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-accent-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'choice_input_size',
			[
				'label'      => esc_html__('Box / Circle Size', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 12, 'max' => 30]],
				'default'    => ['size' => 18, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gchoice input[type="checkbox"], {{WRAPPER}} .gchoice input[type="radio"], {{WRAPPER}} .gfield_checkbox input, {{WRAPPER}} .gfield_radio input, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; min-width: {{SIZE}}{{UNIT}} !important; min-height: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-choice-size: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'choice_items_spacing',
			[
				'label'      => esc_html__('Spacing Between Options', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 0, 'max' => 24]],
				'default'    => ['size' => 8, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gchoice, {{WRAPPER}} .gform_wrapper.gravity-theme .gchoice' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		// =========================================================
		// Checkbox Button / Pill Style Sub-Section
		// =========================================================
		$this->add_control(
			'heading_checkbox_pill_style',
			[
				'label'     => esc_html__('Checkbox Button / Pill Style', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'checkbox_pill_style',
			[
				'label'        => esc_html__('Enable Button / Pill Style', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'prefix_class' => 'pc-gf-checkbox-pills-',
				'description'  => esc_html__('Convert standard checkboxes and radio buttons into modern badge / pill buttons.', 'pubcrawls-addon'),
				'selectors'    => [
					'{{WRAPPER}} .gfield_checkbox, {{WRAPPER}} .gfield_radio, {{WRAPPER}} .gfield--type-checkbox .ginput_container_checkbox, {{WRAPPER}} .gfield--type-radio .ginput_container_radio, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio' => 'display: flex !important; flex-direction: row !important; flex-wrap: wrap !important;',
					'{{WRAPPER}} .gfield_checkbox .gchoice, {{WRAPPER}} .gfield_radio .gchoice, {{WRAPPER}} .gchoice, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox .gchoice, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio .gchoice' => 'margin: 0 !important; display: inline-flex !important; align-items: stretch !important; gap: 0 !important;',
					'{{WRAPPER}} .gfield_checkbox input[type="checkbox"], {{WRAPPER}} .gfield_radio input[type="radio"], {{WRAPPER}} .gchoice input[type="checkbox"], {{WRAPPER}} .gchoice input[type="radio"], {{WRAPPER}} .gchoice .gfield-choice-input' => 'opacity: 0 !important; position: absolute !important; pointer-events: none !important; width: 0 !important; height: 0 !important; margin: 0 !important;',
					'{{WRAPPER}} .gfield_checkbox label, {{WRAPPER}} .gfield_radio label, {{WRAPPER}} .gchoice label, {{WRAPPER}} .gfield_checkbox .gchoice label, {{WRAPPER}} .gfield_radio .gchoice label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label, {{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label' => 'display: inline-flex !important; align-items: center !important; justify-content: center !important; text-align: center !important; cursor: pointer !important; transition: all 0.25s ease !important; margin: 0 !important; user-select: none;',
				],
			]
		);

		$this->add_responsive_control(
			'checkbox_pill_gap',
			[
				'label'      => esc_html__('Gap Between Pills', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => ['px' => ['min' => 0, 'max' => 40]],
				'default'    => ['size' => 8, 'unit' => 'px'],
				'condition'  => ['checkbox_pill_style' => 'yes'],
				'selectors'  => [
					'{{WRAPPER}} .gfield_checkbox, {{WRAPPER}} .gfield_radio, {{WRAPPER}} .gfield--type-checkbox .ginput_container_checkbox, {{WRAPPER}} .gfield--type-radio .ginput_container_radio, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio' => 'gap: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'checkbox_pill_padding',
			[
				'label'      => esc_html__('Pill Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem'],
				'default'    => [
					'top'      => '6',
					'right'    => '12',
					'bottom'   => '6',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'condition'  => ['checkbox_pill_style' => 'yes'],
				'selectors'  => [
					'{{WRAPPER}} .gfield_checkbox label, {{WRAPPER}} .gfield_radio label, {{WRAPPER}} .gchoice label, {{WRAPPER}} .gfield_checkbox .gchoice label, {{WRAPPER}} .gfield_radio .gchoice label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label, {{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'checkbox_pill_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'default'    => [
					'top'      => '999',
					'right'    => '999',
					'bottom'   => '999',
					'left'     => '999',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'condition'  => ['checkbox_pill_style' => 'yes'],
				'selectors'  => [
					'{{WRAPPER}} .gfield_checkbox label, {{WRAPPER}} .gfield_radio label, {{WRAPPER}} .gchoice label, {{WRAPPER}} .gfield_checkbox .gchoice label, {{WRAPPER}} .gfield_radio .gchoice label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label, {{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'      => 'checkbox_pill_typography',
				'selector'  => '{{WRAPPER}} .gfield_checkbox label, {{WRAPPER}} .gfield_radio label, {{WRAPPER}} .gchoice label, {{WRAPPER}} .gfield_checkbox .gchoice label, {{WRAPPER}} .gfield_radio .gchoice label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label, {{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label',
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		// Normal, Hover, and Checked State Tabs
		$this->start_controls_tabs('tabs_checkbox_pill_states', [
			'condition' => ['checkbox_pill_style' => 'yes'],
		]);

		// ── Normal Tab ────────────────────────────────────────────────────────
		$this->start_controls_tab(
			'tab_checkbox_pill_normal',
			[
				'label'     => esc_html__('Normal', 'pubcrawls-addon'),
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->add_control(
			'checkbox_pill_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B8BDC7',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gfield_checkbox label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gfield_checkbox label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gfield_radio label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gfield_radio label, ' .
					'{{WRAPPER}} .gfield_checkbox label, ' .
					'{{WRAPPER}} .gfield_radio label, ' .
					'{{WRAPPER}} .gchoice label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'checkbox_pill_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1b1f27',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gfield_checkbox label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gfield_checkbox label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gfield_radio label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gfield_radio label, ' .
					'{{WRAPPER}} .gfield_checkbox label, ' .
					'{{WRAPPER}} .gfield_radio label, ' .
					'{{WRAPPER}} .gchoice label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'           => 'checkbox_pill_border',
				'selector'       => '{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gfield_checkbox label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gfield_checkbox label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gfield_radio label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gfield_radio label, ' .
					'{{WRAPPER}} .gfield_checkbox label, ' .
					'{{WRAPPER}} .gfield_radio label, ' .
					'{{WRAPPER}} .gchoice label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label',
				'condition'      => ['checkbox_pill_style' => 'yes'],
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width'  => [
						'default' => [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' => true,
						],
					],
					'color'  => [
						'default' => '#2a2f3a',
					],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'checkbox_pill_box_shadow',
				'selector'  => '{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice label, ' .
					'{{WRAPPER}} .gfield_checkbox label, ' .
					'{{WRAPPER}} .gfield_radio label, ' .
					'{{WRAPPER}} .gchoice label',
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->end_controls_tab();

		// ── Hover Tab ─────────────────────────────────────────────────────────
		$this->start_controls_tab(
			'tab_checkbox_pill_hover',
			[
				'label'     => esc_html__('Hover', 'pubcrawls-addon'),
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->add_control(
			'checkbox_pill_hover_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gchoice input:not(:checked) ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input:not(:checked) + label, ' .
					'{{WRAPPER}} .gchoice:hover input:not(:checked) ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gfield_radio input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice label:hover, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice:hover label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice label:hover, ' .
					'{{WRAPPER}} .gfield_radio .gchoice:hover label, ' .
					'{{WRAPPER}} .gchoice label:hover, ' .
					'{{WRAPPER}} .gchoice:hover label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice label:hover, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice label:hover, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label:hover, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice:hover label' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'checkbox_pill_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#242933',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gchoice input:not(:checked) ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input:not(:checked) + label, ' .
					'{{WRAPPER}} .gchoice:hover input:not(:checked) ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gfield_radio input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice label:hover, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice:hover label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice label:hover, ' .
					'{{WRAPPER}} .gfield_radio .gchoice:hover label, ' .
					'{{WRAPPER}} .gchoice label:hover, ' .
					'{{WRAPPER}} .gchoice:hover label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice label:hover, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice label:hover, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label:hover, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice:hover label' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'checkbox_pill_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#4b5563',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gchoice input:not(:checked) ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input:not(:checked) + label, ' .
					'{{WRAPPER}} .gchoice:hover input:not(:checked) ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gfield_radio input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice label:hover, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice:hover label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice label:hover, ' .
					'{{WRAPPER}} .gfield_radio .gchoice:hover label, ' .
					'{{WRAPPER}} .gchoice label:hover, ' .
					'{{WRAPPER}} .gchoice:hover label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice label:hover, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice label:hover, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice label:hover, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice:hover label' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'checkbox_pill_hover_box_shadow',
				'selector'  => '{{WRAPPER}} .gchoice input:not(:checked) + label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input:not(:checked) + label, ' .
					'{{WRAPPER}} .gchoice label:hover, ' .
					'{{WRAPPER}} .gchoice:hover label',
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->end_controls_tab();

		// ── Checked Tab ───────────────────────────────────────────────────────
		$this->start_controls_tab(
			'tab_checkbox_pill_checked',
			[
				'label'     => esc_html__('Checked', 'pubcrawls-addon'),
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->add_control(
			'checkbox_pill_checked_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#E6B325',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked + label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_radio input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gfield_radio input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice input:checked + label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice input:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice input:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice input:checked ~ label' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'checkbox_pill_checked_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#E6B3251A',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked + label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_radio input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gfield_radio input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice input:checked + label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice input:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice input:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice input:checked ~ label' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'checkbox_pill_checked_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#E6B325',
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked + label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_radio input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gfield_radio input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice input:checked + label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice input:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_checkbox input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gfield_radio input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice input:checked + label, ' .
					'{{WRAPPER}} .gform_wrapper.gravity-theme .gchoice input:checked ~ label' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'checkbox_pill_checked_box_shadow',
				'selector'  => '{{WRAPPER}} .gchoice input:checked + label, ' .
					'{{WRAPPER}} .gchoice input:checked ~ label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox input:checked + label, ' .
					'{{WRAPPER}} .gfield_radio input:checked + label',
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->end_controls_tab();

		// ── Checked Hover Tab ─────────────────────────────────────────────────
		$this->start_controls_tab(
			'tab_checkbox_pill_checked_hover',
			[
				'label'     => esc_html__('Checked Hover', 'pubcrawls-addon'),
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->add_control(
			'checkbox_pill_checked_hover_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gchoice:hover input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gchoice:hover input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice:hover .gfield-choice-input:checked + label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice:hover input:checked + label' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'checkbox_pill_checked_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gchoice:hover input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gchoice:hover input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice:hover .gfield-choice-input:checked + label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice:hover input:checked + label' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'checkbox_pill_checked_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => ['checkbox_pill_style' => 'yes'],
				'selectors' => [
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice input[type="checkbox"]:checked ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input[type="checkbox"]:checked + label, ' .
					'{{WRAPPER}} .gchoice:hover input[type="checkbox"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice input[type="radio"]:checked ~ label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input[type="radio"]:checked + label, ' .
					'{{WRAPPER}} .gchoice:hover input[type="radio"]:checked ~ label, ' .
					'{{WRAPPER}} .gchoice .gfield-choice-input:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice:hover .gfield-choice-input:checked + label, ' .
					'{{WRAPPER}} .pc-gf-checkbox-pills-yes .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}}.pc-gf-checkbox-pills-yes .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice:hover input:checked + label' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'checkbox_pill_checked_hover_box_shadow',
				'selector'  => '{{WRAPPER}} .gchoice input:checked + label:hover, ' .
					'{{WRAPPER}} .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_checkbox .gchoice:hover input:checked + label, ' .
					'{{WRAPPER}} .gfield_radio .gchoice:hover input:checked + label',
				'condition' => ['checkbox_pill_style' => 'yes'],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — SECTION BREAKS
		// =========================================================
		$this->start_controls_section(
			'section_style_section_breaks',
			[
				'label' => esc_html__('Section Breaks', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'section_title_color',
			[
				'label'     => esc_html__('Title Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .gsection_title, {{WRAPPER}} .gform_wrapper.gravity-theme .gsection_title' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'section_title_typography',
				'selector' => '{{WRAPPER}} .gsection_title, {{WRAPPER}} .gform_wrapper.gravity-theme .gsection_title',
			]
		);

		$this->add_control(
			'section_desc_color',
			[
				'label'     => esc_html__('Description Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6b7280',
				'selectors' => [
					'{{WRAPPER}} .gsection_description, {{WRAPPER}} .gform_wrapper.gravity-theme .gsection_description' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'section_desc_typography',
				'selector' => '{{WRAPPER}} .gsection_description, {{WRAPPER}} .gform_wrapper.gravity-theme .gsection_description',
			]
		);

		$this->add_control(
			'section_divider_color',
			[
				'label'     => esc_html__('Divider Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e5e7eb',
				'selectors' => [
					'{{WRAPPER}} .gsection, {{WRAPPER}} .gform_wrapper.gravity-theme .gsection' => 'border-bottom-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'section_spacing',
			[
				'label'      => esc_html__('Margin Spacing', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .gsection, {{WRAPPER}} .gform_wrapper.gravity-theme .gsection' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — FIELD DESCRIPTIONS
		// =========================================================
		$this->start_controls_section(
			'section_style_field_descriptions',
			[
				'label' => esc_html__('Field Descriptions', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'field_desc_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => [
					'{{WRAPPER}} .gfield_description:not(.validation_message):not(.gfield_validation_message), {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_description:not(.validation_message):not(.gfield_validation_message)' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper' => '--gf-ctrl-desc-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'field_desc_typography',
				'selector' => '{{WRAPPER}} .gfield_description:not(.validation_message):not(.gfield_validation_message), {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_description:not(.validation_message):not(.gfield_validation_message)',
			]
		);

		$this->add_responsive_control(
			'field_desc_margin_top',
			[
				'label'      => esc_html__('Spacing (Margin Top)', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 0, 'max' => 20]],
				'default'    => ['size' => 6, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gfield_description:not(.validation_message):not(.gfield_validation_message), {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_description:not(.validation_message):not(.gfield_validation_message)' => 'margin-top: {{SIZE}}{{UNIT}} !important; margin-block-start: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — SUBMIT & ACTION BUTTONS
		// =========================================================
		$this->start_controls_section(
			'section_style_buttons',
			[
				'label' => esc_html__('Submit & Buttons', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_alignment',
			[
				'label'     => esc_html__('Alignment', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => ['title' => esc_html__('Left', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-left'],
					'center'     => ['title' => esc_html__('Center', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-center'],
					'flex-end'   => ['title' => esc_html__('Right', 'pubcrawls-addon'), 'icon' => 'eicon-text-align-right'],
				],
				'default'   => 'flex-start',
				'selectors_dictionary' => [
					'flex-start' => 'display: flex !important; justify-content: flex-start !important; text-align: left !important;',
					'center'     => 'display: flex !important; justify-content: center !important; text-align: center !important;',
					'flex-end'   => 'display: flex !important; justify-content: flex-end !important; text-align: right !important;',
				],
				'selectors' => [
					'{{WRAPPER}} .gform_footer, {{WRAPPER}} .gform-footer, {{WRAPPER}} .gform_page_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_page_footer' => '{{VALUE}}',
					'{{WRAPPER}} .gform_button, {{WRAPPER}} .gform_footer input[type="submit"], {{WRAPPER}} .gform-footer input[type="submit"], {{WRAPPER}} .gform_footer button[type="submit"], {{WRAPPER}} .gform-footer button[type="submit"]' => 'align-self: auto !important;',
				],
			]
		);

		$this->add_control(
			'button_full_width',
			[
				'label'        => esc_html__('Full Width Button', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'prefix_class' => 'button-full-width-',
				'selectors'    => [
					'{{WRAPPER}} .gform_footer, {{WRAPPER}} .gform-footer, {{WRAPPER}} .gform_page_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_page_footer' => 'width: 100% !important; display: flex !important;',
					'{{WRAPPER}} .gform_button, {{WRAPPER}} .gform_footer input[type="submit"], {{WRAPPER}} .gform-footer input[type="submit"], {{WRAPPER}} .gform_footer button[type="submit"], {{WRAPPER}} .gform-footer button[type="submit"], {{WRAPPER}} .gform_wrapper input[type="submit"], {{WRAPPER}} .gform_wrapper button[type="submit"]' => 'width: 100% !important; min-width: 100% !important; flex: 1 1 100% !important; text-align: center !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => $this->get_button_selectors(),
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem'],
				'default'    => [
					'top'      => '12',
					'right'    => '28',
					'bottom'   => '12',
					'left'     => '28',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					$this->get_button_selectors() => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper'   => '--gf-ctrl-btn-padding-y: {{TOP}}{{UNIT}} !important; --gf-ctrl-btn-padding-x: {{RIGHT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => esc_html__('Margin Top', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 0, 'max' => 60]],
				'default'    => ['size' => 20, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .gform_footer, {{WRAPPER}} .gform-footer, {{WRAPPER}} .gform_page_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_page_footer' => 'margin-top: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		// Button Normal / Hover Tabs
		$this->start_controls_tabs('tabs_button_states');

		$this->start_controls_tab(
			'tab_button_normal',
			['label' => esc_html__('Normal', 'pubcrawls-addon')]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					$this->get_button_selectors() => 'color: {{VALUE}} !important; -webkit-text-fill-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'   => '--gf-ctrl-btn-color-primary: {{VALUE}} !important; --gf-color-primary-contrast: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'button_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => [
					$this->get_button_selectors() => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'   => '--gf-ctrl-btn-bg-color-primary: {{VALUE}} !important; --gf-color-primary: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => $this->get_button_selectors(),
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'default'    => [
					'top'      => '6',
					'right'    => '6',
					'bottom'   => '6',
					'left'     => '6',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					$this->get_button_selectors() => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .gform_wrapper'   => '--gf-ctrl-btn-radius: {{TOP}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_box_shadow',
				'selector' => $this->get_button_selectors(),
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			['label' => esc_html__('Hover', 'pubcrawls-addon')]
		);

		$this->add_control(
			'button_hover_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					$this->get_button_hover_selectors() => 'color: {{VALUE}} !important; -webkit-text-fill-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'         => '--gf-ctrl-btn-color-hover-primary: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'button_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1d4ed8',
				'selectors' => [
					$this->get_button_hover_selectors() => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'         => '--gf-ctrl-btn-bg-color-hover-primary: {{VALUE}} !important; --gf-color-primary-darker: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					$this->get_button_hover_selectors() => 'border-color: {{VALUE}} !important;',
					'{{WRAPPER}} .gform_wrapper'         => '--gf-ctrl-btn-border-color-hover-primary: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_hover_box_shadow',
				'selector' => $this->get_button_hover_selectors(),
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — MULTI-PAGE PROGRESS BAR
		// =========================================================
		$this->start_controls_section(
			'section_style_progress_bar',
			[
				'label' => esc_html__('Multi-Page Steps / Progress', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'progress_bar_height',
			[
				'label'      => esc_html__('Progress Bar Height', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => ['px' => ['min' => 4, 'max' => 40]],
				'selectors'  => [
					'{{WRAPPER}} .gf_progressbar, {{WRAPPER}} .gform_wrapper.gravity-theme .gf_progressbar' => 'height: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'progress_track_bg',
			[
				'label'     => esc_html__('Track Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gf_progressbar, {{WRAPPER}} .gform_wrapper.gravity-theme .gf_progressbar' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'progress_fill_bg',
			[
				'label'     => esc_html__('Fill / Active Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => [
					'{{WRAPPER}} .gf_progressbar_percentage, {{WRAPPER}} .gform_wrapper.gravity-theme .gf_progressbar_percentage' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'progress_text_color',
			[
				'label'     => esc_html__('Percentage Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gf_progressbar_percentage span, {{WRAPPER}} .gf_progressbar_percentage, {{WRAPPER}} .gform_wrapper.gravity-theme .gf_progressbar_percentage' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'steps_text_color',
			[
				'label'     => esc_html__('Step Title Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gf_step, {{WRAPPER}} .gf_step_active, {{WRAPPER}} .gform_wrapper.gravity-theme .gf_step, {{WRAPPER}} .gform_wrapper.gravity-theme .gf_step_active' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — VALIDATION & CONFIRMATION MESSAGES
		// =========================================================
		$this->start_controls_section(
			'section_style_messages',
			[
				'label' => esc_html__('Validation & Messages', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'heading_val_summary',
			[
				'label' => esc_html__('Error Summary Banner', 'pubcrawls-addon'),
				'type'  => \Elementor\Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'val_summary_text_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#b91c1c',
				'selectors' => [
					'{{WRAPPER}} .gform_validation_errors, {{WRAPPER}} .validation_error, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_validation_errors, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_validation_errors h2, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_validation_errors ol li, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_validation_errors ol a' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'val_summary_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fef2f2',
				'selectors' => [
					'{{WRAPPER}} .gform_validation_errors, {{WRAPPER}} .validation_error, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_validation_errors' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'val_summary_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fecaca',
				'selectors' => [
					'{{WRAPPER}} .gform_validation_errors, {{WRAPPER}} .validation_error, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_validation_errors' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'heading_field_error',
			[
				'label'     => esc_html__('Individual Field Error', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'field_error_msg_color',
			[
				'label'     => esc_html__('Error Message Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#dc2626',
				'selectors' => [
					'{{WRAPPER}} .validation_message, {{WRAPPER}} .gfield_validation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .validation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_validation_message' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'field_error_msg_typography',
				'selector' => '{{WRAPPER}} .validation_message, {{WRAPPER}} .gfield_validation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .validation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_validation_message',
			]
		);

		$this->add_control(
			'field_error_border_color',
			[
				'label'     => esc_html__('Invalid Input Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#dc2626',
				'selectors' => [
					'{{WRAPPER}} .gfield_error input, {{WRAPPER}} .gfield_error select, {{WRAPPER}} .gfield_error textarea, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_error input, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_error select, {{WRAPPER}} .gform_wrapper.gravity-theme .gfield_error textarea' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'heading_confirmation',
			[
				'label'     => esc_html__('Success / Confirmation Message', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'confirm_msg_color',
			[
				'label'     => esc_html__('Message Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#166534',
				'selectors' => [
					'{{WRAPPER}} .gform_confirmation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_confirmation_message' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'confirm_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f0fdf4',
				'selectors' => [
					'{{WRAPPER}} .gform_confirmation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_confirmation_message' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'confirm_border',
				'selector' => '{{WRAPPER}} .gform_confirmation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_confirmation_message',
			]
		);

		$this->add_responsive_control(
			'confirm_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'default'    => [
					'top'      => '24',
					'right'    => '24',
					'bottom'   => '24',
					'left'     => '24',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .gform_confirmation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_confirmation_message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'confirm_typography',
				'selector' => '{{WRAPPER}} .gform_confirmation_message, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_confirmation_message',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on frontend and editor preview.
	 */
	protected function render()
	{
		// Enqueue widget styles to ensure they load on live preview and frontend.
		wp_enqueue_style('pc-gravity-forms');

		$settings = $this->get_settings_for_display();

		// Check if Gravity Forms is installed and active.
		if (! class_exists('GFAPI') && ! class_exists('RGFormsModel')) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pc-gf-placeholder-notice">';
				echo '<i class="eicon-alert" aria-hidden="true"></i>';
				echo '<div><strong>' . esc_html__('Gravity Forms Not Detected', 'pubcrawls-addon') . '</strong><br>' . esc_html__('Please install and activate the Gravity Forms plugin to display forms.', 'pubcrawls-addon') . '</div>';
				echo '</div>';
			}
			return;
		}

		$form_id = ! empty($settings['form_id']) ? absint($settings['form_id']) : 0;

		// If no form selected yet.
		if (empty($form_id)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pc-gf-placeholder-notice">';
				echo '<i class="eicon-form-horizontal" aria-hidden="true"></i>';
				echo '<div><strong>' . esc_html__('Gravity Forms Widget', 'pubcrawls-addon') . '</strong><br>' . esc_html__('Please select a Gravity Form from the widget settings panel on the left.', 'pubcrawls-addon') . '</div>';
				echo '</div>';
			}
			return;
		}

		$display_title       = 'yes' === $settings['display_title'];
		$display_description = 'yes' === $settings['display_description'];
		$ajax                = 'yes' === $settings['ajax'];
		$tabindex            = ! empty($settings['tabindex']) ? absint($settings['tabindex']) : 0;

		$custom_title        = ! empty($settings['custom_title']) ? trim($settings['custom_title']) : '';
		$custom_description  = ! empty($settings['custom_description']) ? trim($settings['custom_description']) : '';

		// If user customized the title/desc, let custom header render them and suppress native GF header.
		$has_custom_header   = ($display_title && ! empty($custom_title)) || ($display_description && ! empty($custom_description));
		$gf_title            = $display_title && empty($custom_title);
		$gf_description      = $display_description && empty($custom_description);

		// Container classes.
		$classes = ['pc-gravity-form-wrap'];
		if (! $display_title && empty($custom_title)) {
			$classes[] = 'hide-form-title';
		}
		if (! $display_description && empty($custom_description)) {
			$classes[] = 'hide-form-description';
		}
		if ('yes' === $settings['button_full_width']) {
			$classes[] = 'button-full-width';
			$classes[] = 'button-full-width-yes';
		}
		if ('yes' === ($settings['checkbox_pill_style'] ?? '')) {
			$classes[] = 'pc-gf-checkbox-pills';
			$classes[] = 'pc-gf-checkbox-pills-yes';
		}

		// Enqueue GF frontend scripts if available.
		if (function_exists('gravity_form_enqueue_scripts')) {
			gravity_form_enqueue_scripts($form_id, $ajax);
		}

		?>
		<div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
			<?php if ($has_custom_header) : ?>
				<div class="pc-gf-custom-header">
					<?php if ($display_title && ! empty($custom_title)) : ?>
						<h3 class="pc-gf-custom-title"><?php echo esc_html($custom_title); ?></h3>
					<?php endif; ?>
					<?php if ($display_description && ! empty($custom_description)) : ?>
						<div class="pc-gf-custom-description"><?php echo wp_kses_post(wpautop($custom_description)); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php
			$shortcode = sprintf(
				'[gravityform id="%d" title="%s" description="%s" ajax="%s" tabindex="%d"]',
				$form_id,
				$gf_title ? 'true' : 'false',
				$gf_description ? 'true' : 'false',
				$ajax ? 'true' : 'false',
				$tabindex
			);

			echo do_shortcode($shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
		<?php
	}
}
