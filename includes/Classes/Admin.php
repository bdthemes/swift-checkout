<?php

/**
 * Plugin admin functions.
 *
 * @package SwiftCheckout
 */

namespace SwiftCheckout\Classes;

use SwiftCheckout\Traits\Singleton;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class forSwift Checkout plugin.
 */
class Admin {
    use Singleton;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu'], 20);
    }

    /**
     * Register admin menu.
     */
    public function register_admin_menu() {
        if (!current_user_can('manage_options')) {
            return;
        }

        add_menu_page(
            esc_html__('Swift Checkout', 'swift-checkout'),
            esc_html__('Swift Checkout', 'swift-checkout'),
            'manage_options',
            'swift-checkout',
            [$this, 'print_admin_page'],
            'dashicons-cart',
            58.7
        );
    }

    /**
     * Print admin page.
     */
    public function print_admin_page() {
        echo '<div class="swift-checkout-root"></div>';
    }
}
