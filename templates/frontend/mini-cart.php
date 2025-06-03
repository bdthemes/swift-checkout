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
    <div class="swift-checkout-cart-items">

            <?php
            if (!empty($cart_items)) :
                foreach ($cart_items as $cart_item_key => $cart_item) :
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0) :
            ?>
                        <div class="swift-checkout-cart-item" data-item-key="<?php echo esc_attr($cart_item_key); ?>">

                            <div class="swift-checkout-cart-item-inner">
                            <div class="product-image">
                                <?php echo $_product->get_image(); ?>
                            </div>
                            <div class="swift-checkout-content">
                                <div class="product-name">
                                    <?php echo esc_html($_product->get_name()); ?>
                                </div>
                                <div class="product-quantity">
                                   <div class="product-remove">
                                        <button class="swift-checkout-remove-item" data-item-key="<?php echo esc_attr($cart_item_key); ?>">×</button>
                                    </div>
                                    <div class="swift-checkout-quantity">
                                        <button class="swift-checkout-qty-minus" data-item-key="<?php echo esc_attr($cart_item_key); ?>">-</button>
                                        <input type="number" min="1" class="swift-checkout-qty-input"
                                            value="<?php echo esc_attr($cart_item['quantity']); ?>"
                                            data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                                        <button class="swift-checkout-qty-plus" data-item-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                    </div>
                                   
                                </div>
                            </div>
                            </div>

                            <div class="swift-checkout-cart-right">
                                <div class="swift-checkout-qty-price">
                                    <span class="swift-checkout-qty-input-text">
                                        <?php echo esc_attr($cart_item['quantity']); ?>
                                    </span>

                                    <span>x</span>
                                </div>
                                <span class="product-price">
                                    <?php echo wp_kses_post($cart->get_product_price($_product)); ?>
                                </span>
                            </div>
                           
                        </div>
            <?php
                    endif;
                endforeach;
            endif;
            ?>
            <div>
                <span class="cart-subtotal-label"><?php esc_html_e('Subtotal', 'swift-checkout'); ?></span>
                <span class="cart-subtotal-value">
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
                </span>
            </div>
            <div>
                <span class="cart-shipping-label"><?php esc_html_e('Shipping', 'swift-checkout'); ?></span>
                <span class="cart-shipping-value">
                    <?php
                    if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) {
                        echo wp_kses_post(WC()->cart->get_cart_shipping_total());
                    } else {
                        echo esc_html__('Free', 'swift-checkout');
                    }
                    ?>
                </span>
            </div>
            <div>
                <span class="cart-total-label"><?php esc_html_e('Total', 'swift-checkout'); ?></span>
                <span class="cart-total-value">
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
                </span>
            </div>
    </div>
</div>