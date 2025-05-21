<?php

/**
 * AJAX Handler Class forSwift Checkout
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
 * Class Ajax
 *
 * Handles AJAX requests for the plugin
 */
class Ajax {
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    public static function init() {
        // Register AJAX handlers for frontend
        add_action('wp_ajax_spc_add_to_cart', array(__CLASS__, 'add_to_cart'));
        add_action('wp_ajax_nopriv_spc_add_to_cart', array(__CLASS__, 'add_to_cart'));

        add_action('wp_ajax_spc_update_cart', array(__CLASS__, 'update_cart'));
        add_action('wp_ajax_nopriv_spc_update_cart', array(__CLASS__, 'update_cart'));

        add_action('wp_ajax_spc_remove_from_cart', array(__CLASS__, 'remove_from_cart'));
        add_action('wp_ajax_nopriv_spc_remove_from_cart', array(__CLASS__, 'remove_from_cart'));

        add_action('wp_ajax_spc_create_order', array(__CLASS__, 'create_order'));
        add_action('wp_ajax_nopriv_spc_create_order', array(__CLASS__, 'create_order'));

        add_action('wp_ajax_spc_get_order_received', array(__CLASS__, 'get_order_received_html'));
        add_action('wp_ajax_nopriv_spc_get_order_received', array(__CLASS__, 'get_order_received_html'));

        add_action('wp_ajax_spc_get_variation', array(__CLASS__, 'get_variation'));
        add_action('wp_ajax_nopriv_spc_get_variation', array(__CLASS__, 'get_variation'));
    }

    /**
     * AJAX handler for adding a product to cart
     *
     * @return void
     */
    public static function add_to_cart() {
        check_ajax_referer('spc_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $variations = isset($_POST['variations']) ? json_decode(stripslashes($_POST['variations']), true) : array();

        error_log(print_r($_POST, true));

        if ($product_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid product', 'swift-checkout')));
            exit;
        }

        // Add to cart
        $added = false;
        if (!empty($variations)) {
            // For variable products
            $data_store = \WC_Data_Store::load('product');
            $variation_id = $data_store->find_matching_product_variation(
                wc_get_product($product_id),
                $variations
            );

            if ($variation_id) {
                $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations);
            } else {
                wp_send_json_error(array('message' => __('No matching variation found.', 'swift-checkout')));
                exit;
            }
        } else {
            // For simple products
            $added = WC()->cart->add_to_cart($product_id, $quantity);
        }

        if ($added) {
            self::get_refreshed_fragments();
        } else {
            wp_send_json_error(array('message' => __('Could not add item to cart', 'swift-checkout')));
        }
    }

    /**
     * AJAX handler for updating cart
     *
     * @return void
     */
    public static function update_cart() {
        check_ajax_referer('spc_nonce', 'nonce');

        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

        if (empty($cart_item_key)) {
            wp_send_json_error(array('message' => __('Invalid cart item', 'swift-checkout')));
            exit;
        }

        WC()->cart->set_quantity($cart_item_key, $quantity);
        self::get_refreshed_fragments();
    }

    /**
     * AJAX handler for removing from cart
     *
     * @return void
     */
    public static function remove_from_cart() {
        check_ajax_referer('spc_nonce', 'nonce');

        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';

        if (empty($cart_item_key)) {
            wp_send_json_error(array('message' => __('Invalid cart item', 'swift-checkout')));
            exit;
        }

        WC()->cart->remove_cart_item($cart_item_key);
        self::get_refreshed_fragments();
    }

