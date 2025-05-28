<?php

/**
 * Cart totals template
 *
 * @package swift_checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure WooCommerce is active
if (!function_exists('WC')) {
    return;
}

// Get cart instance
$cart = WC()->cart;
if (!$cart) {
    return;
}
?>

<div class="spc-cart-totals">
    <h2 class="spc-checkout-title">Order Summary</h2>
    <table class="spc-totals-table">
        <tbody>
            <?php
            // Subtotal
            ?>
            <tr class="spc-totals-subtotal">
                <th><?php esc_html_e('Subtotal', 'swift-checkout'); ?></th>
                <td><?php echo wp_kses_post($cart->get_cart_subtotal()); ?></td>
            </tr>
            <?php

            // Shipping (if calculated)
            if ($cart->needs_shipping()) {
            ?>
                <tr class="spc-totals-shipping">
                    <th><?php esc_html_e('Shipping', 'swift-checkout'); ?></th>
                    <td>
                        <?php
                        // Force WooCommerce to calculate shipping if needed
                        if (!WC()->shipping()->get_packages()) {
                            WC()->cart->calculate_shipping();
                        }

                        // Get available packages and shipping methods
                        $packages = WC()->shipping()->get_packages();
                        $available_methods = false;

                        // Check if we have any available shipping methods
                        if (!empty($packages)) {
                            foreach ($packages as $i => $package) {
                                if (!empty($package['rates'])) {
                                    $available_methods = true;
                                    break;
                                }
                            }
                        }

                        if ($available_methods) {
                            // Display shipping calculator if shipping is available
                            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
                            $formatted_destination = WC()->countries->get_formatted_address(array(
                                'country' => WC()->customer->get_shipping_country(),
                                'state' => WC()->customer->get_shipping_state(),
                                'postcode' => WC()->customer->get_shipping_postcode(),
                                'city' => WC()->customer->get_shipping_city(),
                            ));

                            // Display shipping destination if available
                            if ($formatted_destination) {
                                echo '<p class="spc-shipping-destination">';
                                printf(
                                    wp_kses_post(__('Shipping to %s.', 'swift-checkout')) . ' ',
                                    '<strong>' . esc_html($formatted_destination) . '</strong>'
                                );
                                echo '</p>';
                            }

                            // Show shipping methods
                            echo '<div class="spc-shipping-methods">';

                            foreach ($packages as $i => $package) {
                                if (!empty($package['rates'])) {
                                    echo '<ul class="spc-shipping-method-list">';

                                    // Sort rates by cost
                                    $rates = $package['rates'];
                                    uasort($rates, function ($a, $b) {
                                        if ($a->cost === $b->cost) return 0;
                                        return ($a->cost < $b->cost) ? -1 : 1;
                                    });

                                    foreach ($rates as $method_id => $method) {
                                        echo '<li class="spc-shipping-method-option">';
                                        printf(
                                            '<input type="radio" name="shipping_method[%1$d]" data-package="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="spc-shipping-method-select" %4$s />',
                                            $i,
                                            sanitize_title($method->id),
                                            esc_attr($method->id),
                                            checked($method->id, isset($chosen_shipping_methods[$i]) ? $chosen_shipping_methods[$i] : '', false)
                                        );

                                        printf(
                                            '<label for="shipping_method_%1$d_%2$s">%3$s</label>',
                                            $i,
                                            sanitize_title($method->id),
                                            wp_kses_post($method->get_label() . ': ' . wc_price($method->cost))
                                        );

                                        if ($method->get_meta_data()) {
                                            echo '<div class="spc-shipping-method-description">';
                                            foreach ($method->get_meta_data() as $meta) {
                                                if (is_string($meta)) {
                                                    echo '<small>' . wp_kses_post($meta) . '</small>';
                                                }
                                            }
                                            echo '</div>';
                                        }

                                        echo '</li>';
                                    }

                                    echo '</ul>';
                                }
                            }

                            echo '</div>';
                        } else {
                            // No shipping methods available
                            if (!empty($cart->get_cart())) {
                                if (WC()->countries->get_shipping_countries() === false) {
                                    echo wp_kses_post(__('Shipping is not available to your location.', 'swift-checkout'));
                                } else {
                                    echo wp_kses_post(__('No shipping options available.', 'swift-checkout'));
                                }
                            }
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }

            // Tax (if displayed separately)
            if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) {
                $tax_totals = $cart->get_tax_totals();
                if (!empty($tax_totals)) {
                ?>
                    <tr class="spc-totals-tax">
                        <th><?php esc_html_e('Tax', 'swift-checkout'); ?></th>
                        <td><?php echo wp_kses_post(WC()->cart->get_taxes_total()); ?></td>
                    </tr>
                <?php
                }
            }

            // Discount (if any)
            if ($cart->has_discount()) {
                ?>
                <tr class="spc-totals-discount">
                    <th><?php esc_html_e('Discount', 'swift-checkout'); ?></th>
                    <td>-<?php echo wp_kses_post(wc_price($cart->get_discount_total())); ?></td>
                </tr>
            <?php
            }

            // Total
            ?>
            <tr class="spc-totals-total">
                <th><?php esc_html_e('Total', 'swift-checkout'); ?></th>
                <td><?php echo wp_kses_post($cart->get_total()); ?></td>
            </tr>
        </tbody>
    </table>
</div>