<?php

/**
 * Single product template
 *
 * @package swift_checkout
 */

namespace SwiftCheckout\Templates\Frontend;

use SwiftCheckout\Classes\Utils;

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

<div class="spc-product-card" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
    <?php if ($product->is_type('variable')) : ?>
        <button class="spc-select-options" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <?php esc_html_e('Select Options', 'swift-checkout'); ?>
        </button>
        <div class="spc-variations-wrapper" id="spc-variations-<?php echo esc_attr($product->get_id()); ?>" style="display: none;">
            <form class="spc-variations-form" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                <?php
                $attributes = $product->get_attributes();
                foreach ($attributes as $attribute_name => $attribute) :
                ?>
                    <div class="spc-variation-row">
                        <label for="<?php echo esc_attr(sanitize_title($attribute_name)); ?>">
                            <?php echo esc_html(wc_attribute_label($attribute_name)); ?>
                        </label>
                        <select
                            name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                            id="<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                            data-attribute_name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                            class="spc-variation-select">
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

                <div class="spc-variation-price"></div>
                <div class="spc-variation-stock"></div>

                <div class="spc-variation-add-to-cart">
                    <button type="submit" class="spc-add-to-cart" disabled>
                        <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
                    </button>
                </div>
            </form>
        </div>
    <?php else : ?>
        <button class="spc-add-to-cart" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
        </button>
    <?php endif; ?>
</div>