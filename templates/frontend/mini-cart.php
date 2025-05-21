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
?>

<div class="spc-mini-cart-contents">
    <table class="spc-cart-items">
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
            $cart_items = $cart->get_cart();
            if (!empty($cart_items)) :
                foreach ($cart_items as $cart_item_key => $cart_item) :
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0) :
            ?>
                        <tr class="spc-cart-item" data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                            <td class="product-name">
                                <?php echo esc_html($_product->get_name()); ?>
                            </td>
                            <td class="product-price">
                                <?php echo wp_kses_post($cart->get_product_price($_product)); ?>
                            </td>
                            <td class="product-quantity">
                                <div class="spc-quantity">
                                    <button class="spc-qty-minus" data-item-key="<?php echo esc_attr($cart_item_key); ?>">-</button>
                                    <input type="number" min="1" class="spc-qty-input"
                                        value="<?php echo esc_attr($cart_item['quantity']); ?>"
                                        data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                                    <button class="spc-qty-plus" data-item-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                </div>
                            </td>
                            <td class="product-subtotal">
                                <?php echo wp_kses_post($cart->get_product_subtotal($_product, $cart_item['quantity'])); ?>
                            </td>
                            <td class="product-remove" style="text-align: right;">
                                <button class="spc-remove-item" data-item-key="<?php echo esc_attr($cart_item_key); ?>">×</button>
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
                <td colspan="3" class="cart-subtotal-label"><?php esc_html_e('Total', 'swift-checkout'); ?></td>
                <td colspan="2" class="cart-subtotal-value"><?php echo wp_kses_post($cart->get_cart_subtotal()); ?></td>
            </tr>
        </tfoot>
    </table>
</div>