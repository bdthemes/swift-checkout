<?php

/**
 * Builder Configuration Class
 *
 * @package Gebkit
 * @subpackage Gebkit/includes/Core
 */

namespace SwiftCheckout\Core;

use SwiftCheckout\Traits\Singleton;
use SwiftCheckout\Classes\Settings;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Builder Configuration Class
 *
 * Handles the configuration for different page builders
 */
class BuilderConfig extends BaseConfig {
    use Singleton;

    /**
     * Get the option name for this configuration
     *
     * @return string
     */
    protected function get_settings_name(): string {
        return 'builder_config';
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    protected function get_defaults(): array {
        return [
            'elementor' => [
                'active' => true,
                'label' => __('Elementor', 'swift-checkout'),
                'icon' => 'elementor',
                'description' => __('Elementor is a powerful page builder plugin for WordPress.', 'swift-checkout'),
            ],
            'gutenberg' => [
                'active' => true,
                'label' => __('Gutenberg', 'swift-checkout'),
                'icon' => 'gutenberg',
                'description' => __('Gutenberg is the default WordPress editor introduced in WordPress 5.0.', 'swift-checkout'),
            ],
        ];
    }
}
