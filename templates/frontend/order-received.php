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

<div class="spc-order-received">
    <h2 class="spc-order-received-title spc-common-title"><?php esc_html_e('Order received', 'swift-checkout'); ?></h2>

    <p class="spc-thank-you-message">
        <?php esc_html_e('Thank you. Your order has been received.', 'swift-checkout'); ?>
    </p>

    <ul class="spc-order-details">
        <li class="spc-order-number">
            <?php esc_html_e('Order number:', 'swift-checkout'); ?>
            <strong><?php echo esc_html($order->get_order_number()); ?></strong>
        </li>
        <li class="spc-order-date">
            <?php esc_html_e('Date:', 'swift-checkout'); ?>
            <strong><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></strong>
        </li>
        <li class="spc-order-total">
            <?php esc_html_e('Total:', 'swift-checkout'); ?>
            <strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong>
        </li>
        <?php if ($order->get_payment_method_title()) : ?>
            <li class="spc-order-payment-method">
                <?php esc_html_e('Payment method:', 'swift-checkout'); ?>
                <strong><?php echo esc_html($order->get_payment_method_title()); ?></strong>
            </li>
        <?php endif; ?>
    </ul>

    <div class="spc-order-details-items">
        <h3 class="spc-order-details-title spc-common-title"><?php esc_html_e('Order details', 'swift-checkout'); ?></h3>
        <div class="spc-order-table-wrapper">
            <table class="spc-order-table">
                <thead>
                    <tr>
                        <th class="product-name"><?php esc_html_e('Product', 'swift-checkout'); ?></th>
                        <th class="product-total"><?php esc_html_e('Total', 'swift-checkout'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($order->get_items() as $item_id => $item) {
                        $product = $item->get_product();
                        $purchase_note = $product ? $product->get_purchase_note() : '';
                    ?>
                        <tr class="spc-order-item">
                            <td class="product-name">
                                <?php echo wp_kses_post($item->get_name()); ?>
                                <strong class="product-quantity">× <?php echo esc_html($item->get_quantity()); ?></strong>
                            </td>
                            <td class="product-total">
                                <?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?>
                            </td>
                        </tr>
                        <?php if ($purchase_note) : ?>
                            <tr class="spc-purchase-note">
                                <td colspan="2"><?php echo wp_kses_post(wpautop(do_shortcode($purchase_note))); ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <?php
                    foreach ($order->get_order_item_totals() as $key => $total) {
                    ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($total['label']); ?></th>
                            <td><?php echo wp_kses_post($total['value']); ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tfoot>
            </table>
        </div>
    </div>

    <?php if ($order->get_customer_note()) : ?>
        <div class="spc-order-note">
            <h3><?php esc_html_e('Note:', 'swift-checkout'); ?></h3>
            <p><?php echo wp_kses_post(nl2br(wptexturize($order->get_customer_note()))); ?></p>
        </div>
    <?php endif; ?>

    <?php
    $billing_address = $order->get_formatted_billing_address();
    $shipping_address = $order->get_formatted_shipping_address();

    if ($billing_address || $shipping_address) : ?>
        <div class="spc-addresses">
            <?php if ($billing_address) : ?>
                <div class="spc-billing-address">
                    <h3 class="spc-billing-address-title spc-common-title"><?php esc_html_e('Billing address', 'swift-checkout'); ?></h3>
                    <address><?php echo wp_kses_post($billing_address); ?></address>
                </div>
            <?php endif; ?>

            <?php if ($shipping_address) : ?>
                <div class="spc-shipping-address">
                    <h3 class="spc-shipping-address-title spc-common-title"><?php esc_html_e('Shipping address', 'swift-checkout'); ?></h3>
                    <address><?php echo wp_kses_post($shipping_address); ?></address>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="spc-actions">
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button"><?php esc_html_e('Continue shopping', 'swift-checkout'); ?></a>
    </div>
</div>