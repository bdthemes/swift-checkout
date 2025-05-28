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
    <div class="spc-no-products">
        <?php esc_html_e('Product not found or not available.', 'swift-checkout'); ?>
    </div>
<?php
    return;
}
?>

<div class="spc-product-card <?php isset($args['cartButtonAlignment']) ? esc_attr($args['cartButtonAlignment']) : ''; ?>"
    data-product-id="<?php echo esc_attr($product->get_id()); ?>">
    <?php if ($product->is_type('variable')): ?>
        <button class="spc-select-options" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <?php esc_html_e('Select Options', 'swift-checkout'); ?>
        </button>
        <div class="spc-variations-wrapper" id="spc-variations-<?php echo esc_attr($product->get_id()); ?>"
            style="display: none;">
            <form class="spc-variations-form" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                <?php
                $attributes = $product->get_attributes();
                foreach ($attributes as $attribute_name => $attribute):
                ?>
                    <div class="spc-variation-row">
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
                                class="spc-variation-select">
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

                <div class="spc-variation-price"></div>
                <div class="spc-variation-stock"></div>

                <div class="spc-variation-add-to-cart">
                    <button type="submit" class="spc-add-to-cart" disabled>
                        <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <button class="spc-add-to-cart" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
        </button>
    <?php endif; ?>
</div>