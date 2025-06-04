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
        \add_action('wp_ajax_swift_checkout_add_to_cart', array(__CLASS__, 'add_to_cart'));
        \add_action('wp_ajax_nopriv_swift_checkout_add_to_cart', array(__CLASS__, 'add_to_cart'));

        \add_action('wp_ajax_swift_checkout_update_cart', array(__CLASS__, 'update_cart'));
        \add_action('wp_ajax_nopriv_swift_checkout_update_cart', array(__CLASS__, 'update_cart'));

        \add_action('wp_ajax_swift_checkout_remove_from_cart', array(__CLASS__, 'remove_from_cart'));
        \add_action('wp_ajax_nopriv_swift_checkout_remove_from_cart', array(__CLASS__, 'remove_from_cart'));

        \add_action('wp_ajax_swift_checkout_create_order', array(__CLASS__, 'create_order'));
        \add_action('wp_ajax_nopriv_swift_checkout_create_order', array(__CLASS__, 'create_order'));

        \add_action('wp_ajax_swift_checkout_get_order_received', array(__CLASS__, 'get_order_received_html'));
        \add_action('wp_ajax_nopriv_swift_checkout_get_order_received', array(__CLASS__, 'get_order_received_html'));

        \add_action('wp_ajax_swift_checkout_get_variation', array(__CLASS__, 'get_variation'));
        \add_action('wp_ajax_nopriv_swift_checkout_get_variation', array(__CLASS__, 'get_variation'));

        \add_action('wp_ajax_swift_checkout_update_shipping_methods', array(__CLASS__, 'update_shipping_methods'));
        \add_action('wp_ajax_nopriv_swift_checkout_update_shipping_methods', array(__CLASS__, 'update_shipping_methods'));

        \add_action('wp_ajax_swift_checkout_update_cart_totals', array(__CLASS__, 'update_cart_totals'));
        \add_action('wp_ajax_nopriv_swift_checkout_update_cart_totals', array(__CLASS__, 'update_cart_totals'));
    }

    /**
     * AJAX handler for adding a product to cart
     *
     * @return void
     */
    public static function add_to_cart() {
        check_ajax_referer('swift_checkout_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $variations = isset($_POST['variations']) ? json_decode(sanitize_text_field(wp_unslash($_POST['variations'])), true) : array();

        if ($product_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid product', 'swift-checkout')));
            exit;
        }

        // Always clear the cart completely before adding any new product
        if (function_exists('WC')) {
            WC()->cart->empty_cart();
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
        check_ajax_referer('swift_checkout_nonce', 'nonce');

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
        check_ajax_referer('swift_checkout_nonce', 'nonce');

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
        check_ajax_referer('swift_checkout_nonce', 'nonce');

        if (WC()->cart->is_empty()) {
            wp_send_json_error(array('message' => __('Your cart is empty', 'swift-checkout')));
            exit;
        }

        // Get checkout fields - all possible fields
        $fields = array(
            'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
            'first_name' => isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '',
            'last_name' => isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '',
            'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
            'company' => isset($_POST['company']) ? sanitize_text_field(wp_unslash($_POST['company'])) : '',

            'address' => isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '',
            'address_1' => isset($_POST['address_1']) ? sanitize_text_field(wp_unslash($_POST['address_1'])) : '',
            'address_2' => isset($_POST['address_2']) ? sanitize_text_field(wp_unslash($_POST['address_2'])) : '',
            'city' => isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '',
            'state' => isset($_POST['state']) ? sanitize_text_field(wp_unslash($_POST['state'])) : '',
            'postcode' => isset($_POST['postcode']) ? sanitize_text_field(wp_unslash($_POST['postcode'])) : '',
            'country' => isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '',

            'order_notes' => isset($_POST['order_notes']) ? sanitize_text_field(wp_unslash($_POST['order_notes'])) : '',
            'create_account' => isset($_POST['create_account']) ? (bool) $_POST['create_account'] : false,
            'shipping_address' => isset($_POST['shipping_address']) ? (bool) $_POST['shipping_address'] : false,
            'shipping_method' => isset($_POST['shipping_method']) ? sanitize_text_field(wp_unslash($_POST['shipping_method'])) : '',
        );

        // Get the required fields configuration
        $required_fields = isset($_POST['required_fields']) ? json_decode(sanitize_text_field(wp_unslash($_POST['required_fields'])), true) : array('name' => true, 'phone' => true, 'address' => true);

        // If name is provided but not first/last name, split it
        if (!empty($fields['name']) && (empty($fields['first_name']) || empty($fields['last_name']))) {
            $name_parts = explode(' ', $fields['name'], 2);
            $fields['first_name'] = $name_parts[0];
            $fields['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
        }

        // Basic validation - only check fields that are marked as required
        $validation_errors = array();
        $field_errors = array();

        foreach ($required_fields as $field => $is_required) {
            if ($is_required && empty($fields[$field])) {
                $field_label = ucfirst(str_replace('_', ' ', $field));
                /* translators: %s: Field label (e.g. "First Name", "Email", etc.) */
                $error_message = sprintf(__('Please enter your %s', 'swift-checkout'), $field_label);
                $validation_errors[] = $error_message;
                $field_errors[$field] = $error_message;
            }
        }

        // Email format validation (only if email is provided)
        if (!empty($fields['email']) && !is_email($fields['email'])) {
            $error_message = __('Please enter a valid email address', 'swift-checkout');
            $validation_errors[] = $error_message;
            $field_errors['email'] = $error_message;
        }

        if (!empty($validation_errors)) {
            wp_send_json_error(array(
                'message' => implode('<br>', $validation_errors),
                'field_errors' => $field_errors
            ));
            exit;
        }

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

            // Prepare address fields
            $address_fields = array();

            // Map fields to WooCommerce address fields
            $address_mapping = array(
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'company' => 'company',
                'phone' => 'phone',
                'email' => 'email',
                'address' => 'address_1', // Map full address to address_1
                'address_1' => 'address_1',
                'address_2' => 'address_2',
                'city' => 'city',
                'state' => 'state',
                'postcode' => 'postcode',
                'country' => 'country',
            );

            foreach ($address_mapping as $field => $wc_field) {
                if (!empty($fields[$field])) {
                    $address_fields[$wc_field] = $fields[$field];
                }
            }

            // Set default country if not provided
            if (empty($address_fields['country'])) {
                $address_fields['country'] = WC()->countries->get_base_country();
            }

            // Set billing and shipping addresses
            $order->set_address($address_fields, 'billing');

            // Handle shipping address if different shipping address is checked
            if (!empty($fields['shipping_address']) && $fields['shipping_address']) {
                $shipping_fields = array();

                // Map shipping address fields
                $shipping_mapping = array(
                    'shipping_first_name' => 'first_name',
                    'shipping_last_name' => 'last_name',
                    'shipping_company' => 'company',
                    'shipping_address_1' => 'address_1',
                    'shipping_address_2' => 'address_2',
                    'shipping_city' => 'city',
                    'shipping_state' => 'state',
                    'shipping_postcode' => 'postcode',
                    'shipping_country' => 'country',
                );

                foreach ($shipping_mapping as $post_field => $wc_field) {
                    if (isset($_POST[$post_field])) {
                        $shipping_fields[$wc_field] = sanitize_text_field(wp_unslash($_POST[$post_field]));
                    }
                }

                // Set default country if not provided for shipping
                if (empty($shipping_fields['country'])) {
                    $shipping_fields['country'] = WC()->countries->get_base_country();
                }

                // Set shipping address
                $order->set_address($shipping_fields, 'shipping');
            } else {
                // Use same address for shipping
                $order->set_address($address_fields, 'shipping');
            }

            // Add order notes if provided
            if (!empty($fields['order_notes'])) {
                $order->add_order_note($fields['order_notes'], 1, true); // Customer note
            }

            // Handle shipping method if selected
            if (!empty($fields['shipping_method'])) {
                // Check if shipping method contains rate ID format (method:instance)
                if (strpos($fields['shipping_method'], ':') !== false) {
                    list($method_id, $instance_id) = explode(':', $fields['shipping_method'], 2);

                    // Add shipping line based on selected method
                    $shipping_rate = null;

                    // Try to find the selected shipping rate
                    $packages = WC()->shipping()->get_packages();
                    if (!empty($packages)) {
                        foreach ($packages as $package) {
                            if (!empty($package['rates']) && isset($package['rates'][$fields['shipping_method']])) {
                                $shipping_rate = $package['rates'][$fields['shipping_method']];
                                break;
                            }
                        }
                    }

                    if ($shipping_rate) {
                        // Add shipping line using the rate
                        $item = new \WC_Order_Item_Shipping();
                        $item->set_method_title($shipping_rate->get_label());
                        $item->set_method_id($method_id);
                        $item->set_instance_id($instance_id);
                        $item->set_total($shipping_rate->get_cost());
                        $item->set_taxes($shipping_rate->get_taxes());

                        // Add any meta data
                        if ($shipping_rate->get_meta_data()) {
                            foreach ($shipping_rate->get_meta_data() as $key => $value) {
                                $item->add_meta_data($key, $value, true);
                            }
                        }

                        $order->add_item($item);
                    } else {
                        // If we couldn't find the rate in the packages, add it manually
                        // Get shipping method instance
                        $shipping_method = \WC_Shipping_Zones::get_shipping_method($instance_id);

                        if ($shipping_method) {
                            $cost = 0;
                            $title = $shipping_method->get_title();

                            // Get cost if available
                            if (method_exists($shipping_method, 'get_option')) {
                                if ($method_id === 'flat_rate') {
                                    $cost = $shipping_method->get_option('cost');
                                }
                            }

                            // Add shipping line
                            $item = new \WC_Order_Item_Shipping();
                            $item->set_method_title($title);
                            $item->set_method_id($method_id);
                            $item->set_instance_id($instance_id);
                            $item->set_total($cost);
                            $order->add_item($item);
                        }
                    }
                } else {
                    // Simple method handling (fallback)
                    $item = new \WC_Order_Item_Shipping();
                    $item->set_method_title($fields['shipping_method']);
                    $item->set_method_id($fields['shipping_method']);
                    $item->set_total(0); // Default to zero if we can't determine cost
                    $order->add_item($item);
                }
            }

            // Set payment method
            $order->set_payment_method('bacs'); // Default to bank transfer

            // Calculate totals
            $order->calculate_totals();

            // Set order status to pending
            $order->update_status('pending', __('Order created from Swift Checkout', 'swift-checkout'));

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
                '.swift-checkout-mini-cart-contents' => $mini_cart,
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
        check_ajax_referer('swift_checkout_nonce', 'nonce');

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
        check_ajax_referer('swift_checkout_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $attributes = isset($_POST['attributes']) ? json_decode(sanitize_text_field(wp_unslash($_POST['attributes'])), true) : array();

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

    /**
     * Update shipping methods based on customer address
     */
    public static function update_shipping_methods() {
        check_ajax_referer('swift_checkout_nonce', 'nonce');

        // Get the destination address from the POST data
        $country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';
        $state = isset($_POST['state']) ? sanitize_text_field(wp_unslash($_POST['state'])) : '';
        $postcode = isset($_POST['postcode']) ? sanitize_text_field(wp_unslash($_POST['postcode'])) : '';
        $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';

        if (empty($country)) {
            wp_send_json_error(array('message' => __('Country is required', 'swift-checkout')));
            exit;
        }

        // Set customer address in session
        if (function_exists('WC')) {
            WC()->customer->set_billing_location($country, $state, $postcode, $city);
            WC()->customer->set_shipping_location($country, $state, $postcode, $city);

            // For empty cart situations, create a temporary dummy product
            $cart_is_empty = WC()->cart->is_empty();
            $temp_product_added = false;

            if ($cart_is_empty) {
                // Find a valid, simple product to temporarily add to cart
                $products = wc_get_products(array(
                    'limit' => 1,
                    'type' => 'simple',
                    'status' => 'publish',
                ));

                if (!empty($products)) {
                    $temp_product = $products[0];
                    WC()->cart->add_to_cart($temp_product->get_id(), 1);
                    $temp_product_added = true;
                }
            }

            // Recalculate shipping for the cart
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();

            // Get available shipping methods
            ob_start();

            $shipping_methods = array();

            // Get shipping packages
            $packages = WC()->shipping()->get_packages();

            if (!empty($packages)) {
                $package = reset($packages); // Get first package
                if (!empty($package['rates'])) {
                    echo '<div class="swift-checkout-shipping-methods-list">';
                    foreach ($package['rates'] as $method_id => $method) {
                        echo '<div class="swift-checkout-shipping-method">';
                        echo '<label>';
                        echo '<input type="radio" name="shipping_method" value="' . esc_attr($method_id) . '" class="swift-checkout-shipping-method-input" data-trigger="update-cart">';
                        echo esc_html($method->get_label());
                        echo ' - ' . wp_kses_post($method->get_cost_html());
                        echo '</label>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    // No shipping methods for this address - fall back to zones
                    self::get_shipping_zones_html();
                }
            } else {
                // Fallback to shipping zones if no packages available
                self::get_shipping_zones_html();
            }

            // Remove the temporary product if it was added
            if ($cart_is_empty && $temp_product_added) {
                WC()->cart->empty_cart();
            }
        } else {
            // Fallback message if WooCommerce is not available
            echo '<p>' . esc_html__('No shipping methods available. WooCommerce is not active.', 'swift-checkout') . '</p>';
        }

        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html
        ));
    }

    /**
     * Helper method to get HTML for shipping zones
     */
    private static function get_shipping_zones_html() {
        if (!class_exists('WC_Shipping_Zones')) {
            return;
        }

        $shipping_zones = \WC_Shipping_Zones::get_zones();

        // Add methods from each zone
        foreach ($shipping_zones as $zone_id => $zone_data) {
            $zone = new \WC_Shipping_Zone($zone_id);
            $zone_methods = $zone->get_shipping_methods(true);

            if (!empty($zone_methods)) {
                echo '<div class="swift-checkout-shipping-zone">';
                echo '<h4 class="swift-checkout-zone-name">' . esc_html($zone_data['zone_name']) . '</h4>';

                foreach ($zone_methods as $method) {
                    $method_id = $method->id . ':' . $method->instance_id;
                    $method_title = $method->get_title();
                    $method_cost = '';

                    // Get cost if available
                    if (method_exists($method, 'get_option')) {
                        if ($method->id === 'flat_rate') {
                            $method_cost = $method->get_option('cost');
                        }
                    }

                    echo '<div class="swift-checkout-shipping-method">';
                    echo '<label>';
                    echo '<input type="radio" name="shipping_method" value="' . esc_attr($method_id) . '" class="swift-checkout-shipping-method-input" data-trigger="update-cart">';
                    echo esc_html($method_title);
                    if ($method_cost) {
                        echo ' - ' . wp_kses_post(\wc_price($method_cost));
                    }
                    echo '</label>';
                    echo '</div>';
                }

                echo '</div>';
            }
        }

        // Add rest of world zone if available
        $rest_of_world = new \WC_Shipping_Zone(0);
        $rest_methods = $rest_of_world->get_shipping_methods(true);

        if (!empty($rest_methods)) {
            echo '<div class="swift-checkout-shipping-zone">';
            echo '<h4 class="swift-checkout-zone-name">' . esc_html__('Rest of World', 'swift-checkout') . '</h4>';

            foreach ($rest_methods as $method) {
                $method_id = $method->id . ':' . $method->instance_id;
                $method_title = $method->get_title();
                $method_cost = '';

                // Get cost if available
                if (method_exists($method, 'get_option')) {
                    if ($method->id === 'flat_rate') {
                        $method_cost = $method->get_option('cost');
                    }
                }

                echo '<div class="swift-checkout-shipping-method">';
                echo '<label>';
                echo '<input type="radio" name="shipping_method" value="' . esc_attr($method_id) . '" class="swift-checkout-shipping-method-input" data-trigger="update-cart">';
                echo esc_html($method_title);
                if ($method_cost) {
                    echo ' - ' . wp_kses_post(\wc_price($method_cost));
                }
                echo '</label>';
                echo '</div>';
            }

            echo '</div>';
        }
    }

    /**
     * Update cart totals when shipping method is selected
     */
    public static function update_cart_totals() {
        check_ajax_referer('swift_checkout_nonce', 'nonce');

        // Get the selected shipping method
        $shipping_method = isset($_POST['shipping_method']) ? sanitize_text_field(wp_unslash($_POST['shipping_method'])) : '';

        $response = array(
            'success' => false,
            'shipping_total' => '',
            'cart_total' => ''
        );

        if (empty($shipping_method)) {
            wp_send_json_error($response);
            return;
        }

        // Update the chosen shipping method
        if (function_exists('WC') && WC()->cart) {
            try {
                // Split the shipping method if it has format like "flat_rate:1"
                if (strpos($shipping_method, ':') !== false) {
                    list($method_id, $instance_id) = explode(':', $shipping_method, 2);
                }

                // Set the chosen shipping method for all packages
                WC()->session->set('chosen_shipping_methods', array($shipping_method));

                // Make sure the method is applied immediately
                WC()->shipping()->calculate_shipping();

                // Recalculate totals
                WC()->cart->calculate_shipping();
                WC()->cart->calculate_totals();

                // Get updated values
                $response['success'] = true;

                // If cart is empty, show zero values
                if (WC()->cart->is_empty()) {
                    $response['shipping_total'] = wp_kses_post(wc_price(0));
                    $response['cart_total'] = wp_kses_post(wc_price(0));
                } else {
                    $response['shipping_total'] = wp_kses_post(WC()->cart->get_cart_shipping_total());
                    $response['cart_total'] = wp_kses_post(WC()->cart->get_total());
                }
            } catch (Exception $e) {
                // Try to return data anyway to prevent UI issues
                $response['success'] = true;
                if (WC()->cart->is_empty()) {
                    $response['shipping_total'] = wp_kses_post(wc_price(0));
                    $response['cart_total'] = wp_kses_post(wc_price(0));
                } else {
                    $response['shipping_total'] = wp_kses_post(WC()->cart->get_cart_shipping_total());
                    $response['cart_total'] = wp_kses_post(WC()->cart->get_total());
                }
            }
        }

        wp_send_json_success($response);
    }
}
