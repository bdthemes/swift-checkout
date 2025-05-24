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
        \add_action('admin_menu', [$this, 'register_admin_menu'], 20);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Register admin menu.
     */
    public function register_admin_menu() {
        if (!\current_user_can('manage_options')) {
            return;
        }

        \add_menu_page(
            \esc_html__('Swift Checkout', 'swift-checkout'),
            \esc_html__('Swift Checkout', 'swift-checkout'),
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

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages, Gutenberg editor, or Elementor
        $allowed_hooks = [
            'toplevel_page_swift-checkout',
            'post.php',
            'post-new.php',
            'elementor'
        ];

        $is_allowed_hook = false;
        foreach ($allowed_hooks as $allowed) {
            if (strpos($hook, $allowed) !== false) {
                $is_allowed_hook = true;
                break;
            }
        }

        if (!$is_allowed_hook && !\is_customize_preview()) {
            return;
        }

        // Enqueue WordPress dashboard icons
        \wp_enqueue_style('dashicons');

        // Enqueue admin styles
        \wp_enqueue_style(
            'swift-checkout-admin-style',
            SWIFT_CHECKOUT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SWIFT_CHECKOUT_VERSION
        );

        // Enqueue jQuery UI Sortable for drag and drop functionality
        \wp_enqueue_script('jquery-ui-sortable');

        // Enqueue admin scripts
        \wp_enqueue_script(
            'swift-checkout-admin-script',
            SWIFT_CHECKOUT_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            SWIFT_CHECKOUT_VERSION,
            true
        );

        // Localize admin script
        \wp_localize_script(
            'swift-checkout-admin-script',
            'spcAdminData',
            [
                'ajax_url' => \admin_url('admin-ajax.php'),
                'nonce' => \wp_create_nonce('spc_admin_nonce'),
                'i18n' => [
                    'drag_to_reorder' => \__('Drag to reorder', 'swift-checkout'),
                    'unnamed_field' => \__('Unnamed Field', 'swift-checkout'),
                    'field_removed' => \__('Field removed', 'swift-checkout'),
                    'field_added' => \__('Field added', 'swift-checkout'),
                    'save_success' => \__('Settings saved successfully', 'swift-checkout'),
                    'save_error' => \__('Error saving settings', 'swift-checkout'),
                    'collapse' => \__('Collapse', 'swift-checkout'),
                    'expand' => \__('Expand', 'swift-checkout'),
                ]
            ]
        );
    }
}
