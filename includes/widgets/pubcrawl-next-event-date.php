<?php

/**
 * Elementor Widget: Next Event Date
 *
 * Displays upcoming event date range formatted for menus and banners with icon support.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
    exit;
}

class Pubcrawls_Next_Event_Date extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'pc-next-event-date';
    }

    public function get_title()
    {
        return esc_html__('Next Event Date', 'pubcrawls-addon');
    }

    public function get_icon()
    {
        return 'eicon-calendar';
    }

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
        return ['pubcrawl', 'event', 'date', 'next', 'upcoming', 'countdown', 'badge'];
    }

    protected function register_controls()
    {

        // ── Date & Icon Content ───────────────────────────────────
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Date Settings', 'pubcrawls-addon'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'pubcrawl_start',
            [
                'label'       => esc_html__('Start Date', 'pubcrawls-addon'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => '',
                'description' => esc_html__('Start date as M-D (e.g. 3-17)', 'pubcrawls-addon'),
            ]
        );

        $this->add_control(
            'pubcrawl_end',
            [
                'label'       => esc_html__('End Date', 'pubcrawls-addon'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => '',
                'description' => esc_html__('End date as M-D (e.g. 3-17, leave empty for single day)', 'pubcrawls-addon'),
            ]
        );

        $this->add_control(
            'pubcrawl_selected_icon',
            [
                'label'       => esc_html__('Icon', 'pubcrawls-addon'),
                'type'        => \Elementor\Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-calendar-alt',
                    'library' => 'fa-solid',
                ],
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'pubcrawl_icon_align',
            [
                'label'     => esc_html__('Icon Position', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => 'left',
                'options'   => [
                    'left'  => esc_html__('Before (Left)', 'pubcrawls-addon'),
                    'right' => esc_html__('After (Right)', 'pubcrawls-addon'),
                ],
                'condition' => [
                    'pubcrawl_selected_icon[value]!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_icon_spacing',
            [
                'label'      => esc_html__('Icon Spacing', 'pubcrawls-addon'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .pc-next-date--icon-left .pc-next-date-icon'  => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .pc-next-date--icon-right .pc-next-date-icon' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'pubcrawl_selected_icon[value]!' => '',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Style Section: Icon ───────────────────────────────────
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
                    'px' => ['min' => 8, 'max' => 60],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .pc-next-date-icon'     => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .pc-next-date-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'pubcrawl_icon_color',
            [
                'label'     => esc_html__('Color', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pc-next-date-icon'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pc-next-date-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Style Section: Text ───────────────────────────────────
        $this->start_controls_section(
            'section_style_text',
            [
                'label' => esc_html__('Text', 'pubcrawls-addon'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'pubcrawl_text_typography',
                'selector' => '{{WRAPPER}} .pc-next-date-text, {{WRAPPER}}',
            ]
        );

        $this->add_control(
            'pubcrawl_text_color',
            [
                'label'     => esc_html__('Text Color', 'pubcrawls-addon'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pc-next-date-text' => 'color: {{VALUE}};',
                    '{{WRAPPER}}'                    => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_text_align',
            [
                'label'   => esc_html__('Alignment', 'pubcrawls-addon'),
                'type'    => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
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
                    '{{WRAPPER}}' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings  = $this->get_settings_for_display();

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
        $start_str = isset($settings['start']) ? trim($settings['start']) : '';
        $end_str   = isset($settings['end']) ? trim($settings['end']) : '';

        if (empty($start_str)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
?>
                <div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-calendar"></span><span class="pubcrawls-placeholder-text"><?php esc_html_e('Next Event Date (Configure start date e.g. 3-17 in Content tab)', 'pubcrawls-addon'); ?></span></div>
        <?php
            }
            return;
        }

        $start_parts = explode('-', $start_str);
        if (count($start_parts) < 2) {
            echo esc_html($start_str);
            return;
        }

        $sm = $start_parts[0];
        $sd = $start_parts[1];

        $today = new \DateTime();
        $year  = $today->format('Y');

        $start = \DateTime::createFromFormat('Y-n-j', "$year-$sm-$sd");
        if (! $start) {
            echo esc_html($start_str);
            return;
        }

        $formatted_date = '';
        if (! empty($end_str)) {
            $end_parts = explode('-', $end_str);
            if (count($end_parts) >= 2) {
                $em  = $end_parts[0];
                $ed  = $end_parts[1];
                $end = \DateTime::createFromFormat('Y-n-j', "$year-$em-$ed");

                if ($end && $end < $today) {
                    $start->modify('+1 year');
                    $end->modify('+1 year');
                }

                if ($end) {
                    $formatted_date = $start->format('F j') . ' – ' . $end->format('j, Y');
                }
            }
        }

        if (empty($formatted_date)) {
            if ($start < $today) {
                $start->modify('+1 year');
            }
            $formatted_date = $start->format('F j, Y');
        }

        $has_icon   = ! empty($settings['selected_icon']['value']);
        $icon_align = ! empty($settings['icon_align']) ? $settings['icon_align'] : 'left';
        ?>

        <div class="pc-next-date-wrap pc-next-date--icon-<?php echo esc_attr($icon_align); ?>">
            <?php if ($has_icon && 'left' === $icon_align) : ?>
                <span class="pc-next-date-icon" aria-hidden="true">
                    <?php
                    if (class_exists('\Elementor\Icons_Manager')) {
                        \Elementor\Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
                    }
                    ?>
                </span>
            <?php endif; ?>

            <span class="pc-next-date-text">
                <?php echo esc_html($formatted_date); ?>
            </span>

            <?php if ($has_icon && 'right' === $icon_align) : ?>
                <span class="pc-next-date-icon" aria-hidden="true">
                    <?php
                    if (class_exists('\Elementor\Icons_Manager')) {
                        \Elementor\Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
                    }
                    ?>
                </span>
            <?php endif; ?>
        </div>
<?php
    }
}
