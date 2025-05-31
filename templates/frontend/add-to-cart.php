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
// Get the product
$product = wc_get_product($product_id);

// Check if product exists and is purchasable
if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
?>
    <div class="swift-checkout-no-products">
        <?php esc_html_e('Product not found or not available.', 'swift-checkout'); ?>
    </div>
<?php
    return;
}

// Auto-add to cart flag
$auto_add = isset($args['auto_add_to_cart']) && ($args['auto_add_to_cart'] === 'yes' || $args['auto_add_to_cart'] === true);
?>

<div class="swift-checkout-product-card <?php isset($args['cartButtonAlignment']) ? esc_attr($args['cartButtonAlignment']) : ''; ?>"
    data-product-id="<?php echo esc_attr($product->get_id()); ?>">
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
                        <?php
                        $variations = $product->get_available_variations();
                        $variation_attributes = array();

                        // Get all variation attributes
                        foreach ($variations as $variation) {
                            foreach ($variation['attributes'] as $attr_name => $attr_value) {
                                if (!isset($variation_attributes[$attr_name])) {
                                    $variation_attributes[$attr_name] = array();
                                }
                                if (!in_array($attr_value, $variation_attributes[$attr_name])) {
                                    $variation_attributes[$attr_name][] = $attr_value;
                                }
                            }
                        }

                        // Display select for current attribute
                        if (isset($variation_attributes['attribute_' . $attribute_name])):
                        ?>
                            <select name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                                id="<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                                data-attribute_name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                                class="swift-checkout-variation-select">
                                <option value=""><?php echo esc_html__('Choose an option', 'swift-checkout'); ?></option>
                                <?php
                                foreach ($variation_attributes['attribute_' . $attribute_name] as $value) {
                                    $label = $value;
                                    if ($attribute->is_taxonomy()) {
                                        $term = get_term_by('slug', $value, $attribute_name);
                                        if ($term) {
                                            $label = $term->name;
                                        }
                                    }
                                    echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                        <?php endif; ?>
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