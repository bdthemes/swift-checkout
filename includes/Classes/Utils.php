<?php

/**
 * Utilities Class
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
 * Class Utils
 *
 * Utility functions for the plugin
 */
class Utils {
    /**
     * Check if we're on an admin page
     *
     * @return bool
     */
    public static function is_plugin_admin_page() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        return $screen && strpos($screen->id, 'swift-checkout') !== false;
    }

    /**
     * Get plugin settings
     *
     * @param string $key Optional setting key
     * @param mixed  $default Default value if setting doesn't exist
     * @return mixed
     */
    public static function get_settings($key = '', $default = false) {
        $settings = get_option('spc_settings', array());

        if (empty($key)) {
            return $settings;
        }

        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Update plugin settings
     *
     * @param string|array $key Setting key or array of key => value pairs
     * @param mixed  $value Setting value (not used if $key is array)
     * @return bool
     */
    public static function update_settings($key, $value = '') {
        $settings = self::get_settings();

        if (is_array($key)) {
            $settings = array_merge($settings, $key);
        } else {
            $settings[$key] = $value;
        }

        return update_option('spc_settings', $settings);
    }

    /**
     * Format price
     *
     * @param float $price Price to format
     * @return string
     */
    public static function format_price($price) {
        return function_exists('wc_price') ? wc_price($price) : sprintf('$%0.2f', $price);
    }

    /**
     * Parse array of IDs from comma-separated string
     *
     * @param string $string Comma-separated IDs
     * @return array
     */
    public static function parse_id_list($string) {
        if (empty($string)) {
            return array();
        }

        return array_map('absint', explode(',', $string));
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    public static function is_woocommerce_active() {
        $plugin_path = 'woocommerce/woocommerce.php';
        return in_array($plugin_path, apply_filters('active_plugins', get_option('active_plugins'))) ||
            (is_multisite() && array_key_exists($plugin_path, get_site_option('active_sitewide_plugins', array())));
    }

    /**
     * Load a template file
     *
     * @param string $template_name Template file name
     * @param array $args Variables to pass to the template
     * @return void
     */
    public static function load_template($template_name, $args = array()) {
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

    /**
     * Set the current product ID for a specific checkout instance
     *
     * @param int $product_id Product ID
     * @return void
     */
    public static function set_current_product_id($product_id) {
        // Use a cookie to store the current product ID
        if (!headers_sent() && $product_id) {
            setcookie('spc_current_product_id', $product_id, time() + 3600, '/');
            $_COOKIE['spc_current_product_id'] = $product_id;
        }
    }

    /**
     * Get the current product ID for a specific checkout instance
     *
     * @return int|null Product ID or null if not found
     */
    public static function get_current_product_id() {
        return isset($_COOKIE['spc_current_product_id']) ? (int)$_COOKIE['spc_current_product_id'] : null;
    }

    /**
     * Filter cart items to show only those for the current product
     *
     * @param array $cart_items Array of cart items
     * @param int $product_id Product ID to filter by
     * @return array Filtered cart items
     */
    public static function filter_cart_items_by_product($cart_items, $product_id) {
        if (!$product_id || empty($cart_items)) {
            return $cart_items;
        }

        $filtered_items = array();
        foreach ($cart_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                $filtered_items[$key] = $item;
            }
        }

        return $filtered_items;
    }
}
