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
class AddToCart {
    /**
     * Get Add to Cart markup
     *
     * @param array $attributes Element attributes
     * @return string
     */
    public static function get_markup($builder, $attributes, $object = null) {
        // Handle auto add to cart if enabled
        if (isset($attributes['auto_add_to_cart']) && ($attributes['auto_add_to_cart'] === true || $attributes['auto_add_to_cart'] === 'yes') && !empty($attributes['productId'])) {
            // Only add to cart if not already in cart
            $product_in_cart = false;
            if (function_exists('\\WC') && isset(\WC()->cart)) {
                foreach (\WC()->cart->get_cart() as $cart_item) {
                    if ($cart_item['product_id'] == $attributes['productId']) {
                        $product_in_cart = true;
                        break;
                    }
                }

                if (!$product_in_cart) {
                    // Clear cart first to ensure only one product is in the cart
                    \WC()->cart->empty_cart();
                    \WC()->cart->add_to_cart($attributes['productId']);
                }
            }
        }

        // Process checkout fields configuration
        $checkout_fields = array();
        $enable_custom_fields = isset($attributes['enable_custom_fields']) && ($attributes['enable_custom_fields'] === 'yes' || $attributes['enable_custom_fields'] === true);

        if ($enable_custom_fields && !empty($attributes['checkout_fields'])) {
            $checkout_fields = $attributes['checkout_fields'];
        }

        // Add checkout fields data to the attributes
        $attributes['enable_custom_fields'] = $enable_custom_fields ? 'yes' : 'no';
        $attributes['checkout_fields'] = $checkout_fields;
        $attributes['align'] = isset($attributes['align']) ? 'align' . $attributes['align'] : '';

        $attributes['product_id'] = $attributes['productId'];
        if ($builder === 'gutenberg' && defined('REST_REQUEST')) {
            Utils::load_template('block-editor-markup.php', $attributes);
        } else { ?>
            <div class="spc-container <?php echo esc_attr(isset($attributes['align']) ? $attributes['align'] : ''); ?> <?php echo esc_attr(isset($attributes['stylePreset']) ? $attributes['stylePreset'] : ''); ?>"
                data-builder="<?php echo esc_attr($builder); ?>"
                data-product-id="<?php echo esc_attr($attributes['productId'] ?? ''); ?>"
                data-auto-add-to-cart="<?php echo esc_attr($attributes['auto_add_to_cart'] ?? 'no'); ?>">
                <?php Utils::load_template('add-to-cart.php', $attributes); ?>
                <div class="spc-mini-cart">
                    <h2 class="spc-mini-cart-title"><?php \esc_html_e('Your Cart', 'swift-checkout'); ?></h2>
                    <?php
                    // Pass the specific product ID to mini-cart
                    $mini_cart_args = $attributes;
                    $mini_cart_args['specific_product_id'] = $attributes['productId'] ?? 0;
                    Utils::load_template('mini-cart.php', $mini_cart_args);
                    ?>
                </div>
                <?php Utils::load_template('checkout-form.php', $attributes); ?>
            </div>
<?php
        }
    }
}
