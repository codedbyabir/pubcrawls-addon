<?php

/**
 * Elementor Widget: Get Directions Button
 *
 * Displays a Google Maps directions button based on the ACF 'address' field.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) exit;

class Pubcrawls_Get_Directions extends \Elementor\Widget_Base
{

    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name()
    {
        return 'pc-get-directions';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title()
    {
        return esc_html__('Get Directions Button', 'pubcrawls-addon');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon()
    {
        return 'eicon-map-pin';
    }

    /**
     * Get widget categories.
     *
     * @return array
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
        return ['pubcrawl', 'directions', 'maps', 'google maps', 'navigation', 'location'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls()
    {

        // ── Section: Button (Content) ────────────────────────────
        $this->start_controls_section(
            'section_button',
            [
                'label' => esc_html__('Button', 'pubcrawls-addon'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'pubcrawl_dynamic_notice',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => __('This widget dynamically generates a Google Maps directions button from the ACF field &ldquo;address&rdquo; of the current post or taxonomy term.', 'pubcrawls-addon'),
                'content_classes' => 'elementor-descriptor',
            ]
        );

        $this->add_control(
            'pubcrawl_button_text',
            [
                'label'       => esc_html__('Button Text', 'pubcrawls-addon'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => esc_html__('Get Directions', 'pubcrawls-addon'),
                'placeholder' => esc_html__('Get Directions', 'pubcrawls-addon'),
                'dynamic'     => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'pubcrawl_custom_address',
            [
                'label'       => esc_html__('Custom Address (Optional)', 'pubcrawls-addon'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'description' => esc_html__('Overrides current post ACF &ldquo;address&rdquo; field if specified.', 'pubcrawls-addon'),
                'placeholder' => esc_html__('e.g. 123 Main Street, City', 'pubcrawls-addon'),
                'dynamic'     => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'pubcrawl_open_in_new_window',
            [
                'label'        => esc_html__('Open in New Window', 'pubcrawls-addon'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'pubcrawls-addon'),
                'label_off'    => esc_html__('No', 'pubcrawls-addon'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_alignment',
            [
                'label'        => esc_html__('Alignment', 'pubcrawls-addon'),
                'type'         => \Elementor\Controls_Manager::CHOOSE,
                'options'      => [
                    'left'    => [
                        'title' => esc_html__('Left', 'pubcrawls-addon'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center'  => [
                        'title' => esc_html__('Center', 'pubcrawls-addon'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'   => [
                        'title' => esc_html__('Right', 'pubcrawls-addon'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => esc_html__('Justified', 'pubcrawls-addon'),
                        'icon'  => 'eicon-text-align-justify',
                    ],
                ],
                'prefix_class' => 'elementor%s-align-',
                'default'      => '',
                'selectors'    => [
                    '{{WRAPPER}} .pc-get-directions-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pubcrawl_selected_icon',
            [
                'label'            => esc_html__('Icon', 'pubcrawls-addon'),
                'type'             => \Elementor\Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'skin'             => 'inline',
                'label_block'      => false,
                'default'          => [
                    'value'   => 'fas fa-location-arrow',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'pubcrawl_icon_align',
            [
                'label'     => esc_html__('Icon Position', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => 'left',
                'options'   => [
                    'left'  => esc_html__('Before', 'pubcrawls-addon'),
                    'right' => esc_html__('After', 'pubcrawls-addon'),
                ],
                'condition' => [
                    'pubcrawl_selected_icon[value]!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_icon_indent',
            [
                'label'      => esc_html__('Icon Spacing', 'pubcrawls-addon'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .pc-get-directions-btn .pc-btn-icon-left'  => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .pc-get-directions-btn .pc-btn-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'pubcrawl_selected_icon[value]!' => '',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Section: Button Style ────────────────────────────────
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
                'name'     => 'pubcrawl_button_typography',
                'selector' => '{{WRAPPER}} .pc-get-directions-btn',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name'     => 'pubcrawl_button_text_shadow',
                'selector' => '{{WRAPPER}} .pc-get-directions-btn',
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        // Normal State
        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => esc_html__('Normal', 'pubcrawls-addon'),
            ]
        );

        $this->add_control(
            'pubcrawl_button_text_color',
            [
                'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pc-get-directions-btn'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pc-get-directions-btn svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name'     => 'pubcrawl_button_background',
                'label'    => esc_html__('Background', 'pubcrawls-addon'),
                'types'    => ['classic', 'gradient'],
                'exclude'  => ['image'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Elementor Group_Control_Background parameter, not a WP query parameter.
                'selector' => '{{WRAPPER}} .pc-get-directions-btn',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'pubcrawl_button_box_shadow',
                'selector' => '{{WRAPPER}} .pc-get-directions-btn',
            ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => esc_html__('Hover', 'pubcrawls-addon'),
            ]
        );

        $this->add_control(
            'pubcrawl_button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pc-get-directions-btn:hover, {{WRAPPER}} .pc-get-directions-btn:focus'         => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pc-get-directions-btn:hover svg, {{WRAPPER}} .pc-get-directions-btn:focus svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name'     => 'pubcrawl_button_hover_background',
                'label'    => esc_html__('Background', 'pubcrawls-addon'),
                'types'    => ['classic', 'gradient'],
                'exclude'  => ['image'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Elementor Group_Control_Background parameter, not a WP query parameter.
                'selector' => '{{WRAPPER}} .pc-get-directions-btn:hover, {{WRAPPER}} .pc-get-directions-btn:focus',
            ]
        );

        $this->add_control(
            'pubcrawl_button_hover_border_color',
            [
                'label'     => esc_html__('Border Color', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pc-get-directions-btn:hover, {{WRAPPER}} .pc-get-directions-btn:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'pubcrawl_button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .pc-get-directions-btn:hover, {{WRAPPER}} .pc-get-directions-btn:focus',
            ]
        );

        $this->add_control(
            'pubcrawl_hover_animation',
            [
                'label' => esc_html__('Hover Animation', 'pubcrawls-addon'),
                'type'  => \Elementor\Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'      => 'pubcrawl_button_border',
                'selector'  => '{{WRAPPER}} .pc-get-directions-btn',
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .pc-get-directions-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_button_padding',
            [
                'label'      => esc_html__('Padding', 'pubcrawls-addon'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .pc-get-directions-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Section: Icon Style ──────────────────────────────────
        $this->start_controls_section(
            'section_style_icon',
            [
                'label'     => esc_html__('Icon', 'pubcrawls-addon'),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'pubcrawl_selected_icon[value]!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_icon_size',
            [
                'label'      => esc_html__('Size', 'pubcrawls-addon'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px' => [
                        'min' => 6,
                        'max' => 60,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .pc-get-directions-btn .pc-btn-icon'     => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .pc-get-directions-btn .pc-btn-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('tabs_icon_style');

        $this->start_controls_tab(
            'tab_icon_normal',
            [
                'label' => esc_html__('Normal', 'pubcrawls-addon'),
            ]
        );

        $this->add_control(
            'pubcrawl_icon_color',
            [
                'label'     => esc_html__('Color', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pc-get-directions-btn .pc-btn-icon'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pc-get-directions-btn .pc-btn-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_icon_hover',
            [
                'label' => esc_html__('Hover', 'pubcrawls-addon'),
            ]
        );

        $this->add_control(
            'pubcrawl_icon_hover_color',
            [
                'label'     => esc_html__('Color', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pc-get-directions-btn:hover .pc-btn-icon, {{WRAPPER}} .pc-get-directions-btn:focus .pc-btn-icon'         => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pc-get-directions-btn:hover .pc-btn-icon svg, {{WRAPPER}} .pc-get-directions-btn:focus .pc-btn-icon svg' => 'fill: {{VALUE}};',
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
        $btn_text = ! empty($settings['button_text']) ? $settings['button_text'] : esc_html__('Get Directions', 'pubcrawls-addon');

        // Custom address override or dynamic address from ACF
        $address = ! empty($settings['custom_address']) ? trim($settings['custom_address']) : '';
        if (! $address && function_exists('get_field')) {
            $address = get_field('address');
            if (! $address && (is_category() || is_tax())) {
                $term = get_queried_object();
                if ($term && isset($term->term_id)) {
                    $address = get_field('address', $term);
                }
            }
        }


        $url = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($address);

        $has_icon   = ! empty($settings['selected_icon']['value']);
        $icon_align = ! empty($settings['icon_align']) ? $settings['icon_align'] : 'left';

        $btn_classes = ['pc-get-directions-btn'];
        if (! empty($settings['hover_animation'])) {
            $btn_classes[] = 'elementor-animation-' . esc_attr($settings['hover_animation']);
        }

        $this->add_render_attribute('button', [
            'href'  => esc_url($url),
            'class' => implode(' ', $btn_classes),
        ]);

        if (! empty($settings['open_in_new_window']) && 'yes' === $settings['open_in_new_window']) {
            $this->add_render_attribute('button', 'target', '_blank');
            $this->add_render_attribute('button', 'rel', 'noopener noreferrer');
        }
?>

        <a <?php $this->print_render_attribute_string('button'); ?>>
            <?php if ($has_icon && 'left' === $icon_align) : ?>
                <span class="pc-btn-icon pc-btn-icon-left">
                    <?php
                    if (class_exists('\Elementor\Icons_Manager')) {
                        \Elementor\Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
                    }
                    ?>
                </span>
            <?php endif; ?>

            <?php if (! empty($btn_text)) : ?>
                <?php echo esc_html($btn_text); ?>
            <?php endif; ?>

            <?php if ($has_icon && 'right' === $icon_align) : ?>
                <span class="pc-btn-icon pc-btn-icon-right">
                    <?php
                    if (class_exists('\Elementor\Icons_Manager')) {
                        \Elementor\Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
                    }
                    ?>
                </span>
            <?php endif; ?>
        </a>

<?php
    }
}
