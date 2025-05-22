<?php

/**
 * Common Add to Cart Render Class
 *
 * @package SwiftCheckout
 * @subpackage SwiftCheckout/includes/Renders
 */

namespace SwiftCheckout\Renders;

use SwiftCheckout\Classes\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress global functions compatibility layer
 */
if (!function_exists('SwiftCheckout\Renders\esc_attr')) {
    function esc_attr($text) {
        return \esc_attr($text);
    }
}

if (!function_exists('SwiftCheckout\Renders\esc_html')) {
    function esc_html($text) {
        return \esc_html($text);
    }
}

if (!function_exists('SwiftCheckout\Renders\esc_html_e')) {
    function esc_html_e($text, $domain = 'default') {
        \esc_html_e($text, $domain);
    }
}

if (!function_exists('SwiftCheckout\Renders\esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return \esc_html__($text, $domain);
    }
}

if (!function_exists('SwiftCheckout\Renders\sanitize_title')) {
    function sanitize_title($title, $fallback_title = '', $context = 'save') {
        return \sanitize_title($title, $fallback_title, $context);
    }
}

if (!function_exists('SwiftCheckout\Renders\wc_get_product')) {
    function wc_get_product($product = false) {
        return \wc_get_product($product);
    }
}

if (!function_exists('SwiftCheckout\Renders\wc_get_product_terms')) {
    function wc_get_product_terms($product_id, $taxonomy, $args = array()) {
        return \wc_get_product_terms($product_id, $taxonomy, $args);
    }
}

if (!function_exists('SwiftCheckout\Renders\wc_attribute_label')) {
    function wc_attribute_label($name, $product = '') {
        return \wc_attribute_label($name, $product);
    }
}

if (!function_exists('SwiftCheckout\Renders\get_block_wrapper_attributes')) {
    function get_block_wrapper_attributes($attributes = array()) {
        return \get_block_wrapper_attributes($attributes);
    }
}

if (!defined('SwiftCheckout\Renders\REST_REQUEST') && defined('REST_REQUEST')) {
    define('SwiftCheckout\Renders\REST_REQUEST', REST_REQUEST);
}

class AddToCart {

    protected static function get_wrapper_start($attributes = [], $builder = 'gutenberg', $object = null) {
        // Normalize class attribute (ensure array)
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = implode(' ', $attributes['class']);
        }

        switch ($builder) {
            case 'gutenberg':
                // Gutenberg expects just the array of attributes
                $wrapper_attributes = \get_block_wrapper_attributes($attributes);
                break;

            case 'elementor':
                // Elementor expects associative array of attributes
                $object->add_render_attribute('_root', $attributes);
                $wrapper_attributes = $object->get_render_attribute_string('_root');
                break;

            default:
                $wrapper_attributes = '';
                break;
        }

        return sprintf('<div %s>', $wrapper_attributes);
    }

    protected static function get_wrapper_end() {
        return '</div>';
    }

    protected static function get_original_markup($attributes) {
        Utils::load_template('product-grid.php', $attributes);
    }

    /**
     * Get heading markup
     *
     * @param array $attributes Element attributes
     * @return string
     */
    public static function get_markup($builder, $attributes, $object = null) {
        $attributes['product_id'] = $attributes['productId'];
        if ($builder === 'gutenberg' && defined('REST_REQUEST')) {
            Utils::load_template('block-editor-markup.php', $attributes);
        } else { ?>
            <div class="spc-container" data-builder="<?php echo \esc_attr($builder); ?>">
                <?php Utils::load_template('product-grid.php', $attributes); ?>
                <div class="spc-mini-cart">
                    <h2 class="spc-mini-cart-title"><?php \esc_html_e('Your Cart', 'swift-checkout'); ?></h2>
                    <?php Utils::load_template('mini-cart.php', $attributes); ?>
                </div>
                <?php Utils::load_template('checkout-form.php', $attributes); ?>
            </div>
<?php
        }
    }
}
