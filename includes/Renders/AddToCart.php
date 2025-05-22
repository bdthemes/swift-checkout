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
        if (isset($attributes['auto_add_to_cart']) && $attributes['auto_add_to_cart'] === 'yes' && !empty($attributes['productId'])) {
            // Only add to cart if not already in cart
            $product_in_cart = false;
            if (function_exists('\WC') && isset(\WC()->cart)) {
                foreach (\WC()->cart->get_cart() as $cart_item) {
                    if ($cart_item['product_id'] == $attributes['productId']) {
                        $product_in_cart = true;
                        break;
                    }
                }

                if (!$product_in_cart) {
                    \WC()->cart->add_to_cart($attributes['productId']);
                }
            }
        }

        // print_r($attributes);
        $attributes['product_id'] = $attributes['productId'];
        if ($builder === 'gutenberg' && defined('REST_REQUEST')) {
            Utils::load_template('block-editor-markup.php', $attributes);
        } else { ?>
            <div class="spc-container <?php echo isset($attributes['stylePreset']) ? \esc_attr($attributes['stylePreset']) : ''; ?>"
                data-builder="<?php echo \esc_attr($builder); ?>"
                data-product-id="<?php echo \esc_attr($attributes['productId'] ?? ''); ?>"
                data-auto-add-to-cart="<?php echo \esc_attr($attributes['auto_add_to_cart'] ?? 'no'); ?>">
                <?php Utils::load_template('product-grid.php', $attributes); ?>
                <div class="spc-mini-cart">
                    <h2 class="spc-mini-cart-title"><?php \esc_html_e('Your Cart', 'swift-checkout'); ?></h2>
                    <?php Utils::load_template('mini-cart.php', $attributes); ?>
                </div>
                <?php Utils::load_template('checkout-form.php', $attributes); ?>
            </div>
<?php
        }
    }
}
