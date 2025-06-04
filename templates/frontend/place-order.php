<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Check if cart has items to determine initial visibility
$has_items = function_exists('WC') && isset(WC()->cart) && !WC()->cart->is_empty();
$visible_class = $has_items ? 'swift-checkout-visible' : '';
?>

<div class="swift-checkout-place-order-wrapper <?php echo esc_attr($visible_class); ?>">
    <button type="button" id="swift-checkout-submit-order" class="swift-checkout-submit-order" name="swift_checkout_submit_order">
        <?php esc_html_e('Place Order', 'swift-checkout'); ?>
    </button>
</div>