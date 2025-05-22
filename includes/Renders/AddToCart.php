<?php

/**
 * Common Add to Cart Render Class
 *
 * @package SwiftCheckout
 * @subpackage SwiftCheckout/includes/Renders
 */

namespace SwiftCheckout\Renders;

use SwiftCheckout\Classes\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress global functions compatibility layer
 */
if (!function_exists('SwiftCheckout\Renders\esc_attr')) {
    function esc_attr($text)
    {
        return \esc_attr($text);
    }
}

if (!function_exists('SwiftCheckout\Renders\esc_html')) {
    function esc_html($text)
    {
        return \esc_html($text);
    }
}

if (!function_exists('SwiftCheckout\Renders\esc_html_e')) {
    function esc_html_e($text, $domain = 'default')
    {
        \esc_html_e($text, $domain);
    }
}

if (!function_exists('SwiftCheckout\Renders\esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        return \esc_html__($text, $domain);
    }
}

if (!function_exists('SwiftCheckout\Renders\sanitize_title')) {
    function sanitize_title($title, $fallback_title = '', $context = 'save')
    {
        return \sanitize_title($title, $fallback_title, $context);
    }
}

if (!function_exists('SwiftCheckout\Renders\wc_get_product')) {
    function wc_get_product($product = false)
    {
        return \wc_get_product($product);
    }
}

if (!function_exists('SwiftCheckout\Renders\wc_get_product_terms')) {
    function wc_get_product_terms($product_id, $taxonomy, $args = array())
    {
        return \wc_get_product_terms($product_id, $taxonomy, $args);
    }
}

if (!function_exists('SwiftCheckout\Renders\wc_attribute_label')) {
    function wc_attribute_label($name, $product = '')
    {
        return \wc_attribute_label($name, $product);
    }
}

if (!function_exists('SwiftCheckout\Renders\get_block_wrapper_attributes')) {
    function get_block_wrapper_attributes($attributes = array())
    {
        return \get_block_wrapper_attributes($attributes);
    }
}

if (!defined('SwiftCheckout\Renders\REST_REQUEST') && defined('REST_REQUEST')) {
    define('SwiftCheckout\Renders\REST_REQUEST', REST_REQUEST);
}

class AddToCart
{

    protected static function get_wrapper_start($attributes = [], $builder = 'gutenberg', $object = null)
    {
        // Normalize class attribute (ensure array)
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = implode(' ', $attributes['class']);
        }

        switch ($builder) {
            case 'gutenberg':
                // Gutenberg expects just the array of attributes
                $wrapper_attributes = \get_block_wrapper_attributes($attributes);
                break;

            case 'elementor':
                // Elementor expects associative array of attributes
                $object->add_render_attribute('_root', $attributes);
                $wrapper_attributes = $object->get_render_attribute_string('_root');
                break;

            default:
                $wrapper_attributes = '';
                break;
        }

