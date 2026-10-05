<?php

/**
 * Elementor Widget: FAQ Grid
 *
 * Displays a grid of FAQs filtered by category and limit.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_FAQ_Grid extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-faq-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('FAQ Grid', 'pubcrawls-addon');
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-help-o';
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
		return ['pubcrawl', 'faq', 'faqs', 'questions', 'answers', 'accordion', 'help'];
	}

	/**
	 * Get FAQ Categories for select2 control options.
	 *
	 * @return array
	 */
	protected function get_faq_categories()
	{
		$options = [];

		$terms = get_terms([
			'taxonomy'   => 'faq-category',
			'hide_empty' => false,
		]);

		if (! empty($terms) && ! is_wp_error($terms)) {
			foreach ($terms as $term) {
				$options[$term->slug] = html_entity_decode($term->name, ENT_QUOTES, 'UTF-8');
			}
		}

		return $options;
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
			'pubcrawl_limit',
			[
				'label'       => esc_html__('Limit', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => -1,
				'description' => esc_html__('Number of FAQs to show (-1 for all)', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_category',
			[
				'label'       => esc_html__('Category', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $this->get_faq_categories(),
				'default'     => [],
				'description' => esc_html__('Select one or more categories to filter FAQs. Leave empty to show all FAQs.', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_faq_schema',
			[
				'label'        => esc_html__('FAQ Schema', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'description'  => esc_html__('Enable FAQPage structured data (Schema.org) markup.', 'pubcrawls-addon'),
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
				'default' => 2,
				'mobile_default' => 1,
				'tablet_default' => 2,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
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
					'{{WRAPPER}} .pubcrawls-faq-grid' => 'gap: {{SIZE}}{{UNIT}};',
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

		$this->start_controls_tabs('tabs_card_style');

		// Normal Tab
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
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-item' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_card_hover',
			[
				'label' => esc_html__('Hover', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_card_bg_color_hover',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-item:hover, {{WRAPPER}} .pubcrawls-faq-grid .pubcrawls-faq-item:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_card_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-item:hover, {{WRAPPER}} .pubcrawls-faq-grid .pubcrawls-faq-item:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'pubcrawl_card_border',
				'selector'  => '{{WRAPPER}} .pubcrawls-faq-item',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-faq-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .pubcrawls-faq-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_question',
			[
				'label' => esc_html__('Question', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_question_typography',
				'selector' => '{{WRAPPER}} .pubcrawls-faq-question',
			]
		);

		$this->add_control(
			'pubcrawl_question_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-question' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_question_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-item:hover .pubcrawls-faq-question, {{WRAPPER}} .pubcrawls-faq-question:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_question_margin_bottom',
			[
				'label'      => esc_html__('Margin Bottom', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 50],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-faq-question' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_answer',
			[
				'label' => esc_html__('Answer', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_answer_typography',
				'selector' => '{{WRAPPER}} .pubcrawls-faq-answer',
			]
		);

		$this->add_control(
			'pubcrawl_answer_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-answer' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_answer_hover_color',
			[
				'label'     => esc_html__('Hover Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-item:hover .pubcrawls-faq-answer, {{WRAPPER}} .pubcrawls-faq-item:hover .pubcrawls-faq-answer *, {{WRAPPER}} .pubcrawls-faq-answer:hover' => 'color: {{VALUE}};',
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
		$limit    = isset($settings['limit']) ? intval($settings['limit']) : -1;
		$category = $settings['category'] ?? [];

		if (is_string($category)) {
			$category = array_filter(array_map('trim', explode(',', $category)));
		} elseif (! is_array($category)) {
			$category = [];
		}

		$query_args = [
			'post_type'      => 'faq',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		];

		if (! empty($category)) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$query_args['tax_query'] = [
				[
					'taxonomy' => 'faq-category',
					'field'    => 'slug',
					'terms'    => array_values($category),
				],
			];
		}

		$faqs = new \WP_Query($query_args);

		if (! $faqs->have_posts()) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-text">' . esc_html__('No FAQs found', 'pubcrawls-addon') . '</span></div>';
			} else {
				echo '<p class="pubcrawls-no-faqs">' . esc_html__('No FAQs found.', 'pubcrawls-addon') . '</p>';
			}
			return;
		}
		$global_faq_enabled = class_exists('Pubcrawls_Admin')
			? ('1' === (string) Pubcrawls_Admin::get_setting('enable_faq_schema_global', '1'))
			: true;
		$enable_schema   = $global_faq_enabled && ('yes' === ($settings['faq_schema'] ?? $settings['pubcrawl_faq_schema'] ?? 'yes'));
		$schema_entities = [];
?>
		<div class="pubcrawls-faq-grid">
			<?php foreach ($faqs->posts as $faq) :
				$question_text = html_entity_decode(wp_strip_all_tags($faq->post_title), ENT_QUOTES, 'UTF-8');
				$answer_html   = trim(wp_kses_post(apply_filters('the_content', $faq->post_content)));

				if ($enable_schema && ! empty(trim($question_text)) && ! empty(trim(wp_strip_all_tags($answer_html)))) {
					$schema_entities[] = [
						'@type'          => 'Question',
						'name'           => trim($question_text),
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text'  => $answer_html,
						],
					];
				}
			?>
				<div class="pubcrawls-faq-item">
					<h3 class="pubcrawls-faq-question">
						<?php echo esc_html($faq->post_title); ?>
					</h3>
					<div class="pubcrawls-faq-answer">
						<?php echo wp_kses_post($answer_html); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ($enable_schema && ! empty($schema_entities)) : ?>
			<script type="application/ld+json">
			<?php
			echo wp_json_encode([
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $schema_entities,
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			?>
			</script>
		<?php endif; ?>
<?php
	}
}
