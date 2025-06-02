<?php

/**
 * Shipping methods template
 *
 * @package swift_checkout
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>

<h3 class="swift-checkout-shipping-methods-title"><?php esc_html_e('Shipping Methods', 'swift-checkout'); ?></h3>
<div id="swift-checkout-shipping-methods" class="swift-checkout-shipping-methods">
    <?php
    // Get available shipping methods from WooCommerce
    $shipping_methods = array();
    $first_method = true; // Track the first method to auto-select it

    if (function_exists('WC') && WC() && WC()->cart && !WC()->cart->is_empty()) {
        // Calculate shipping for current cart and destination
        $packages = WC()->shipping()->get_packages();

        if (!empty($packages)) {
            $package = reset($packages); // Get first package
            if (!empty($package['rates'])) {
                $shipping_methods = $package['rates'];
            }
        }

        // If no shipping methods available yet (before address entered)
        // Show default flat rate options based on zones
        if (empty($shipping_methods)) {
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

                        $selected = $first_method ? ' checked="checked"' : '';
                        $method_class = $first_method ? 'swift-checkout-shipping-method selected' : 'swift-checkout-shipping-method';

                        echo '<div class="' . $method_class . '">';
                        echo '<label>';
                        echo '<input type="radio" name="shipping_method" value="' . esc_attr($method_id) . '" class="swift-checkout-shipping-method-input" data-trigger="update-cart"' . $selected . '>';
                        echo esc_html($method_title);
                        if ($method_cost) {
                            echo ' - ' . wp_kses_post(\wc_price($method_cost));
                        }
                        echo '</label>';
                        echo '</div>';

                        $first_method = false; // Only the first method gets selected
                    }

                    echo '</div>';
                }
            }
        } else {
            // Display calculated shipping methods
            foreach ($shipping_methods as $method) {
                $selected = $first_method ? ' checked="checked"' : '';
                $method_class = $first_method ? 'swift-checkout-shipping-method selected' : 'swift-checkout-shipping-method';

                echo '<div class="' . $method_class . '">';
                echo '<label>';
                echo '<input type="radio" name="shipping_method" value="' . esc_attr($method->id) . '" class="swift-checkout-shipping-method-input" data-trigger="update-cart"' . $selected . '>';
                echo esc_html($method->get_label());
                echo ' - ' . wp_kses_post(\wc_price($method->get_cost()));
                echo '</label>';
                echo '</div>';

                $first_method = false; // Only the first method gets selected
            }
        }
    } else {
        // Fallback for when WooCommerce isn't available or cart is empty
        echo '<p>' . esc_html__('No shipping methods available. Please add items to your cart.', 'swift-checkout') . '</p>';
    }
    ?>
    <div class="swift-checkout-shipping-methods-loading" style="display: none;">
        <?php esc_html_e('Calculating shipping options...', 'swift-checkout'); ?>
    </div>
</div>