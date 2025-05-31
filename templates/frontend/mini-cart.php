<?php

/**
 * Mini cart template
 *
 * @package swift_checkout
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::instance()->editor->is_edit_mode()) {
    WC()->frontend_includes();
    WC()->initialize_session();
    WC()->initialize_cart();
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

// Get the specific product ID for this checkout instance
$specific_product_id = isset($specific_product_id) ? (int)$specific_product_id : 0;

// Get cart items
$cart_items = $cart->get_cart();

// Filter cart items to only show this specific product if specified
if ($specific_product_id > 0) {
    $filtered_cart_items = array();
    foreach ($cart_items as $cart_item_key => $cart_item) {
        if ($cart_item['product_id'] == $specific_product_id) {
            $filtered_cart_items[$cart_item_key] = $cart_item;
        }
    }
    $cart_items = $filtered_cart_items;
}
?>

<div class="swift-checkout-mini-cart-contents">
    <table class="swift-checkout-cart-items">
        <thead>
            <tr>
                <th class="product-name"><?php esc_html_e('Product', 'swift-checkout'); ?></th>
                <th class="product-price"><?php esc_html_e('Price', 'swift-checkout'); ?></th>
                <th class="product-quantity"><?php esc_html_e('Quantity', 'swift-checkout'); ?></th>
                <th class="product-subtotal"><?php esc_html_e('Subtotal', 'swift-checkout'); ?></th>
                <th class="product-remove" style="text-align: right;"><?php esc_html_e('Remove', 'swift-checkout'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($cart_items)) :
                foreach ($cart_items as $cart_item_key => $cart_item) :
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0) :
            ?>
                        <tr class="swift-checkout-cart-item" data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                            <td class="product-name">
                                <?php echo esc_html($_product->get_name()); ?>
                            </td>
                            <td class="product-price">
                                <?php echo wp_kses_post($cart->get_product_price($_product)); ?>
                            </td>
                            <td class="product-quantity">
                                <div class="swift-checkout-quantity">
                                    <button class="swift-checkout-qty-minus" data-item-key="<?php echo esc_attr($cart_item_key); ?>">-</button>
                                    <input type="number" min="1" class="swift-checkout-qty-input"
                                        value="<?php echo esc_attr($cart_item['quantity']); ?>"
                                        data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                                    <button class="swift-checkout-qty-plus" data-item-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                </div>
                            </td>
                            <td class="product-subtotal">
                                <?php echo wp_kses_post($cart->get_product_subtotal($_product, $cart_item['quantity'])); ?>
                            </td>
                            <td class="product-remove" style="text-align: right;">
                                <button class="swift-checkout-remove-item" data-item-key="<?php echo esc_attr($cart_item_key); ?>">×</button>
                            </td>
                        </tr>
            <?php
                    endif;
                endforeach;
            endif;
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="cart-subtotal-label"><?php esc_html_e('Subtotal', 'swift-checkout'); ?></td>
                <td colspan="2" class="cart-subtotal-value">
                    <?php
                    // Calculate subtotal for this product's items only
                    if ($specific_product_id > 0 && !empty($cart_items)) {
                        $subtotal = 0;
                        foreach ($cart_items as $cart_item) {
                            $_product = $cart_item['data'];
                            $subtotal += $_product->get_price() * $cart_item['quantity'];
                        }
                        echo wp_kses_post(wc_price($subtotal));
                    } else {
                        echo wp_kses_post($cart->get_cart_subtotal());
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="cart-shipping-label"><?php esc_html_e('Shipping', 'swift-checkout'); ?></td>
                <td colspan="2" class="cart-shipping-value">
                    <?php
                    if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) {
                        echo wp_kses_post(WC()->cart->get_cart_shipping_total());
                    } else {
                        echo esc_html__('Free', 'swift-checkout');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="cart-total-label"><?php esc_html_e('Total', 'swift-checkout'); ?></td>
                <td colspan="2" class="cart-total-value">
                    <?php
                    // Calculate total including shipping for specific product only
                    if ($specific_product_id > 0 && !empty($cart_items)) {
                        $subtotal = 0;
                        foreach ($cart_items as $cart_item) {
                            $_product = $cart_item['data'];
                            $subtotal += $_product->get_price() * $cart_item['quantity'];
                        }
                        // We can't easily calculate shipping for just this product
                        // so we'll display the subtotal as the total for specific product case
                        echo wp_kses_post(wc_price($subtotal));
                    } else {
                        echo wp_kses_post($cart->get_total());
                    }
                    ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>