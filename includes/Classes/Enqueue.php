<?php

/**
 * Frontend Class forSwift Checkout
 *
 * @since 1.0.0
 * @package swift_checkout
 */

namespace SwiftCheckout\Classes;

use SwiftCheckout\Traits\Singleton;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Enqueue
 *
 * Handles frontend-related functionality for the plugin
 */
class Enqueue {

    use Singleton;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        self::init();
    }

    /**
     * Initialize frontend functionality
     *
     * @return void
     */
    public static function init() {
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('enqueue_block_assets', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Enqueue frontend scripts and styles
     *
     * @return void
     */
    public static function enqueue_scripts() {
        // Enqueue WordPress dashicons for field collapse icons
        wp_enqueue_style('dashicons');

        // Enqueue custom CSS
        wp_enqueue_style(
            'swift-checkout-style',
            SWIFT_CHECKOUT_PLUGIN_URL . 'assets/css/swift-checkout.css',
            array(),
            SWIFT_CHECKOUT_VERSION
        );

        // Enqueue custom JS
        wp_enqueue_script(
            'swift-checkout-script',
            SWIFT_CHECKOUT_PLUGIN_URL . 'assets/js/swift-checkout.js',
            array('jquery'),
            SWIFT_CHECKOUT_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'swift-checkout-script',
            'spcData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('swift_checkout_nonce'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'checkout_url' => wc_get_checkout_url(),
                'i18n' => array(
                    'collapse' => __('Collapse', 'swift-checkout'),
                    'expand' => __('Expand', 'swift-checkout'),
                    'personal_info' => __('Personal Information', 'swift-checkout'),
                    'billing_address' => __('Billing Address', 'swift-checkout'),
                    'shipping_address' => __('Shipping Address', 'swift-checkout'),
                    'order_details' => __('Order Details', 'swift-checkout'),
                ),
            )
        );
    }
}
