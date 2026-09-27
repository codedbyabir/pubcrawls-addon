<?php

/**
 * Elementor Widget: City Map
 *
 * Displays an embedded interactive map from ACF field 'map_url'.
 *
 * @package Pubcrawl_Addon
 */

if (! defined('ABSPATH')) {
    exit;
}

class Pubcrawls_City_Map extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'pc-city-map';
    }

    public function get_title()
    {
        return esc_html__('City Map', 'pubcrawls-addon');
    }

    public function get_icon()
    {
        return 'eicon-google-maps';
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
        return ['pubcrawl', 'city', 'map', 'mapme', 'interactive', 'location', 'embed'];
    }

    public function get_style_depends()
    {
        return ['pub-crawls-styles'];
    }

    protected function register_controls()
    {

        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Map Settings', 'pubcrawls-addon'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'pubcrawl_post_id',
            [
                'label'       => esc_html__('Post ID', 'pubcrawls-addon'),
                'type'        => \Elementor\Controls_Manager::NUMBER,
                'default'     => '',
                'description' => esc_html__('Post ID — leave empty to use current post', 'pubcrawls-addon'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_map',
            [
                'label' => esc_html__('Map', 'pubcrawls-addon'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_map_height',
            [
                'label'      => esc_html__('Height', 'pubcrawls-addon'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh', 'em', 'rem'],
                'range'      => [
                    'px' => ['min' => 200, 'max' => 1200],
                    'vh' => ['min' => 20, 'max' => 100],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .pubcrawl-city-map__iframe-wrap' => 'height: {{SIZE}}{{UNIT}}; min-height: unset;',
                    '{{WRAPPER}} .pubcrawl-city-map__iframe'      => 'min-height: unset;',
                ],
            ]
        );

        $this->add_responsive_control(
            'pubcrawl_map_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'pubcrawls-addon'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .pubcrawl-city-map__iframe-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'pubcrawl_map_border',
                'selector' => '{{WRAPPER}} .pubcrawl-city-map__iframe-wrap',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'pubcrawl_map_box_shadow',
                'selector' => '{{WRAPPER}} .pubcrawl-city-map__iframe-wrap',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings    = $this->get_settings_for_display();

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
        $post_id_val = ! empty($settings['post_id']) ? $settings['post_id'] : ($settings['id'] ?? '');
        $map_post_id = ! empty($post_id_val) ? absint($post_id_val) : get_the_ID();

        if (! $map_post_id) {
            return;
        }

        $map_url = function_exists('get_field') ? get_field('map_url', $map_post_id) : get_post_meta($map_post_id, 'map_url', true);

        if (! $map_url) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="pubcrawls-editor-placeholder"><span class="pubcrawls-placeholder-icon dashicons dashicons-location"></span><span class="pubcrawls-placeholder-text">' . esc_html__('City Map (No map_url field found)', 'pubcrawls-addon') . '</span></div>';
            }
            return;
        }
?>
        <div class="pubcrawl-city-map">
            <div class="pubcrawl-city-map__iframe-wrap">
                <iframe
                    class="pubcrawl-city-map__iframe"
                    src="<?php echo esc_url($map_url); ?>"
                    title="<?php echo esc_attr__('Interactive City Map', 'pubcrawls-addon'); ?>"
                    loading="lazy"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin">
                </iframe>
            </div>
        </div>
<?php
    }
}

