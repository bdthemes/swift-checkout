<?php

/**
 * Shortcode Handler Class
 *
 * @since 1.0.0
 * @package swift_checkout
 */

namespace SwiftCheckout\Classes;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Shortcode
 *
 * Handles the plugin shortcode functionality
 */
class Shortcode {
    /**
     * Initialize the shortcode
     *
     * @return void
     */
    public static function init() {
        if (!is_admin()) {
            add_shortcode('swift_checkout', array(__CLASS__, 'render_checkout_page'));
        }
    }

    /**
     * Render the checkout page
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render_checkout_page($atts) {
        // Make sure WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<p>' . __('WooCommerce is required for this shortcode to work.', 'swift-checkout') . '</p>';
        }

        // Extract attributes
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'layout' => 'standard',
            'categories' => '', // comma-separated category IDs
            'products' => '',   // comma-separated product IDs
        ), $atts, 'swift_checkout');

        // Start output capture
        ob_start();

        // Output container
        echo '<div class="spc-container" data-layout="' . esc_attr($atts['layout']) . '">';

        // Load product grid template
        self::load_template('product-grid.php', array('atts' => $atts));
        echo '<div class="spc-mini-cart">';
        echo '<h2 class="spc-mini-cart-title">' . esc_html__('Your Cart', 'swift-checkout') . '</h2>';
        self::load_template('mini-cart.php', array('atts' => $atts));
        echo '</div>';


        // Load checkout form template
        self::load_template('checkout-form.php', array('atts' => $atts));

        echo '</div>'; // Close container

        // Add React container (hidden for now, will be visible with JS enabled)
        echo '<div id="swift-checkout-app" style="display: none;"
			data-layout="' . esc_attr($atts['layout']) . '"
			data-categories="' . esc_attr($atts['categories']) . '"
			data-products="' . esc_attr($atts['products']) . '"
			data-product-id="' . (!empty($atts['product_id']) ? esc_attr($atts['product_id']) : '') . '"></div>';

        return ob_get_clean();
    }

    /**
     * Load a template file
     *
     * @param string $template_name Template file name
     * @param array $args Variables to pass to the template
     * @return void
     */
    private static function load_template($template_name, $args = array()) {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        // Look for template in theme first
        $template = locate_template('swift-checkout/' . $template_name);

        // If not found in theme, use plugin template
        if (!$template) {
            $template = SWIFT_CHECKOUT_PLUGIN_DIR . 'templates/frontend/' . $template_name;
        }

        if (file_exists($template)) {
            include $template;
        }
    }
}
