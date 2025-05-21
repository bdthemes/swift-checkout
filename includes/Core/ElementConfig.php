<?php

/**
 * Element Configuration Class
 *
 * @package Gebkit
 * @subpackage Gebkit/includes/Core
 */

namespace SwiftCheckout\Core;

use SwiftCheckout\Traits\Singleton;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Element Configuration Class
 *
 * Handles the configuration for elements/blocks/widgets
 */
class ElementConfig extends BaseConfig {
    use Singleton;

    /**
     * Get the option name for this configuration
     *
     * @return string
     */
    protected function get_settings_name(): string {
        return 'element_config';
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    protected function get_defaults(): array {
        return [
            'add-to-cart' => [
                'active' => true,
                'label' => __('Add to Cart', 'swift-checkout'),
                'icon' => 'cart',
                'category' => 'basic',
                'description' => __('A simple add to cart element.', 'swift-checkout'),
                'include' => [
                    'elementor' => [
                        'path' => SWIFT_CHECKOUT_FEATURES_DIR . 'Elementor/AddToCart/AddToCart.php',
                        'class' => 'SwiftCheckout\Features\Elementor\AddToCart\AddToCart',
                    ],
                    'gutenberg' => [
                        'path' => SWIFT_CHECKOUT_INCLUDES_DIR . 'assets/gutenberg/AddToCart/AddToCart.php',
                        'class' => 'SwiftCheckout\Features\Gutenberg\AddToCart\AddToCart',
                    ],
                ],
            ],
        ];
    }
}