    /**
     * AJAX handler for creating an order
     *
     * @return void
     */
    public static function create_order() {
        check_ajax_referer('spc_nonce', 'nonce');

        if (WC()->cart->is_empty()) {
            wp_send_json_error(array('message' => __('Your cart is empty', 'swift-checkout')));
            exit;
        }

        // Get checkout fields - simplified version
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $address = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';
        $country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';

        // Extract first and last name from full name
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

        // Basic validation
        if (empty($name) || empty($phone) || empty($address)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', 'swift-checkout')));
            exit;
        }

        // Validate email format
        // if (!is_email($email)) {
        // 	wp_send_json_error(array('message' => __('Please enter a valid email address', 'swift-checkout')));
        // 	exit;
        // }

        try {
            // Create the order
            $order = wc_create_order();

            // Add products to order
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product_id = $cart_item['product_id'];
                $variation_id = $cart_item['variation_id'];
                $quantity = $cart_item['quantity'];

                if ($variation_id) {
                    $order->add_product(wc_get_product($variation_id), $quantity, array(
                        'variation' => $cart_item['variation'],
                    ));
                } else {
                    $order->add_product(wc_get_product($product_id), $quantity);
                }
            }

            // Set address with simplified fields
            $address_fields = array(
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'phone'      => $phone,
                'address_1'  => $address,
                // 'country'    => empty($country) ? 'US' : $country,
            );

            if (!empty($email)) {
                $address_fields['email'] = $email;
            }

            $order->set_address($address_fields, 'billing');
            $order->set_address($address_fields, 'shipping');

            // Set payment method
            $order->set_payment_method('bacs'); // Default to bank transfer

            // Calculate totals
            $order->calculate_totals();

            // Set order status to pending
            $order->update_status('pending', __('Order created fromSwift Checkout', 'swift-checkout'));

            // Empty cart
            WC()->cart->empty_cart();

            wp_send_json_success(array(
                'message' => __('Order created successfully', 'swift-checkout'),
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Get refreshed cart fragments
     *
     * @return void
     */
    private static function get_refreshed_fragments() {
        ob_start();
        Utils::load_template('mini-cart.php');
        $mini_cart = ob_get_clean();
        $data = array(
            'fragments' => array(
                '.spc-mini-cart-contents' => $mini_cart,
            ),
            'cart_hash' => WC()->cart->get_cart_hash(),
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_items_count' => WC()->cart->get_cart_contents_count(),
        );

        wp_send_json_success($data);
    }


    /**
     * Get order received HTML via AJAX
     *
     * @return void
     */
    public static function get_order_received_html() {
        check_ajax_referer('spc_nonce', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        if (empty($order_id)) {
            wp_send_json_error(array('message' => __('Invalid order ID', 'swift-checkout')));
            exit;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found', 'swift-checkout')));
            exit;
        }

        ob_start();
        Utils::load_template('order-received.php', array('order' => $order));
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
        ));
    }
    /**
     * Get variation details via AJAX
     */
    public static function get_variation() {
        check_ajax_referer('spc_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $attributes = isset($_POST['attributes']) ? $_POST['attributes'] : array();

        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            wp_send_json_error(array('message' => __('Invalid product.', 'swift-checkout')));
        }

        $variation = wc_get_product($variation_id);
        if (!$variation) {
            // Try to find matching variation
            $data_store = \WC_Data_Store::load('product');
            $variation_id = $data_store->find_matching_product_variation($product, $attributes);
            $variation = wc_get_product($variation_id);
        }

        if (!$variation) {
            wp_send_json_error(array('message' => __('No matching variation found.', 'swift-checkout')));
        }

        $price_html = $variation->get_price_html();
        $stock_status = $variation->get_stock_status();
        $is_purchasable = $variation->is_purchasable() && $variation->is_in_stock();

        $stock_html = '';
        if ($stock_status === 'instock') {
            $stock_html = '<span class="in-stock">' . __('In stock', 'swift-checkout') . '</span>';
        } else {
            $stock_html = '<span class="out-of-stock">' . __('Out of stock', 'swift-checkout') . '</span>';
        }

        wp_send_json_success(array(
            'price_html' => $price_html,
            'stock_html' => $stock_html,
            'is_purchasable' => $is_purchasable,
            'variation_id' => $variation_id
        ));
    }
}
