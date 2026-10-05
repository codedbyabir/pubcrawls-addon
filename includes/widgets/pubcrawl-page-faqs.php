<?php

/**
 * Elementor Widget: Page / Post FAQs
 *
 * Displays dynamic FAQs from the ACF repeater 'faqs' on current page, post, or taxonomy term.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Page_FAQs extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-page-faqs';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Page / Post FAQs', 'pubcrawls-addon');
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
		return ['pubcrawl', 'faq', 'page faqs', 'questions', 'accordion', 'dynamic', 'help'];
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
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);


		$this->add_control(
			'pubcrawl_show_title',
			[
				'label'        => esc_html__('Show Section Title', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'pubcrawl_title',
			[
				'label'       => esc_html__('Title Text', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('Frequently Asked Questions (FAQs)', 'pubcrawls-addon'),
				'placeholder' => esc_html__('Frequently Asked Questions (FAQs)', 'pubcrawls-addon'),
				'condition'   => [
					'pubcrawl_show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_title_tag',
			[
				'label'     => esc_html__('Title HTML Tag', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'p'    => 'p',
				],
				'condition' => [
					'pubcrawl_show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_custom_faqs',
			[
				'label'        => esc_html__('Custom FAQs', 'pubcrawls-addon'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
				'label_off'    => esc_html__('No', 'pubcrawls-addon'),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__('Enable to create custom questions & answers instead of loading dynamic FAQs from ACF.', 'pubcrawls-addon'),
				'separator'    => 'before',
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'question',
			[
				'label'       => esc_html__('Question', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__('Frequently Asked Question?', 'pubcrawls-addon'),
				'placeholder' => esc_html__('Enter question', 'pubcrawls-addon'),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'answer',
			[
				'label'       => esc_html__('Answer', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::WYSIWYG,
				'default'     => esc_html__('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'pubcrawls-addon'),
				'placeholder' => esc_html__('Enter answer', 'pubcrawls-addon'),
				'show_label'  => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'pubcrawl_faq_items',
			[
				'label'       => esc_html__('FAQ Items', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'question' => esc_html__('What is included in the pub crawl?', 'pubcrawls-addon'),
						'answer'   => esc_html__('The pub crawl includes entry to all participating venues, exclusive drink specials, and a dedicated crawl host.', 'pubcrawls-addon'),
					],
					[
						'question' => esc_html__('Do I need to print my tickets?', 'pubcrawls-addon'),
						'answer'   => esc_html__('No, you can simply show your mobile e-ticket or confirmation QR code at the check-in point.', 'pubcrawls-addon'),
					],
				],
				'title_field' => '{{{ question }}}',
				'condition'   => [
					'pubcrawl_custom_faqs' => 'yes',
				],
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

		// ── Style Section: Title ─────────────────────────────────
		$this->start_controls_section(
			'section_style_title',
			[
				'label'     => esc_html__('Section Title', 'pubcrawls-addon'),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'pubcrawl_show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'pubcrawl_title_color',
			[
				'label'     => esc_html__('Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-faq-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'pubcrawl_title_typography',
				'selector' => '{{WRAPPER}} .pubcrawl-faq-title',
			]
		);

		$this->add_responsive_control(
			'pubcrawl_title_align',
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
				'selectors' => [
					'{{WRAPPER}} .pubcrawl-faq-title' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_title_margin_bottom',
			[
				'label'      => esc_html__('Margin Bottom', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 60],
				],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawl-faq-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Grid ──────────────────────────────────
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
				'label'          => esc_html__('Columns', 'pubcrawls-addon'),
				'type'           => \Elementor\Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 4,
				'default'        => 2,
				'tablet_default' => 1,
				'mobile_default' => 1,
				'selectors'      => [
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

		// ── Style Section: Card ──────────────────────────────────
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => esc_html__('Card Item', 'pubcrawls-addon'),
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

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_card_border',
				'selector' => '{{WRAPPER}} .pubcrawls-faq-item',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawls-faq-item',
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
					'{{WRAPPER}} .pubcrawls-faq-item:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_card_border_color_hover',
			[
				'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pubcrawls-faq-item:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pubcrawls-faq-item:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pubcrawl_card_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pubcrawls-faq-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
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

		// ── Style Section: Question ──────────────────────────────
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

		// ── Style Section: Answer ────────────────────────────────
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

		$is_custom = ('yes' === ($settings['pubcrawl_custom_faqs'] ?? $settings['custom_faqs'] ?? ''));
		$faqs      = [];

		if ($is_custom) {
			$custom_items = ! empty($settings['pubcrawl_faq_items']) ? $settings['pubcrawl_faq_items'] : (! empty($settings['faq_items']) ? $settings['faq_items'] : []);
			if (! empty($custom_items) && is_array($custom_items)) {
				foreach ($custom_items as $item) {
					$faqs[] = [
						'question' => $item['question'] ?? '',
						'answer'   => $item['answer'] ?? '',
					];
				}
			}
		} else {
			if (is_category() || is_tax()) {
				$source = get_queried_object();
			} else {
				$source = get_the_ID();
			}

			if (function_exists('have_rows') && have_rows('faqs', $source)) {
				while (have_rows('faqs', $source)) {
					the_row();
					$faqs[] = [
						'question' => get_sub_field('question'),
						'answer'   => get_sub_field('answer'),
					];
				}
			}
		}

		if (empty($faqs)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				$msg = $is_custom
					? esc_html__('No FAQ items added yet. Click "Add Item" in the Content panel.', 'pubcrawls-addon')
					: esc_html__('No FAQs for this Page/Post (ACF repeater is empty).', 'pubcrawls-addon');
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-editor-help"></span><span class="pubcrawls-placeholder-text">' . esc_html($msg) . '</span></div>';
			}
			return;
		}

		$show_title      = ($settings['show_title'] ?? 'yes') === 'yes';
		$title           = ! empty($settings['title']) ? $settings['title'] : esc_html__('Frequently Asked Questions (FAQs)', 'pubcrawls-addon');
		$title_tag       = ! empty($settings['title_tag']) ? \Elementor\Utils::validate_html_tag($settings['title_tag']) : 'h2';
		$global_faq_enabled = class_exists('Pubcrawls_Admin')
			? ('1' === (string) Pubcrawls_Admin::get_setting('enable_faq_schema_global', '1'))
			: true;
		$enable_schema   = $global_faq_enabled && ('yes' === ($settings['faq_schema'] ?? $settings['pubcrawl_faq_schema'] ?? 'yes'));
		$schema_entities = [];
?>

		<?php if ($show_title && ! empty($title)) : ?>
			<<?php echo esc_attr($title_tag); ?> class="pubcrawl-faq-title"><?php echo esc_html($title); ?></<?php echo esc_attr($title_tag); ?>>
		<?php endif; ?>

		<div class="pubcrawls-faq-grid">
			<?php foreach ($faqs as $faq) :
				$qsn = $faq['question'] ?? '';
				$ans = $faq['answer'] ?? '';
				if (empty($qsn) && empty($ans)) {
					continue;
				}
				$question_text = html_entity_decode(wp_strip_all_tags($qsn), ENT_QUOTES, 'UTF-8');
				$answer_html   = trim(wp_kses_post(wpautop($ans)));

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
					<h3 class="pubcrawls-faq-question"><?php echo esc_html($qsn); ?></h3>
					<div class="pubcrawls-faq-answer"><?php echo wp_kses_post($answer_html); ?></div>
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
