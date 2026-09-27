<?php

/**
 * Elementor Widget: Social Share Icons
 *
 * Displays customizable social share icons (Facebook, Twitter/X, LinkedIn, etc.)
 * with Elementor icon library selection, size, padding, colors, border, and hover controls.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Pubcrawls_Social_Share extends \Elementor\Widget_Base
{

	/**
	 * Retrieve widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'pc-social-share';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Social Share Icons', 'pubcrawls-addon');
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-share';
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
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords()
	{
		return ['pubcrawl', 'social', 'share', 'facebook', 'twitter', 'linkedin', 'sharing'];
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls()
	{

		// ── Content Section: Share Buttons ──────────────────────
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__('Share Buttons', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'network',
			[
				'label'   => esc_html__('Network', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'facebook',
				'options' => [
					'facebook'  => esc_html__('Facebook', 'pubcrawls-addon'),
					'twitter'   => esc_html__('X / Twitter', 'pubcrawls-addon'),
					'linkedin'  => esc_html__('LinkedIn', 'pubcrawls-addon'),
					'whatsapp'  => esc_html__('WhatsApp', 'pubcrawls-addon'),
					'pinterest' => esc_html__('Pinterest', 'pubcrawls-addon'),
					'reddit'    => esc_html__('Reddit', 'pubcrawls-addon'),
					'email'     => esc_html__('Email', 'pubcrawls-addon'),
					'copy_link' => esc_html__('Copy Page Link', 'pubcrawls-addon'),
				],
			]
		);

		$repeater->add_control(
			'custom_icon',
			[
				'label'       => esc_html__('Icon', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fab fa-facebook-f',
					'library' => 'fa-brands',
				],
				'description' => esc_html__('Choose icon from library or upload SVG.', 'pubcrawls-addon'),
			]
		);

		$repeater->add_control(
			'custom_url',
			[
				'label'       => esc_html__('Custom URL', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__('https://...', 'pubcrawls-addon'),
				'description' => esc_html__('Leave empty to automatically share current post URL.', 'pubcrawls-addon'),
			]
		);

		$repeater->add_control(
			'custom_label',
			[
				'label'       => esc_html__('Label / Tooltip', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__('Share on Facebook', 'pubcrawls-addon'),
			]
		);

		$repeater->add_control(
			'customize_colors',
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
			'item_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}'     => 'color: {{VALUE}};',
					'{{WRAPPER}} {{CURRENT_ITEM}} svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'item_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => 'background-color: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'item_hover_color',
			[
				'label'     => esc_html__('Hover Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}:hover'     => 'color: {{VALUE}};',
					'{{WRAPPER}} {{CURRENT_ITEM}}:hover svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'item_hover_bg_color',
			[
				'label'     => esc_html__('Hover Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'customize_colors' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_share_items',
			[
				'label'       => esc_html__('Social Networks', 'pubcrawls-addon'),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'network'     => 'facebook',
						'custom_icon' => [
							'value'   => 'fab fa-facebook-f',
							'library' => 'fa-brands',
						],
					],
					[
						'network'     => 'twitter',
						'custom_icon' => [
							'value'   => 'fab fa-x-twitter',
							'library' => 'fa-brands',
						],
					],
					[
						'network'     => 'linkedin',
						'custom_icon' => [
							'value'   => 'fab fa-linkedin-in',
							'library' => 'fa-brands',
						],
					],
				],
				'title_field' => '{{{ network.charAt(0).toUpperCase() + network.slice(1) }}}',
			]
		);

		$this->end_controls_section();

		// ── Style Section: Layout ────────────────────────────────
		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => esc_html__('Layout', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_alignment',
			[
				'label'     => esc_html__('Alignment', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__('Left', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => esc_html__('Center', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => esc_html__('Right', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-right',
					],
					'space-between' => [
						'title' => esc_html__('Space Between', 'pubcrawls-addon'),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => 'flex-start',
				'selectors' => [
					'{{WRAPPER}} .pc-social-share-wrap' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_items_gap',
			[
				'label'      => esc_html__('Spacing Between Icons', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 60],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-social-share-wrap' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style Section: Buttons & Icons ───────────────────────
		$this->start_controls_section(
			'section_style_buttons',
			[
				'label' => esc_html__('Buttons & Icons', 'pubcrawls-addon'),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pubcrawl_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 10, 'max' => 60],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 16,
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-social-share-btn'     => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .pc-social-share-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_btn_box_size',
			[
				'label'      => esc_html__('Box Size (Width & Height)', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 20, 'max' => 100],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 38,
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-social-share-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_btn_padding',
			[
				'label'      => esc_html__('Padding', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .pc-social-share-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pubcrawl_btn_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'default'    => [
					'top'      => '50',
					'right'    => '50',
					'bottom'   => '50',
					'left'     => '50',
					'unit'     => '%',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .pc-social-share-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_color_style',
			[
				'label'   => esc_html__('Color Palette', 'pubcrawls-addon'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => [
					'official' => esc_html__('Official Brand Colors', 'pubcrawls-addon'),
					'custom'   => esc_html__('Custom Colors', 'pubcrawls-addon'),
				],
			]
		);

		$this->start_controls_tabs('tabs_btn_colors');

		// Normal Tab
		$this->start_controls_tab(
			'tab_btn_normal',
			[
				'label' => esc_html__('Normal', 'pubcrawls-addon'),
			]
		);

		$this->add_control(
			'pubcrawl_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => [
					'pubcrawl_color_style' => 'custom',
				],
				'selectors' => [
					'{{WRAPPER}} .pc-social-share-btn'     => 'color: {{VALUE}};',
					'{{WRAPPER}} .pc-social-share-btn svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_bg_color',
			[
				'label'     => esc_html__('Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1f2937',
				'condition' => [
					'pubcrawl_color_style' => 'custom',
				],
				'selectors' => [
					'{{WRAPPER}} .pc-social-share-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'pubcrawl_btn_border',
				'selector' => '{{WRAPPER}} .pc-social-share-btn',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_btn_box_shadow',
				'selector' => '{{WRAPPER}} .pc-social-share-btn',
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
			'pubcrawl_icon_hover_color',
			[
				'label'     => esc_html__('Hover Icon Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => [
					'pubcrawl_color_style' => 'custom',
				],
				'selectors' => [
					'{{WRAPPER}} .pc-social-share-btn:hover'     => 'color: {{VALUE}};',
					'{{WRAPPER}} .pc-social-share-btn:hover svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_bg_hover_color',
			[
				'label'     => esc_html__('Hover Background Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e6b325',
				'condition' => [
					'pubcrawl_color_style' => 'custom',
				],
				'selectors' => [
					'{{WRAPPER}} .pc-social-share-btn:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_border_hover_color',
			[
				'label'     => esc_html__('Hover Border Color', 'pubcrawls-addon'),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pc-social-share-btn:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pubcrawl_hover_animation',
			[
				'label' => esc_html__('Hover Animation', 'pubcrawls-addon'),
				'type'  => \Elementor\Controls_Manager::HOVER_ANIMATION,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pubcrawl_btn_hover_box_shadow',
				'selector' => '{{WRAPPER}} .pc-social-share-btn:hover',
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
		$items    = ! empty($settings['share_items']) ? $settings['share_items'] : [];

		// Backward compatibility: if repeater is empty but legacy platform setting exists.
		if (empty($items) && ! empty($settings['platform'])) {
			$platform      = sanitize_key($settings['platform']);
			$default_icons = [
				'facebook' => 'fab fa-facebook-f',
				'twitter'  => 'fab fa-x-twitter',
				'linkedin' => 'fab fa-linkedin-in',
			];
			$items = [
				[
					'network'      => $platform,
					'custom_icon'  => [
						'value'   => $default_icons[$platform] ?? 'fas fa-share-alt',
						'library' => 'fa-brands',
					],
					'custom_label' => $settings['custom_label'] ?? '',
				],
			];
		}

		if (empty($items)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-share"></span><span class="pubcrawls-placeholder-text">' . esc_html__('Social Share Icons (No buttons added)', 'pubcrawls-addon') . '</span></div>';
			}
			return;
		}

		$permalink = get_permalink();
		$title     = get_the_title();

		$color_style = $settings['color_style'] ?? 'custom';
		$hover_anim  = ! empty($settings['hover_animation']) ? 'elementor-animation-' . esc_attr($settings['hover_animation']) : '';

		$default_icon_map = [
			'facebook'  => 'fab fa-facebook-f',
			'twitter'   => 'fab fa-x-twitter',
			'linkedin'  => 'fab fa-linkedin-in',
			'whatsapp'  => 'fab fa-whatsapp',
			'pinterest' => 'fab fa-pinterest-p',
			'reddit'    => 'fab fa-reddit-alien',
			'email'     => 'fas fa-envelope',
			'copy_link' => 'fas fa-link',
		];
?>

		<div class="pc-social-share-wrap">
			<?php
			foreach ($items as $index => $item) :
				$network   = ! empty($item['network']) ? sanitize_key($item['network']) : 'facebook';
				$share_url = ! empty($item['custom_url']) ? trim($item['custom_url']) : '';

				if (empty($share_url)) {
					switch ($network) {
						case 'facebook':
							$share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($permalink);
							break;
						case 'twitter':
							$share_url = 'https://twitter.com/intent/tweet?url=' . rawurlencode($permalink) . '&text=' . rawurlencode($title);
							break;
						case 'linkedin':
							$share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($permalink);
							break;
						case 'whatsapp':
							$share_url = 'https://api.whatsapp.com/send?text=' . rawurlencode($title . ' ' . $permalink);
							break;
						case 'pinterest':
							$share_url = 'https://pinterest.com/pin/create/button/?url=' . rawurlencode($permalink) . '&description=' . rawurlencode($title);
							break;
						case 'reddit':
							$share_url = 'https://reddit.com/submit?url=' . rawurlencode($permalink) . '&title=' . rawurlencode($title);
							break;
						case 'email':
							$share_url = 'mailto:?subject=' . rawurlencode($title) . '&body=' . rawurlencode($permalink);
							break;
						case 'copy_link':
						case 'current_url':
							$share_url = $permalink;
							break;
						default:
							$share_url = $permalink;
							break;
					}
				}

				$label = ! empty($item['custom_label']) ? $item['custom_label'] : ucfirst(str_replace('_', ' ', $network));

				$btn_classes = [
					'pc-social-share-btn',
					'elementor-repeater-item-' . esc_attr($item['_id'] ?? $index),
					'pc-social-share--' . esc_attr($network),
				];

				if ('official' === $color_style) {
					$btn_classes[] = 'pc-social-share-btn--official';
				}

				if ($hover_anim) {
					$btn_classes[] = $hover_anim;
				}

				$is_copy = ('copy_link' === $network);
			?>
				<a href="<?php echo esc_url($share_url); ?>"
					class="<?php echo esc_attr(implode(' ', $btn_classes)); ?>"
					target="_blank"
					rel="noopener noreferrer"
					aria-label="<?php echo esc_attr($label); ?>"
					title="<?php echo esc_attr($label); ?>" <?php if ($is_copy) : ?> data-copy-url="<?php echo esc_url($permalink); ?>" onclick="if(navigator.clipboard){navigator.clipboard.writeText(this.dataset.copyUrl);alert('Link copied to clipboard!');return false;}" <?php endif; ?>>
					<?php
					if (! empty($item['custom_icon']['value']) && class_exists('\Elementor\Icons_Manager')) {
						\Elementor\Icons_Manager::render_icon($item['custom_icon'], ['aria-hidden' => 'true']);
					} elseif (! empty($default_icon_map[$network])) {
					?>
						<i class="<?php echo esc_attr($default_icon_map[$network]); ?>" aria-hidden="true"></i>
					<?php
					} else {
					?>
						<i class="fas fa-share-alt" aria-hidden="true"></i>
					<?php
					}
					?>
				</a>
			<?php endforeach; ?>
		</div>
<?php
	}
}
