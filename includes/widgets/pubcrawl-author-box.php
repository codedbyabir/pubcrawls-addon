<?php

/**
 * Elementor Widget: Author Box
 *
 * Displays a high-fidelity Author Box card powered by ACF User meta:
 * - Author Image ('author_image')
 * - Author Name ('display_name')
 * - Author Bio ('author_bio' wysiwyg)
 * - Social Links: Facebook ('facebook_url'), Instagram ('instagram_url'),
 *   X ('x_url'), LinkedIn ('linkdin_url')
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Image_Size;
use Elementor\Repeater;
use Elementor\Icons_Manager;

class Pubcrawls_Author_Box extends Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-author-box';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Author Box', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-person';
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
		return ['author', 'author box', 'bio', 'profile', 'user', 'team', 'social', 'acf', 'pubcrawl'];
	}

	/**
	 * Enqueue styles for this widget.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['pc-author-box-css'];
	}

	/**
	 * Get site users list for the dropdown control.
	 *
	 * @return array
	 */
	protected function get_users_list(): array
	{
		$options = [];
		$users   = get_users([
			'number'  => 100,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		]);

		foreach ($users as $user) {
			$options[$user->ID] = sprintf('%s (%s)', $user->display_name, $user->user_login);
		}

		return $options;
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{

		/* =========================================================
		 * CONTENT TAB — AUTHOR SOURCE & DATA
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_author',
			[
				'label' => esc_html__('Author Source', 'pubcrawls-addon'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_ab_source',
			[
				'label'   => esc_html__('Source', 'pubcrawls-addon'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'current'  => esc_html__('Current Post Author (Default)', 'pubcrawls-addon'),
					'specific' => esc_html__('Select Specific User', 'pubcrawls-addon'),
					'custom'   => esc_html__('Custom Manual Content', 'pubcrawls-addon'),
				],
				'default' => 'current',
			]
		);

		$this->add_control(
			'pubcrawl_ab_user_id',
			[
				'label'     => esc_html__('Select User', 'pubcrawls-addon'),
				'type'      => Controls_Manager::SELECT,
				'options'   => $this->get_users_list(),
				'condition' => [
					'pubcrawl_ab_source' => 'specific',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_custom_name',
			[
				'label'       => esc_html__('Author Name', 'pubcrawls-addon'),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Brittany Conley',
				'placeholder' => esc_html__('Enter author name', 'pubcrawls-addon'),
				'condition'   => [
					'pubcrawl_ab_source' => 'custom',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_custom_image',
			[
				'label'     => esc_html__('Author Image', 'pubcrawls-addon'),
				'type'      => Controls_Manager::MEDIA,
				'condition' => [
					'pubcrawl_ab_source' => 'custom',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_custom_bio',
			[
				'label'       => esc_html__('Author Bio', 'pubcrawls-addon'),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => 'Brittany Conley is a digital marketing and national event professional with extensive experience in experiential marketing, event production, sponsorships, and nightlife entertainment.',
				'condition'   => [
					'pubcrawl_ab_source' => 'custom',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_name_tag',
			[
				'label'   => esc_html__('Name HTML Tag', 'pubcrawls-addon'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
				],
				'default' => 'h3',
			]
		);

		$this->add_control(
			'pubcrawl_ab_link_author_name',
			[
				'label'        => esc_html__('Link Name to Author Archive', 'pubcrawls-addon'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'no',
				'condition'    => [
					'pubcrawl_ab_source!' => 'custom',
				],
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * CONTENT TAB — LABELS & HEADINGS
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_labels',
			[
				'label' => esc_html__('Labels & Headings', 'pubcrawls-addon'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_ab_show_pre_title',
			[
				'label'        => esc_html__('Show Pre-title', 'pubcrawls-addon'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'pubcrawls-addon'),
				'label_off'    => esc_html__('Hide', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_ab_pre_title',
			[
				'label'     => esc_html__('Pre-title Text', 'pubcrawls-addon'),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__('ABOUT THE AUTHOR:', 'pubcrawls-addon'),
				'condition' => [
					'pubcrawl_ab_show_pre_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_show_follow_title',
			[
				'label'        => esc_html__('Show Follow Label', 'pubcrawls-addon'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'pubcrawls-addon'),
				'label_off'    => esc_html__('Hide', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_ab_follow_title',
			[
				'label'     => esc_html__('Follow Label Text', 'pubcrawls-addon'),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__('FOLLOW US:', 'pubcrawls-addon'),
				'condition' => [
					'pubcrawl_ab_show_follow_title' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * CONTENT TAB — SOCIAL MEDIA PROFILES
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_social',
			[
				'label' => esc_html__('Social Media Profiles', 'pubcrawls-addon'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_ab_show_social',
			[
				'label'        => esc_html__('Show Social Icons', 'pubcrawls-addon'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'pubcrawls-addon'),
				'label_off'    => esc_html__('Hide', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_ab_social_target',
			[
				'label'        => esc_html__('Open Links in New Tab', 'pubcrawls-addon'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_notice_social',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__('Social URLs are pulled automatically from the user ACF fields (Facebook, Instagram, X, LinkedIn). You can customize icons and URLs below.', 'pubcrawls-addon'),
				'content_classes' => 'elementor-descriptor',
				'condition'       => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		// ── Facebook ──
		$this->add_control(
			'pubcrawl_ab_heading_fb',
			[
				'label'     => esc_html__('Facebook', 'pubcrawls-addon'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_icon_fb',
			[
				'label'       => esc_html__('Facebook Icon', 'pubcrawls-addon'),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fab fa-facebook-f',
					'library' => 'fa-brands',
				],
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_custom_fb',
			[
				'label'       => esc_html__('Facebook URL Override', 'pubcrawls-addon'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://facebook.com/...',
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		// ── Instagram ──
		$this->add_control(
			'pubcrawl_ab_heading_ig',
			[
				'label'     => esc_html__('Instagram', 'pubcrawls-addon'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_icon_ig',
			[
				'label'       => esc_html__('Instagram Icon', 'pubcrawls-addon'),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fab fa-instagram',
					'library' => 'fa-brands',
				],
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_custom_ig',
			[
				'label'       => esc_html__('Instagram URL Override', 'pubcrawls-addon'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://instagram.com/...',
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		// ── X (Twitter) ──
		$this->add_control(
			'pubcrawl_ab_heading_x',
			[
				'label'     => esc_html__('X (Twitter)', 'pubcrawls-addon'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_icon_x',
			[
				'label'       => esc_html__('X Icon', 'pubcrawls-addon'),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fab fa-x-twitter',
					'library' => 'fa-brands',
				],
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_custom_x',
			[
				'label'       => esc_html__('X (Twitter) URL Override', 'pubcrawls-addon'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://x.com/...',
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		// ── LinkedIn ──
		$this->add_control(
			'pubcrawl_ab_heading_li',
			[
				'label'     => esc_html__('LinkedIn', 'pubcrawls-addon'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_icon_li',
			[
				'label'       => esc_html__('LinkedIn Icon', 'pubcrawls-addon'),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fab fa-linkedin-in',
					'library' => 'fa-brands',
				],
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_custom_li',
			[
				'label'       => esc_html__('LinkedIn URL Override', 'pubcrawls-addon'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://linkedin.com/in/...',
				'condition'   => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		// ── Additional Social Profiles ──
		$this->add_control(
			'pubcrawl_ab_heading_extra',
			[
				'label'     => esc_html__('Additional Social Profiles', 'pubcrawls-addon'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'pubcrawl_ab_item_title',
			[
				'label'       => esc_html__('Title / Platform', 'pubcrawls-addon'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Website', 'pubcrawls-addon'),
				'placeholder' => esc_html__('e.g. YouTube, TikTok, Threads, Website', 'pubcrawls-addon'),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'pubcrawl_ab_item_url',
			[
				'label'       => esc_html__('URL', 'pubcrawls-addon'),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'https://...',
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'pubcrawl_ab_item_icon',
			[
				'label'   => esc_html__('Icon', 'pubcrawls-addon'),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-globe',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_extra_social_items',
			[
				'label'         => esc_html__('Additional Social Links', 'pubcrawls-addon'),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'title_field'   => '{{{ pubcrawl_ab_item_title }}}',
				'prevent_empty' => false,
				'condition'     => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * STYLE TAB — CARD CONTAINER
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_style_card',
			[
				'label' => esc_html__('Card Box', 'pubcrawls-addon'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'pubcrawl_ab_card_bg',
				'label'    => esc_html__('Background', 'pubcrawls-addon'),
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .pubcrawl-author-box',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_ab_card_border',
				'label'    => esc_html__('Border', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pubcrawl-author-box',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_card_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_card_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_ab_card_box_shadow',
				'label'    => esc_html__('Box Shadow', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pubcrawl-author-box',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_card_gap',
			[
				'label'      => esc_html__('Columns Gap', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => [
						'min' => 10,
						'max' => 80,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * STYLE TAB — AUTHOR IMAGE
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_style_image',
			[
				'label' => esc_html__('Author Image', 'pubcrawls-addon'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_image_size',
			[
				'label'      => esc_html__('Image Size', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => [
						'min' => 60,
						'max' => 260,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__avatar-wrap' => 'flex: 0 0 {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_image_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__avatar-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_ab_image_border',
				'label'    => esc_html__('Border', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pubcrawl-author-box__avatar-wrap',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_ab_image_box_shadow',
				'label'    => esc_html__('Box Shadow', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pubcrawl-author-box__avatar-wrap',
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * STYLE TAB — PRE-TITLE (ABOUT THE AUTHOR)
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_style_pretitle',
			[
				'label'     => esc_html__('Pre-title', 'pubcrawls-addon'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'pubcrawl_ab_show_pre_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_pre_title_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__pretitle' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_ab_pre_title_typography',
				'label'    => esc_html__('Typography', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pubcrawl-author-box__pretitle',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_pre_title_margin',
			[
				'label'      => esc_html__('Bottom Margin', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__pretitle' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * STYLE TAB — AUTHOR NAME
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_style_name',
			[
				'label' => esc_html__('Author Name', 'pubcrawls-addon'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_ab_name_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e50914',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_name_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__name a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_ab_name_typography',
				'label'    => esc_html__('Typography', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pubcrawl-author-box__name',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_name_margin',
			[
				'label'      => esc_html__('Bottom Margin', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__name' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * STYLE TAB — AUTHOR BIO
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_style_bio',
			[
				'label' => esc_html__('Author Bio', 'pubcrawls-addon'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_ab_bio_color',
			[
				'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4b5563',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__bio' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_bio_link_color',
			[
				'label'     => esc_html__('Link Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__bio a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_ab_bio_typography',
				'label'    => esc_html__('Typography', 'pubcrawls-addon'),
				'selector' => '{{WRAPPER}} .pubcrawl-author-box__bio',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_bio_margin',
			[
				'label'      => esc_html__('Bottom Margin', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__bio' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		/* =========================================================
		 * STYLE TAB — SOCIAL & FOLLOW SECTION
		 * ========================================================= */
		$this->start_controls_section(
			'pubcrawl_ab_section_style_social',
			[
				'label'     => esc_html__('Social Icons & Follow Label', 'pubcrawls-addon'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'pubcrawl_ab_show_social' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_follow_color',
			[
				'label'     => esc_html__('Follow Label Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__follow-label' => 'color: {{VALUE}};',
				],
				'condition' => [
					'pubcrawl_ab_show_follow_title' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'pubcrawl_ab_follow_typography',
				'label'     => esc_html__('Follow Label Typography', 'pubcrawls-addon'),
				'selector'  => '{{WRAPPER}} .pubcrawl-author-box__follow-label',
				'condition' => [
					'pubcrawl_ab_show_follow_title' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_social_btn_size',
			[
				'label'      => esc_html__('Button Size', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 28,
						'max' => 70,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_social_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 12,
						'max' => 36,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__social-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .pubcrawl-author-box__social-link i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .pubcrawl-author-box__social-link svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_social_gap',
			[
				'label'      => esc_html__('Icons Gap', 'pubcrawls-addon'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 4,
						'max' => 30,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__social-list' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_ab_social_btn_radius',
			[
				'label'      => esc_html__('Button Radius', 'pubcrawls-addon'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs('pubcrawl_ab_tabs_social_btn');

		// Normal tab
		$this->start_controls_tab(
			'pubcrawl_ab_tab_social_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_ab_social_btn_bg',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f3f4f6',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_social_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-author-box__social-link i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-author-box__social-link svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_social_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7eb',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover tab
		$this->start_controls_tab(
			'pubcrawl_ab_tab_social_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_ab_social_btn_hover_bg',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_social_icon_hover_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-author-box__social-link:hover i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pubcrawl-author-box__social-link:hover svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_ab_social_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#111827',
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-author-box__social-link:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Resolve author data from post, ACF, or custom settings.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function get_author_data(array $settings): array
	{
		$source = $settings['pubcrawl_ab_source'] ?? 'current';

		// Custom source
		if ('custom' === $source) {
			$image_url = ! empty($settings['pubcrawl_ab_custom_image']['url']) ? $settings['pubcrawl_ab_custom_image']['url'] : '';
			return [
				'id'            => 0,
				'name'          => $settings['pubcrawl_ab_custom_name'] ?? 'Brittany Conley',
				'bio'           => $settings['pubcrawl_ab_custom_bio'] ?? '',
				'image'         => $image_url,
				'archive_url'   => '',
				'facebook_url'  => $settings['pubcrawl_ab_custom_fb'] ?? '',
				'instagram_url' => $settings['pubcrawl_ab_custom_ig'] ?? '',
				'x_url'         => $settings['pubcrawl_ab_custom_x'] ?? '',
				'linkdin_url'   => $settings['pubcrawl_ab_custom_li'] ?? '',
			];
		}

		// Resolve User ID
		$user_id = 0;
		if ('specific' === $source && ! empty($settings['pubcrawl_ab_user_id'])) {
			$user_id = (int) $settings['pubcrawl_ab_user_id'];
		} else {
			// Current post author
			$post = get_post();
			if ($post && ! empty($post->post_author)) {
				$user_id = (int) $post->post_author;
			} elseif (is_author()) {
				$user_id = (int) get_queried_object_id();
			} else {
				$user_id = (int) get_current_user_id();
			}
		}

		if (! $user_id) {
			$user_id = 1;
		}

		$user = get_userdata($user_id);
		$name = $user ? $user->display_name : 'Author';

		// 1. Bio
		$bio = '';
		if (function_exists('get_field')) {
			$bio = (string) get_field('author_bio', 'user_' . $user_id);
		}
		if (empty($bio)) {
			$bio = (string) get_user_meta($user_id, 'author_bio', true);
		}
		if (empty($bio)) {
			$bio = (string) get_the_author_meta('description', $user_id);
		}

		// 2. Image
		$image_url = '';
		$raw_img   = null;
		if (function_exists('get_field')) {
			$raw_img = get_field('author_image', 'user_' . $user_id);
		}
		if (empty($raw_img)) {
			$raw_img = get_user_meta($user_id, 'author_image', true);
		}

		if (! empty($raw_img)) {
			if (is_array($raw_img) && ! empty($raw_img['url'])) {
				$image_url = $raw_img['url'];
			} elseif (is_numeric($raw_img)) {
				$image_url = wp_get_attachment_image_url((int) $raw_img, 'full') ?: '';
			} elseif (is_string($raw_img) && filter_var($raw_img, FILTER_VALIDATE_URL)) {
				$image_url = $raw_img;
			}
		}

		if (empty($image_url)) {
			$image_url = get_avatar_url($user_id, ['size' => 300]) ?: '';
		}

		// 3. Social URLs
		$fb = '';
		$ig = '';
		$x  = '';
		$li = '';

		if (function_exists('get_field')) {
			$fb = (string) get_field('facebook_url', 'user_' . $user_id);
			$ig = (string) get_field('instagram_url', 'user_' . $user_id);
			$x  = (string) get_field('x_url', 'user_' . $user_id);
			$li = (string) get_field('linkdin_url', 'user_' . $user_id);
		}

		if (empty($fb)) {
			$fb = (string) get_user_meta($user_id, 'facebook_url', true);
		}
		if (empty($ig)) {
			$ig = (string) get_user_meta($user_id, 'instagram_url', true);
		}
		if (empty($x)) {
			$x = (string) get_user_meta($user_id, 'x_url', true) ?: (string) get_user_meta($user_id, 'twitter_url', true);
		}
		if (empty($li)) {
			$li = (string) get_user_meta($user_id, 'linkdin_url', true) ?: (string) get_user_meta($user_id, 'linkedin_url', true);
		}

		// Setting overrides take precedence if filled
		if (! empty($settings['pubcrawl_ab_custom_fb'])) {
			$fb = $settings['pubcrawl_ab_custom_fb'];
		}
		if (! empty($settings['pubcrawl_ab_custom_ig'])) {
			$ig = $settings['pubcrawl_ab_custom_ig'];
		}
		if (! empty($settings['pubcrawl_ab_custom_x'])) {
			$x = $settings['pubcrawl_ab_custom_x'];
		}
		if (! empty($settings['pubcrawl_ab_custom_li'])) {
			$li = $settings['pubcrawl_ab_custom_li'];
		}

		return [
			'id'            => $user_id,
			'name'          => $name,
			'bio'           => $bio,
			'image'         => $image_url,
			'archive_url'   => get_author_posts_url($user_id),
			'facebook_url'  => trim($fb),
			'instagram_url' => trim($ig),
			'x_url'         => trim($x),
			'linkdin_url'   => trim($li),
		];
	}

	/**
	 * Helper to render social icon with fallback SVG if not set.
	 *
	 * @param array  $icon_setting Elementor icon value.
	 * @param string $fallback     Built-in SVG fallback name.
	 */
	protected function render_social_icon($icon_setting, string $fallback = '')
	{
		if (! empty($icon_setting['value']) && class_exists('\Elementor\Icons_Manager')) {
			Icons_Manager::render_icon($icon_setting, [
				'aria-hidden' => 'true',
				'class'       => 'pubcrawl-author-box__social-icon',
			]);
			return;
		}

		switch ($fallback) {
			case 'facebook':
				?>
				<svg class="pubcrawl-author-box__social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
					<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
				</svg>
				<?php
				break;
			case 'instagram':
				?>
				<svg class="pubcrawl-author-box__social-icon pubcrawl-author-box__social-icon--ig-fallback" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
					<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
					<path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
					<circle cx="17.5" cy="6.5" r="1.5"/>
				</svg>
				<?php
				break;
			case 'x':
				?>
				<svg class="pubcrawl-author-box__social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
					<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
				</svg>
				<?php
				break;
			case 'linkedin':
				?>
				<svg class="pubcrawl-author-box__social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
					<path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
				</svg>
				<?php
				break;
			default:
				?>
				<i class="fas fa-link pubcrawl-author-box__social-icon" aria-hidden="true"></i>
				<?php
				break;
		}
	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render()
	{
		$settings = $this->get_settings_for_display();
		$data     = $this->get_author_data($settings);

		// Fallback preview values in Elementor editor mode if user fields are empty
		if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
			if (empty($data['name'])) {
				$data['name'] = 'Brittany Conley';
			}
			if (empty($data['bio'])) {
				$data['bio'] = '<p>Brittany Conley is a digital marketing and national event professional with extensive experience in experiential marketing, event production, sponsorships, and nightlife entertainment. As a key member of the team, Brittany helps coordinate and execute events across cities nationwide.</p>';
			}
			if (empty($data['image'])) {
				$data['image'] = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80';
			}
			if (empty($data['facebook_url']) && empty($data['instagram_url']) && empty($data['x_url']) && empty($data['linkdin_url'])) {
				$data['facebook_url']  = '#';
				$data['instagram_url'] = '#';
				$data['x_url']         = '#';
			}
		}

		$show_pretitle = ('yes' === ($settings['pubcrawl_ab_show_pre_title'] ?? 'yes'));
		$pretitle_text = $settings['pubcrawl_ab_pre_title'] ?? esc_html__('ABOUT THE AUTHOR:', 'pubcrawls-addon');

		$show_follow   = ('yes' === ($settings['pubcrawl_ab_show_follow_title'] ?? 'yes'));
		$follow_text   = $settings['pubcrawl_ab_follow_title'] ?? esc_html__('FOLLOW US:', 'pubcrawls-addon');

		$show_social   = ('yes' === ($settings['pubcrawl_ab_show_social'] ?? 'yes'));
		$target_blank  = ('yes' === ($settings['pubcrawl_ab_social_target'] ?? 'yes'));
		$target_attr   = $target_blank ? ' target="_blank" rel="noopener noreferrer"' : '';

		$name_tag      = ! empty($settings['pubcrawl_ab_name_tag']) ? sanitize_key($settings['pubcrawl_ab_name_tag']) : 'h3';
		$link_name     = ('yes' === ($settings['pubcrawl_ab_link_author_name'] ?? 'no')) && ! empty($data['archive_url']);

		$extra_items   = ! empty($settings['pubcrawl_ab_extra_social_items']) && is_array($settings['pubcrawl_ab_extra_social_items']) ? $settings['pubcrawl_ab_extra_social_items'] : [];
		$has_extra     = false;
		foreach ($extra_items as $extra) {
			if (! empty($extra['pubcrawl_ab_item_url'])) {
				$has_extra = true;
				break;
			}
		}

		$has_social    = ! empty($data['facebook_url']) || ! empty($data['instagram_url']) || ! empty($data['x_url']) || ! empty($data['linkdin_url']) || $has_extra;
		?>
		<div class="pubcrawl-author-box">

			<?php if (! empty($data['image'])) : ?>
				<div class="pubcrawl-author-box__avatar-wrap">
					<img src="<?php echo esc_url($data['image']); ?>" alt="<?php echo esc_attr($data['name']); ?>" class="pubcrawl-author-box__avatar" loading="lazy" />
				</div>
			<?php endif; ?>

			<div class="pubcrawl-author-box__content">

				<?php if ($show_pretitle && ! empty($pretitle_text)) : ?>
					<div class="pubcrawl-author-box__pretitle">
						<?php echo esc_html($pretitle_text); ?>
					</div>
				<?php endif; ?>

				<?php if (! empty($data['name'])) : ?>
					<<?php echo esc_attr($name_tag); ?> class="pubcrawl-author-box__name">
						<?php if ($link_name) : ?>
							<a href="<?php echo esc_url($data['archive_url']); ?>">
								<?php echo esc_html($data['name']); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html($data['name']); ?>
						<?php endif; ?>
					</<?php echo esc_attr($name_tag); ?>>
				<?php endif; ?>

				<?php if (! empty($data['bio'])) : ?>
					<div class="pubcrawl-author-box__bio">
						<?php echo wp_kses_post($data['bio']); ?>
					</div>
				<?php endif; ?>

				<?php if ($show_social && $has_social) : ?>
					<div class="pubcrawl-author-box__social-row">

						<?php if ($show_follow && ! empty($follow_text)) : ?>
							<span class="pubcrawl-author-box__follow-label">
								<?php echo esc_html($follow_text); ?>
							</span>
						<?php endif; ?>

						<ul class="pubcrawl-author-box__social-list">

							<?php if (! empty($data['facebook_url'])) : ?>
								<li class="pubcrawl-author-box__social-item">
									<a href="<?php echo esc_url($data['facebook_url']); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--facebook" aria-label="<?php esc_attr_e('Facebook', 'pubcrawls-addon'); ?>"<?php echo $target_attr; ?>>
										<?php $this->render_social_icon($settings['pubcrawl_ab_icon_fb'] ?? [], 'facebook'); ?>
									</a>
								</li>
							<?php endif; ?>

							<?php if (! empty($data['instagram_url'])) : ?>
								<li class="pubcrawl-author-box__social-item">
									<a href="<?php echo esc_url($data['instagram_url']); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--instagram" aria-label="<?php esc_attr_e('Instagram', 'pubcrawls-addon'); ?>"<?php echo $target_attr; ?>>
										<?php $this->render_social_icon($settings['pubcrawl_ab_icon_ig'] ?? [], 'instagram'); ?>
									</a>
								</li>
							<?php endif; ?>

							<?php if (! empty($data['x_url'])) : ?>
								<li class="pubcrawl-author-box__social-item">
									<a href="<?php echo esc_url($data['x_url']); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--x" aria-label="<?php esc_attr_e('X (Twitter)', 'pubcrawls-addon'); ?>"<?php echo $target_attr; ?>>
										<?php $this->render_social_icon($settings['pubcrawl_ab_icon_x'] ?? [], 'x'); ?>
									</a>
								</li>
							<?php endif; ?>

							<?php if (! empty($data['linkdin_url'])) : ?>
								<li class="pubcrawl-author-box__social-item">
									<a href="<?php echo esc_url($data['linkdin_url']); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--linkedin" aria-label="<?php esc_attr_e('LinkedIn', 'pubcrawls-addon'); ?>"<?php echo $target_attr; ?>>
										<?php $this->render_social_icon($settings['pubcrawl_ab_icon_li'] ?? [], 'linkedin'); ?>
									</a>
								</li>
							<?php endif; ?>

							<?php if (! empty($extra_items)) : ?>
								<?php foreach ($extra_items as $item) : ?>
									<?php if (! empty($item['pubcrawl_ab_item_url'])) : ?>
										<?php
										$title = ! empty($item['pubcrawl_ab_item_title']) ? $item['pubcrawl_ab_item_title'] : esc_html__('Social Link', 'pubcrawls-addon');
										$icon  = $item['pubcrawl_ab_item_icon'] ?? [];
										?>
										<li class="pubcrawl-author-box__social-item pubcrawl-author-box__social-item--extra">
											<a href="<?php echo esc_url($item['pubcrawl_ab_item_url']); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--custom" aria-label="<?php echo esc_attr($title); ?>" title="<?php echo esc_attr($title); ?>"<?php echo $target_attr; ?>>
												<?php $this->render_social_icon($icon, 'default'); ?>
											</a>
										</li>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endif; ?>

						</ul>
					</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
	}
}
