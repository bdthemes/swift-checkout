<?php

/**
 * Order received template
 *
 * @package swift_checkout
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$order = $args['order'];
?>

<div class="swift-checkout-order-received">
    <h2 class="swift-checkout-order-received-title swift-checkout-common-title"><?php esc_html_e('Order received', 'swift-checkout'); ?></h2>

    <p class="swift-checkout-thank-you-message">
        <?php esc_html_e('Thank you. Your order has been received.', 'swift-checkout'); ?>
    </p>

    <ul class="swift-checkout-order-details">
        <li class="swift-checkout-order-number">
            <?php esc_html_e('Order number:', 'swift-checkout'); ?>
            <strong><?php echo esc_html($order->get_order_number()); ?></strong>
        </li>
        <li class="swift-checkout-order-date">
            <?php esc_html_e('Date:', 'swift-checkout'); ?>
            <strong><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></strong>
        </li>
        <li class="swift-checkout-order-total">
            <?php esc_html_e('Total:', 'swift-checkout'); ?>
            <strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong>
        </li>
        <?php if ($order->get_payment_method_title()) : ?>
            <li class="swift-checkout-order-payment-method">
                <?php esc_html_e('Payment method:', 'swift-checkout'); ?>
                <strong><?php echo esc_html($order->get_payment_method_title()); ?></strong>
            </li>
        <?php endif; ?>
    </ul>


    <div class="swift-checkout-actions">
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button"><?php esc_html_e('Continue shopping', 'swift-checkout'); ?></a>
    </div>
</div>