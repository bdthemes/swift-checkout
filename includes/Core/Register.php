<?php

/**
 * Registration Handler Class
 *
 * @package SwiftCheckout
 * @subpackage SwiftCheckout/includes/Core
 */

namespace SwiftCheckout\Core;

use SwiftCheckout\Traits\Singleton;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Registration Handler Class
 *
 * Handles registration of elements for different builders
 */
class Register {
    use Singleton;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->register_elements();
        $this->register_categories();
    }

    /**
     * Register all elements
     */
    public function register_elements(): void {
        $builders = BuilderConfig::get_instance()->get_settings();

        if (!empty($builders)) {
            foreach ($builders as $slug => $builder) {
                if (!empty($builder['active'])) {
                    $this->register_builder_elements($slug);
                }
            }
        }
    }

    /**
     * Register elements for a specific builder
     *
     * @param string $slug Builder slug
     */
    private function register_builder_elements(string $builder_slug): void {
        $elements = ElementConfig::get_instance()->get_settings();

        if (!empty($elements)) {
            foreach ($elements as $element) {
                if (!empty($element['active'])) {
                    $this->register_element($element, $builder_slug);
                }
            }
        }
    }

    /**
     * Register an element for a specific builder
     *
     * @param array $element Element settings
     * @param array $builder Builder settings
     */
    private function register_element(array $element, string $builder_slug): void {
        switch ($builder_slug) {
            case 'elementor':
                $this->register_elementor_widgets($element, $builder_slug);
                break;
            case 'gutenberg':
                $this->register_gutenberg_blocks($element, $builder_slug);
                break;
        }
    }

    /**
     * Register elements for elementor
     *
     * @param array $element Element settings
     * @param string $builder_slug Builder slug
     */
    private function register_elementor_widgets(array $element, string $builder_slug): void {
        add_action('elementor/widgets/register', function ($widgets_manager) use ($element, $builder_slug) {
            $element_class = $element['include'][$builder_slug]['class'] ?? '';
            if (class_exists($element_class)) {
                $widgets_manager->register(new $element_class());
            }
        });
    }

    /**
     * Register elements for gutenberg
     *
     * @param array $element Element settings
     * @param string $builder_slug Builder slug
     */
    private function register_gutenberg_blocks(array $element, string $builder_slug): void {
        // Get block path from element settings
        $block_path = $element['include'][$builder_slug]['path'] ?? '';

        if (!empty($block_path) && is_string($block_path)) {
            // Get the directory path without the PHP file
            $block_dir = dirname($block_path);

            // Only register if the directory exists
            if (file_exists($block_dir)) {
                register_block_type($block_dir);
            }
        }
    }


    /**
     * Register categories for each builder
     */
    public function register_categories(): void {
        // Register Elementor category
        add_action('elementor/elements/categories_registered', function ($elements_manager) {
            $elements_manager->add_category(
                'swift-checkout',
                [
                    'title' => esc_html__('Swift Checkout', 'swift-checkout'),
                    'icon' => 'fa fa-shopping-cart',
                ]
            );
        });

        // Register Gutenberg category
        add_filter('block_categories_all', function ($categories) {
            return array_merge(
                $categories,
                [
                    [
                        'slug' => 'swift-checkout',
                        'title' => esc_html__('Swift Checkout', 'swift-checkout'),
                        'icon' => 'cart',
                    ],
                ]
            );
        });
    }
}
