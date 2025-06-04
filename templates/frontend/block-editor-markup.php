<?php

/**
 * Single product template
 *
 * @package swift_checkout
 */

namespace SwiftCheckout\Templates\Frontend;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Get product ID from attributes
$product_id = !empty($args['product_id']) ? absint($args['product_id']) : 0;
$preset = !empty($args['stylePreset']) ? $args['stylePreset'] : 'simple';
// Get the product
$product = wc_get_product($product_id);

if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
    return sprintf(
        '<div class="swift-checkout-no-products">%s</div>',
        \esc_html__('Product not found or not available.', 'swift-checkout')
    );
}

// Add sample cart items for editor preview
$sample_cart_items = array(
    array(
        'product_id' => $product_id,
        'quantity' => 1,
        'price' => $product->get_price(),
        'name' => $product->get_name(),
        'image' => $product->get_image('thumbnail'),
        'is_variable' => $product->is_type('variable'),
        'attributes' => $product->is_type('variable') ? $product->get_attributes() : array(),
    )
);

// Add sample cart items to attributes for template
$attributes['sample_cart_items'] = $sample_cart_items;
?>
<div class="swift-checkout-container <?php echo esc_attr($preset); ?>" data-builder=" gutenberg">
    <div class="swift-checkout-product-card" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
        <?php if ($product->is_type('variable')): ?>
            <button class="swift-checkout-select-options" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                <?php esc_html_e('Select Options', 'swift-checkout'); ?>
            </button>
            <div class="swift-checkout-variations-wrapper" id="swift-checkout-variations-<?php echo esc_attr($product->get_id()); ?>"
                style="display: none;">
                <form class="swift-checkout-variations-form" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                    <?php
                    $attributes = $product->get_attributes();
                    foreach ($attributes as $attribute_name => $attribute):
                    ?>
                        <div class="swift-checkout-variation-row">
                            <label for="<?php echo esc_attr(sanitize_title($attribute_name)); ?>">
                                <?php echo esc_html(wc_attribute_label($attribute_name)); ?>
                            </label>
                            <select name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                                id="<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                                data-attribute_name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                                class="swift-checkout-variation-select">
                                <option value=""><?php echo esc_html__('Choose an option', 'swift-checkout'); ?></option>
                                <?php
                                if ($attribute->is_taxonomy()) {
                                    $terms = wc_get_product_terms($product->get_id(), $attribute_name, ['fields' => 'all']);
                                    foreach ($terms as $term) {
                                        echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                                    }
                                } else {
                                    $options = $attribute->get_options();
                                    foreach ($options as $option) {
                                        echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    <?php endforeach; ?>

                    <div class="swift-checkout-variation-price"></div>
                    <div class="swift-checkout-variation-stock"></div>

                    <div class="swift-checkout-variation-add-to-cart">
                        <button type="submit" class="swift-checkout-add-to-cart" disabled>
                            <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <button class="swift-checkout-add-to-cart" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="swift-checkout-mini-cart swift-checkout-visible">
        <h2 class="swift-checkout-mini-cart-title"><?php esc_html_e('Your Cart', 'swift-checkout'); ?></h2>
        <div class="swift-checkout-mini-cart-contents">
            <table class="swift-checkout-cart-items">
                <thead>
                    <tr>
                        <th class="product-name"><?php esc_html_e('Product', 'swift-checkout'); ?></th>
                        <th class="product-price"><?php esc_html_e('Price', 'swift-checkout'); ?></th>
                        <th class="product-quantity"><?php esc_html_e('Quantity', 'swift-checkout'); ?></th>
                        <th class="product-subtotal"><?php esc_html_e('Subtotal', 'swift-checkout'); ?></th>
                        <th class="product-remove" style="text-align: right;">
                            <?php esc_html_e('Remove', 'swift-checkout'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($attributes['sample_cart_items'])):
                        foreach ($attributes['sample_cart_items'] as $item):
                            $product = wc_get_product($item['product_id']);
                            if (!$product)
                                continue;

                            $item_key = md5($item['product_id'] . (isset($item['variation_id']) ? $item['variation_id'] : ''));
                            $variation_attributes = '';

                            if ($product->is_type('variation')) {
                                $variation_attributes = wc_get_formatted_variation($product->get_variation_attributes(), true);
                            }
                    ?>
                            <tr class="swift-checkout-cart-item" data-item-key="<?php echo esc_attr($item_key); ?>">
                                <td class="product-name">
                                    <?php echo esc_html($item['name']); ?>
                                    <?php if ($variation_attributes): ?>
                                        <div class="swift-checkout-variation-details"><?php echo esc_html($variation_attributes); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="product-price">
                                    <span class="woocommerce-Price-amount amount">
                                        <?php echo wp_kses_post(wc_price($item['price'])); ?>
                                    </span>
                                </td>
                                <td class="product-quantity">
                                    <div class="swift-checkout-quantity">
                                        <button class="swift-checkout-qty-minus"
                                            data-item-key="<?php echo esc_attr($item_key); ?>">–</button>
                                        <input type="number" min="1" class="swift-checkout-qty-input"
                                            value="<?php echo esc_attr($item['quantity']); ?>"
                                            data-item-key="<?php echo esc_attr($item_key); ?>">
                                        <button class="swift-checkout-qty-plus"
                                            data-item-key="<?php echo esc_attr($item_key); ?>">+</button>
                                    </div>
                                </td>
                                <td class="product-subtotal">
                                    <span class="woocommerce-Price-amount amount">
                                        <?php echo wp_kses_post(wc_price($item['price'] * $item['quantity'])); ?>
                                    </span>
                                </td>
                                <td class="product-remove" style="text-align: right;">
                                    <button class="swift-checkout-remove-item"
                                        data-item-key="<?php echo esc_attr($item_key); ?>">×</button>
                                </td>
                            </tr>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="cart-subtotal-label"><?php esc_html_e('Total', 'swift-checkout'); ?></td>
                        <td colspan="2" class="cart-subtotal-value">
                            <?php
                            $total = 0;
                            foreach ($attributes['sample_cart_items'] as $item) {
                                $total += $item['price'] * $item['quantity'];
                            }
                            echo wp_kses_post(wc_price($total));
                            ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="swift-checkout-checkout-form swift-checkout-visible">
        <h2 class="swift-checkout-checkout-title"><?php \esc_html_e('Contact Information', 'swift-checkout'); ?></h2>
        <form id="swift-checkout-checkout-form" method="post">
            <div class="swift-checkout-form-section">
                <?php
                // Use custom fields if enabled and available, otherwise use default fields
                $use_custom_fields = isset($args['enable_custom_fields']) && $args['enable_custom_fields'] === 'yes' && !empty($args['checkout_fields']);
                $fields_to_display = $use_custom_fields ? $args['checkout_fields'] : array(
                    array(
                        'field_type' => 'name',
                        'field_required' => true,
                        'field_label' => 'Full Name',
                        'field_placeholder' => ''
                    ),
                    array(
                        'field_type' => 'phone',
                        'field_required' => true,
                        'field_label' => 'Phone',
                        'field_placeholder' => ''
                    ),
                    array(
                        'field_type' => 'email',
                        'field_required' => false,
                        'field_label' => 'Email Address (Optional)',
                        'field_placeholder' => ''
                    ),
                    array(
                        'field_type' => 'address',
                        'field_required' => true,
                        'field_label' => 'Full Address',
                        'field_placeholder' => ''
                    )
                );

                // Track if we have phone and email for putting them in the same row
                $has_phone = false;
                $has_email = false;
                $has_first_name = false;
                $has_last_name = false;

                // First pass to identify phone and email for grouping
                foreach ($fields_to_display as $field) {
                    if (isset($field['field_type'])) {
                        if ($field['field_type'] === 'phone') {
                            $has_phone = true;
                        }
                        if ($field['field_type'] === 'email') {
                            $has_email = true;
                        }
                        if ($field['field_type'] === 'first_name') {
                            $has_first_name = true;
                        }
                        if ($field['field_type'] === 'last_name') {
                            $has_last_name = true;
                        }
                    }
                }

                // Should we group phone and email?
                $group_phone_email = $has_phone && $has_email;
                $group_name_fields = $has_first_name && $has_last_name;
                $in_phone_email_group = false;
                $in_name_group = false;

                foreach ($fields_to_display as $field) :
                    if (empty($field['field_type'])) {
                        continue;
                    }

                    $type = $field['field_type'];
                    $required = isset($field['field_required']) && ($field['field_required'] === 'yes' || $field['field_required'] === true);
                    $label = !empty($field['field_label']) ? $field['field_label'] : '';
                    $placeholder = isset($field['field_placeholder']) ? $field['field_placeholder'] : ' ';

                    // Start phone/email group if needed
                    if ($group_phone_email && ($type === 'phone') && !$in_phone_email_group) {
                        echo '<div class="swift-checkout-input-group">';
                        $in_phone_email_group = true;
                    }

                    // Start first_name/last_name group if needed
                    if ($group_name_fields && ($type === 'first_name') && !$in_name_group) {
                        echo '<div class="swift-checkout-input-group">';
                        $in_name_group = true;
                    }

                    // Determine input type based on field_type
                    $input_type = 'text';
                    if ($type === 'email') {
                        $input_type = 'email';
                    } elseif ($type === 'phone') {
                        $input_type = 'tel';
                    } elseif ($type === 'postcode') {
                        $input_type = 'text';
                    }

                    // Render the field
                    if ($type === 'address' || $type === 'order_notes') {
                        // These fields use textarea
                ?>
                        <div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?>">
                            <textarea id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-form-input" rows="3" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo esc_attr($placeholder); ?>"></textarea>
                            <label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-form-label">
                                <?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
                            </label>
                        </div>
                    <?php
                    } elseif ($type === 'country') {
                        // Country dropdown
                    ?>
                        <div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?>">
                            <select id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-form-input" <?php echo $required ? 'required' : ''; ?>>
                                <option value=""><?php echo esc_html($placeholder ? $placeholder : 'Select a country'); ?></option>
                                <?php
                                // Get countries from WooCommerce if available
                                $countries = array();
                                if (function_exists('WC')) {
                                    $wc = WC();
                                    if (isset($wc->countries) && is_object($wc->countries)) {
                                        $countries = $wc->countries->get_countries();
                                    }
                                }

                                // Display countries
                                foreach ($countries as $code => $country_name) {
                                    echo '<option value="' . esc_attr($code) . '">' . esc_html($country_name) . '</option>';
                                }
                                ?>
                            </select>
                            <label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-form-label">
                                <?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
                            </label>
                        </div>
                    <?php
                    } elseif ($type === 'state') {
                        // State/County dropdown
                    ?>
                        <div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?>">
                            <select id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-form-input" <?php echo $required ? 'required' : ''; ?>>
                                <option value=""><?php echo esc_html($placeholder ? $placeholder : 'Select a state'); ?></option>
                                <?php
                                // Get states from WooCommerce if available
                                $states = array();
                                if (function_exists('WC')) {
                                    $wc = WC();
                                    if (isset($wc->countries) && is_object($wc->countries)) {
                                        $base_country = $wc->countries->get_base_country();
                                        $states = $wc->countries->get_states($base_country);
                                    }
                                }

                                // Display states
                                foreach ($states as $code => $state_name) {
                                    echo '<option value="' . esc_attr($code) . '">' . esc_html($state_name) . '</option>';
                                }
                                ?>
                            </select>
                            <label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-form-label">
                                <?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
                            </label>
                        </div>
                    <?php
                    } elseif ($type === 'create_account') {
                        // Checkbox fields
                    ?>
                        <div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?> swift-checkout-checkbox-row">
                            <label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-checkbox-label">
                                <input type="checkbox" id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-checkbox-input" <?php echo $required ? 'required' : ''; ?>>
                                <?php echo esc_html($label ? $label : 'Create an account?'); ?>
                            </label>
                        </div>
                    <?php
                    } elseif ($type === 'shipping_address') {
                        // Checkbox for different shipping address
                    ?>
                        <div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?> swift-checkout-checkbox-row">
                            <label for="swift-checkout-shipping_address" class="swift-checkout-checkbox-label">
                                <input type="checkbox" id="swift-checkout-shipping_address" name="shipping_address" class="swift-checkout-checkbox-input" <?php echo $required ? 'required' : ''; ?>>
                                <?php echo esc_html($label ? $label : 'Ship to a different address?'); ?>
                            </label>
                        </div>

                        <!-- Shipping address form - hidden by default -->
                        <div id="swift-checkout-shipping-address-fields" class="swift-checkout-shipping-address-fields" style="display: none;">
                            <h3 class="swift-checkout-shipping-title">Shipping Address</h3>

                            <!-- First Name and Last Name in a group -->
                            <div class="swift-checkout-input-group">
                                <!-- First Name -->
                                <div class="swift-checkout-form-row">
                                    <input type="text" id="swift-checkout-shipping_first_name" name="shipping_first_name" class="swift-checkout-form-input" placeholder=" ">
                                    <label for="swift-checkout-shipping_first_name" class="swift-checkout-form-label">First Name</label>
                                </div>

                                <!-- Last Name -->
                                <div class="swift-checkout-form-row">
                                    <input type="text" id="swift-checkout-shipping_last_name" name="shipping_last_name" class="swift-checkout-form-input" placeholder=" ">
                                    <label for="swift-checkout-shipping_last_name" class="swift-checkout-form-label">Last Name</label>
                                </div>
                            </div>

                            <!-- Address Line 1 -->
                            <div class="swift-checkout-form-row">
                                <input type="text" id="swift-checkout-shipping_address_1" name="shipping_address_1" class="swift-checkout-form-input" placeholder=" ">
                                <label for="swift-checkout-shipping_address_1" class="swift-checkout-form-label">Street Address</label>
                            </div>

                            <!-- Address Line 2 -->
                            <div class="swift-checkout-form-row">
                                <input type="text" id="swift-checkout-shipping_address_2" name="shipping_address_2" class="swift-checkout-form-input" placeholder=" ">
                                <label for="swift-checkout-shipping_address_2" class="swift-checkout-form-label">Apartment, suite, etc. (Optional)</label>
                            </div>

                            <!-- City -->
                            <div class="swift-checkout-form-row">
                                <input type="text" id="swift-checkout-shipping_city" name="shipping_city" class="swift-checkout-form-input" placeholder=" ">
                                <label for="swift-checkout-shipping_city" class="swift-checkout-form-label">City</label>
                            </div>

                            <!-- State -->
                            <div class="swift-checkout-form-row">
                                <select id="swift-checkout-shipping_state" name="shipping_state" class="swift-checkout-form-input">
                                    <option value="">Select a state</option>
                                    <?php
                                    // Get states from WooCommerce if available
                                    $states = array();
                                    if (function_exists('WC')) {
                                        $wc = WC();
                                        if (isset($wc->countries) && is_object($wc->countries)) {
                                            $base_country = $wc->countries->get_base_country();
                                            $states = $wc->countries->get_states($base_country);
                                        }
                                    }

                                    // Display states
                                    foreach ($states as $code => $state_name) {
                                        echo '<option value="' . esc_attr($code) . '">' . esc_html($state_name) . '</option>';
                                    }
                                    ?>
                                </select>
                                <label for="swift-checkout-shipping_state" class="swift-checkout-form-label">State/County</label>
                            </div>

                            <!-- Postcode -->
                            <div class="swift-checkout-form-row">
                                <input type="text" id="swift-checkout-shipping_postcode" name="shipping_postcode" class="swift-checkout-form-input" placeholder=" ">
                                <label for="swift-checkout-shipping_postcode" class="swift-checkout-form-label">ZIP / Postal Code</label>
                            </div>

                            <!-- Country -->
                            <div class="swift-checkout-form-row">
                                <select id="swift-checkout-shipping_country" name="shipping_country" class="swift-checkout-form-input">
                                    <option value="">Select a country</option>
                                    <?php
                                    // Get countries from WooCommerce if available
                                    $countries = array();
                                    if (function_exists('WC')) {
                                        $wc = WC();
                                        if (isset($wc->countries) && is_object($wc->countries)) {
                                            $countries = $wc->countries->get_countries();
                                        }
                                    }

                                    // Display countries
                                    foreach ($countries as $code => $country_name) {
                                        echo '<option value="' . esc_attr($code) . '">' . esc_html($country_name) . '</option>';
                                    }
                                    ?>
                                </select>
                                <label for="swift-checkout-shipping_country" class="swift-checkout-form-label">Country</label>
                            </div>
                        </div>
                    <?php
                    } else {
                        // Regular input field
                    ?>
                        <div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?>">
                            <input type="<?php echo esc_attr($input_type); ?>" id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-form-input" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo esc_attr($placeholder); ?>">
                            <label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-form-label">
                                <?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
                            </label>
                        </div>
                <?php
                    }

                    // Close phone/email group if needed
                    if ($group_phone_email && $in_phone_email_group && $type === 'email') {
                        echo '</div>';
                        $in_phone_email_group = false;
                    }

                    // Close first_name/last_name group if needed
                    if ($group_name_fields && $in_name_group && $type === 'last_name') {
                        echo '</div>';
                        $in_name_group = false;
                    }
                endforeach;

                // Close groups if still open
                if ($in_phone_email_group) {
                    echo '</div>';
                }
                if ($in_name_group) {
                    echo '</div>';
                }
                ?>
            </div>

            <div class="swift-checkout-form-section">
                <?php
                // Check for shipping_method in fields array
                $shipping_method_enabled = false;
                foreach ($fields_to_display as $field) {
                    if (isset($field['field_type']) && $field['field_type'] === 'shipping_method') {
                        $shipping_method_enabled = true;
                        break;
                    }
                }

                if ($shipping_method_enabled) {
                ?>
                    <h3 class="swift-checkout-shipping-methods-title">Shipping Methods</h3>
                    <div id="swift-checkout-shipping-methods" class="swift-checkout-shipping-methods">
                        <div class="swift-checkout-shipping-method"><label><input type="radio" name="shipping_method" value="flat_rate:1" class="swift-checkout-shipping-method-input">Inside Dhaka – <span class="woocommerce-Price-amount amount"><bdi>60.00<span class="woocommerce-Price-currencySymbol">৳&nbsp;</span></bdi></span></label></div>
                        <div class="swift-checkout-shipping-method"><label><input type="radio" name="shipping_method" value="flat_rate:2" class="swift-checkout-shipping-method-input">Outside of Dhaka – <span class="woocommerce-Price-amount amount"><bdi>120.00<span class="woocommerce-Price-currencySymbol">৳&nbsp;</span></bdi></span></label></div>
                    </div>
                <?php
                }
                ?>
                <div class="swift-checkout-form-row swift-checkout-form-row-submit">
                    <button type="submit" id="swift-checkout-submit-order" class="swift-checkout-submit-order" name="swift_checkout_submit_order">
                        <?php \esc_html_e('Place Order', 'swift-checkout'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>