        return sprintf('<div %s>', $wrapper_attributes);
    }

    protected static function get_wrapper_end()
    {
        return '</div>';
    }

    protected static function get_original_markup($attributes)
    {
        Utils::load_template('product-grid.php', $attributes);
    }

    /**
     * Get heading markup
     *
     * @param array $attributes Element attributes
     * @return string
     */
    public static function get_markup($builder, $attributes, $object = null)
    {
        // print_r($attributes);
        $attributes['product_id'] = $attributes['productId'];
        if ($builder === 'gutenberg' && defined('REST_REQUEST') && \REST_REQUEST && !empty($_GET['_locale'])) {
            self::get_editor_markup($attributes);
        } else { ?>
            <div class="spc-container <?php echo isset($attributes['stylePreset']) ? \esc_attr($attributes['stylePreset']) : ''; ?>"
                data-builder="<?php echo \esc_attr($builder); ?>">
                <?php Utils::load_template('product-grid.php', $attributes); ?>
                <div class="spc-mini-cart">
                    <h2 class="spc-mini-cart-title"><?php \esc_html_e('Your Cart', 'swift-checkout'); ?></h2>
                    <?php Utils::load_template('mini-cart.php', $attributes); ?>
                </div>
                <?php Utils::load_template('checkout-form.php', $attributes); ?>
            </div>
            <?php
        }
    }
    public static function get_editor_markup($attributes)
    {
        $product_id = $attributes['product_id'];
        $product = \wc_get_product($product_id);

        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return sprintf(
                '<div class="spc-no-products">%s</div>',
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
        <div class="spc-container <?php echo isset($attributes['stylePreset']) ? \esc_attr($attributes['stylePreset']) : ''; ?>"
            data-builder="gutenberg">
            <div class="spc-product-card" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
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
                                    <select name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
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
                <?php else: ?>
                    <button class="spc-add-to-cart" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                        <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="spc-mini-cart spc-visible">
                <h2 class="spc-mini-cart-title"><?php esc_html_e('Your Cart', 'swift-checkout'); ?></h2>
                <div class="spc-mini-cart-contents">
                    <table class="spc-cart-items">
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
                                    <tr class="spc-cart-item" data-item-key="<?php echo esc_attr($item_key); ?>">
                                        <td class="product-name">
                                            <?php echo esc_html($item['name']); ?>
                                            <?php if ($variation_attributes): ?>
                                                <div class="spc-variation-details"><?php echo esc_html($variation_attributes); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="product-price">
                                            <span class="woocommerce-Price-amount amount">
                                                <?php echo wc_price($item['price']); ?>
                                            </span>
                                        </td>
                                        <td class="product-quantity">
                                            <div class="spc-quantity">
                                                <button class="spc-qty-minus"
                                                    data-item-key="<?php echo esc_attr($item_key); ?>">–</button>
                                                <input type="number" min="1" class="spc-qty-input"
                                                    value="<?php echo esc_attr($item['quantity']); ?>"
                                                    data-item-key="<?php echo esc_attr($item_key); ?>">
                                                <button class="spc-qty-plus"
                                                    data-item-key="<?php echo esc_attr($item_key); ?>">+</button>
                                            </div>
                                        </td>
                                        <td class="product-subtotal">
                                            <span class="woocommerce-Price-amount amount">
                                                <?php echo wc_price($item['price'] * $item['quantity']); ?>
                                            </span>
                                        </td>
                                        <td class="product-remove" style="text-align: right;">
                                            <button class="spc-remove-item"
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
                                    echo wc_price($total);
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="spc-checkout-form spc-visible">
                <h2 class="spc-checkout-title"><?php \esc_html_e('Contact Information', 'swift-checkout'); ?></h2>
                <form id="spc-checkout-form" method="post">
                    <div class="spc-form-section">
                        <div class="spc-form-row spc-form-row-name">
                            <label for="spc-name" class="spc-form-label">
                                <?php \esc_html_e('Full Name', 'swift-checkout'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="spc-name" name="name" class="spc-form-input" required>
                        </div>

                        <div class="spc-input-group">
                            <div class="spc-form-row spc-form-row-phone">
                                <label for="spc-phone" class="spc-form-label">
                                    <?php \esc_html_e('Phone', 'swift-checkout'); ?> <span class="required">*</span>
                                </label>
                                <input type="tel" id="spc-phone" name="phone" class="spc-form-input" required>
                            </div>

                            <div class="spc-form-row spc-form-row-email">
                                <label for="spc-email" class="spc-form-label">
                                    <?php \esc_html_e('Email Address (Optional)', 'swift-checkout'); ?>
                                </label>
                                <input type="email" id="spc-email" name="email" class="spc-form-input">
                            </div>
                        </div>

                        <div class="spc-form-row spc-form-row-address">
                            <label for="spc-address" class="spc-form-label">
                                <?php \esc_html_e('Full Address', 'swift-checkout'); ?> <span class="required">*</span>
                            </label>
                            <textarea id="spc-address" name="address" class="spc-form-input" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="spc-form-section">
                        <div class="spc-form-row spc-form-row-submit">
                            <button type="submit" id="spc-submit-order" class="spc-submit-order" name="spc_submit_order">
                                <?php \esc_html_e('Place Order', 'swift-checkout'); ?>
                            </button>
                            <div class="spc-checkout-error"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}
