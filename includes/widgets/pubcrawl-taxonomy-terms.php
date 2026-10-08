<?php

/**
 * Elementor Widget: Taxonomy Terms Grid
 *
 * Displays a customizable grid of taxonomy terms with post count and count after-text,
 * complete box styling (padding, borders, border-radius, background, hover states),
 * and direct links to category/term archive pages.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Taxonomy_Terms extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-taxonomy-terms';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Taxonomy Terms', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-tags';
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
		return ['taxonomy', 'terms', 'categories', 'grid', 'cards', 'post count', 'venues', 'cities', 'pubcrawl'];
	}

	/**
	 * Style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['pc-taxonomy-terms-css'];
	}

	/**
	 * Get public post type options.
	 *
	 * @return array
	 */
	protected function get_post_types_options()
	{
		$options = [
			'' => esc_html__('All / Default Post Types', 'pubcrawls-addon'),
		];

		$post_types = get_post_types(['public' => true], 'objects');
		foreach ($post_types as $slug => $pt) {
			if ('attachment' === $slug) {
				continue;
			}
			$options[$slug] = $pt->labels->name ?? $pt->label ?? $slug;
		}

		return $options;
	}

	/**
	 * Get public taxonomy options.
	 *
	 * @return array
	 */
	protected function get_taxonomies_options()
	{
		$options = [
			'' => esc_html__('Select Taxonomy', 'pubcrawls-addon'),
		];

		$taxonomies = get_taxonomies(['public' => true], 'objects');
		foreach ($taxonomies as $slug => $tax) {
			if (in_array($slug, ['post_format', 'nav_menu'], true)) {
				continue;
			}
			$obj_types = is_array($tax->object_type) ? implode(', ', $tax->object_type) : (string) $tax->object_type;
			$label     = $tax->labels->name ?? $tax->label ?? $slug;
			if (! empty($obj_types)) {
				$label .= ' (' . $obj_types . ')';
			}
			$options[$slug] = $label;
		}

		return $options;
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{

		// ════════════════════════════════════════════════════════════
		// ── CONTENT TAB: QUERY & TAXONOMY ────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__('Taxonomy Query', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'pubcrawl_post_type',
			[
				'label'       => esc_html__('Post Type', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_post_types_options(),
				'default'     => 'venue',
				'description' => esc_html__('Filter or identify post type associated with the terms.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_taxonomy',
			[
				'label'       => esc_html__('Taxonomy', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_taxonomies_options(),
				'default'     => 'venue_category',
				'description' => esc_html__('Select the taxonomy whose terms you want to display.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_count_after_text',
			[
				'label'       => esc_html__('Text After Count (Plural)', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('Venues', 'pubcrawls-addon'),
				'placeholder' => esc_html__('e.g. Venues, Events, Bars', 'pubcrawls-addon'),
				'description' => esc_html__('Text displayed after the count number (e.g., "10 Venues").', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_count_after_text_singular',
			[
				'label'       => esc_html__('Text After Count (Singular)', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('Venues', 'pubcrawls-addon'),
				'placeholder' => esc_html__('e.g. Venue', 'pubcrawls-addon'),
				'description' => esc_html__('Text used when count is exactly 1 (leave as "Venues" or change to "Venue").', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_show_count',
			[
				'label'        => esc_html__('Show Post Count', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_hide_empty',
			[
				'label'        => esc_html__('Hide Empty Terms', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_number',
			[
				'label'       => esc_html__('Number of Terms', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => esc_html__('Enter 0 to display all terms.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_orderby',
			[
				'label'   => esc_html__('Order By', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'name',
				'options' => [
					'name'    => esc_html__('Name', 'pubcrawls-addon'),
					'count'   => esc_html__('Post Count', 'pubcrawls-addon'),
					'slug'    => esc_html__('Slug', 'pubcrawls-addon'),
					'term_id' => esc_html__('Term ID', 'pubcrawls-addon'),
					'include' => esc_html__('Specified Include Order', 'pubcrawls-addon'),
				],
			]
		);

		$this->add_control(
			'pubcrawl_order',
			[
				'label'   => esc_html__('Order', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'ASC',
				'options' => [
					'ASC'  => esc_html__('Ascending (A-Z / Low to High)', 'pubcrawls-addon'),
					'DESC' => esc_html__('Descending (Z-A / High to Low)', 'pubcrawls-addon'),
				],
			]
		);

		$this->add_control(
			'pubcrawl_include_term_ids',
			[
				'label'       => esc_html__('Include Term IDs', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__('e.g. 12, 18, 25', 'pubcrawls-addon'),
				'description' => esc_html__('Comma-separated Term IDs to specifically include.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_exclude_term_ids',
			[
				'label'       => esc_html__('Exclude Term IDs', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__('e.g. 5, 9', 'pubcrawls-addon'),
				'description' => esc_html__('Comma-separated Term IDs to exclude.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_open_in_new_window',
			[
				'label'        => esc_html__('Open Link in New Tab', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: GRID LAYOUT ──────────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_grid',
			[
				'label' => esc_html__('Grid Layout', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_grid_columns',
			[
				'label'           => esc_html__('Columns', 'pubcrawls-addon'),
				'type'            => \Elementor\Controls_Manager::NUMBER,
				'min'             => 1,
				'max'             => 6,
				'step'            => 1,
				'default'         => 5,
				'tablet_default'  => 3,
				'mobile_default'  => 2,
				'selectors'       => [
					'{{WRAPPER}} .pc-taxonomy-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_grid_gap',
			[
				'label'      => esc_html__('Column Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'rem', '%'],
				'default'    => [
					'unit' => 'px',
					'size' => 16,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 60],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-taxonomy-grid' => 'column-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_grid_row_gap',
			[
				'label'      => esc_html__('Row Gap', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'rem', '%'],
				'default'    => [
					'unit' => 'px',
					'size' => 16,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 60],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-taxonomy-grid' => 'row-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: CARD BOX ─────────────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => esc_html__('Card Box', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'default'    => [
					'top'      => '22',
					'right'    => '18',
					'bottom'   => '22',
					'left'     => '18',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-term-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_align',
			[
				'label'     => esc_html__('Alignment', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__('Left', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card' => 'text-align: {{VALUE}}; align-items: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'rem', '%'],
				'default'    => [
					'top'      => '12',
					'right'    => '12',
					'bottom'   => '12',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-term-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs('tabs_card_box_states');

		// ── Normal Card State ──
		$this->start_controls_tab(
			'tab_card_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_card_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#131620',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'pubcrawl_card_border',
				'selector'  => '{{WRAPPER}} .pc-term-card',
				'separator' => 'before',
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
						'default' => '#282E3E',
					],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_box_shadow',
				'selector' => '{{WRAPPER}} .pc-term-card',
			]
		);

		$this->end_controls_tab();

		// ── Hover Card State ──
		$this->start_controls_tab(
			'tab_card_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_card_hover_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1A1F2E',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_card_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#E6B325',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pc-term-card:hover',
				'fields_options' => [
					'box_shadow_type' => [
						'default' => 'yes',
					],
					'box_shadow'      => [
						'default' => [
							'horizontal' => 0,
							'vertical'   => 10,
							'blur'       => 25,
							'spread'     => -5,
							'color'      => 'rgba(0, 0, 0, 0.4)',
						],
					],
				],
			]
		);

		$this->add_control(
			'pubcrawl_card_hover_translate_y',
			[
				'label'      => esc_html__('Hover Lift (Translate Y)', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => -4,
				],
				'range'      => [
					'px' => ['min' => -20, 'max' => 20],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-term-card:hover' => 'transform: translateY({{SIZE}}{{UNIT}});',
				],
			]
		);

		$this->add_control(
			'pubcrawl_card_transition_duration',
			[
				'label'      => esc_html__('Transition Duration (s)', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['s'],
				'default'    => [
					'size' => 0.3,
				],
				'range'      => [
					's' => ['min' => 0.1, 'max' => 1.5, 'step' => 0.05],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-term-card' => 'transition: all {{SIZE}}s ease-in-out;',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: TITLE (TAXONOMY NAME) ────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__('Title (Taxonomy Name)', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pubcrawl_title_html_tag',
			[
				'label'   => esc_html__('HTML Tag', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => [
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
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_title_typography',
				'selector' => '{{WRAPPER}} .pc-term-card__title',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_title_bottom_spacing',
			[
				'label'      => esc_html__('Bottom Spacing', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'default'    => [
					'unit' => 'px',
					'size' => 6,
				],
				'range'      => [
					'px' => ['min' => 0, 'max' => 40],
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-term-card__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs('tabs_title_colors');

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
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_title_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_title_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#E6B325',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card:hover .pc-term-card__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// ════════════════════════════════════════════════════════════
		// ── STYLE TAB: POST COUNT ───────────────────────────────────
		// ════════════════════════════════════════════════════════════
		$this->start_controls_section(
			'section_style_count',
			[
				'label'     => esc_html__('Post Count', 'pubcrawls-addon'),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'pubcrawl_show_count' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_count_typography',
				'selector' => '{{WRAPPER}} .pc-term-card__count',
			]
		);

		$this->start_controls_tabs('tabs_count_colors');

		$this->start_controls_tab(
			'tab_count_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_count_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#94A3B8',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card__count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_count_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_count_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .pc-term-card:hover .pc-term-card__count' => 'color: {{VALUE}};',
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

		// Enqueue styles.
		wp_enqueue_style('pc-taxonomy-terms-css');

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

		$taxonomy   = ! empty($settings['taxonomy']) ? sanitize_key($settings['taxonomy']) : 'venue_category';
		$hide_empty = (! isset($settings['hide_empty']) || 'yes' === $settings['hide_empty']);
		$number     = isset($settings['number']) ? intval($settings['number']) : 0;
		$orderby    = ! empty($settings['orderby']) ? sanitize_key($settings['orderby']) : 'name';
		$order      = ! empty($settings['order']) ? strtoupper(sanitize_key($settings['order'])) : 'ASC';

		$term_args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $hide_empty,
			'orderby'    => $orderby,
			'order'      => $order,
		];

		if ($number > 0) {
			$term_args['number'] = $number;
		}

		if (! empty($settings['include_term_ids'])) {
			$include_ids = array_filter(array_map('intval', explode(',', $settings['include_term_ids'])));
			if (! empty($include_ids)) {
				$term_args['include'] = $include_ids;
			}
		}

		if (! empty($settings['exclude_term_ids'])) {
			$exclude_ids = array_filter(array_map('intval', explode(',', $settings['exclude_term_ids'])));
			if (! empty($exclude_ids)) {
				$term_args['exclude'] = $exclude_ids;
			}
		}

		$terms = get_terms($term_args);

		if (empty($terms) || is_wp_error($terms)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-category"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Taxonomy Terms Grid (No terms found for this taxonomy)', 'pubcrawls-addon') . '</span></div>';
			}
			return;
		}

		$show_count          = (! isset($settings['show_count']) || 'yes' === $settings['show_count']);
		$after_text_plural   = isset($settings['count_after_text']) ? $settings['count_after_text'] : esc_html__('Venues', 'pubcrawls-addon');
		$after_text_singular = isset($settings['count_after_text_singular']) ? $settings['count_after_text_singular'] : $after_text_plural;
		$title_tag           = ! empty($settings['title_html_tag']) ? \Elementor\Utils::validate_html_tag($settings['title_html_tag']) : 'h3';
		$open_new_tab        = (! empty($settings['open_in_new_window']) && 'yes' === $settings['open_in_new_window']);
		$is_nofollow         = (! empty($settings['nofollow']) && 'yes' === $settings['nofollow']);

		$link_attrs = '';
		if ($open_new_tab) {
			$link_attrs .= ' target="_blank"';
		}
		if ($open_new_tab || $is_nofollow) {
			$rel = [];
			if ($open_new_tab) {
				$rel[] = 'noopener';
				$rel[] = 'noreferrer';
			}
			if ($is_nofollow) {
				$rel[] = 'nofollow';
			}
			$link_attrs .= ' rel="' . esc_attr(implode(' ', $rel)) . '"';
		}

		$grid_id = 'pc-taxonomy-grid-' . esc_attr($this->get_id());
		?>
		<div id="<?php echo esc_attr($grid_id); ?>" class="pc-taxonomy-grid-wrap">
			<div class="pc-taxonomy-grid" role="list">
				<?php foreach ($terms as $term) :
					$term_link = get_term_link($term);
					if (is_wp_error($term_link)) {
						$term_link = '#';
					}

					$count = intval($term->count);
					$label_suffix = (1 === $count && ! empty($after_text_singular)) ? $after_text_singular : $after_text_plural;
					$count_string = trim($count . ' ' . $label_suffix);
				?>
					<a href="<?php echo esc_url($term_link); ?>"
					   class="pc-term-card pc-term-<?php echo esc_attr($term->term_id); ?>"
					   role="listitem"<?php echo $link_attrs; ?>>
						
						<<?php echo esc_html($title_tag); ?> class="pc-term-card__title">
							<?php echo esc_html($term->name); ?>
						</<?php echo esc_html($title_tag); ?>>

						<?php if ($show_count) : ?>
							<span class="pc-term-card__count">
								<?php echo esc_html($count_string); ?>
							</span>
						<?php endif; ?>

					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
