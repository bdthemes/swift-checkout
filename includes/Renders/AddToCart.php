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
class AddToCart {
    /**
     * Get Add to Cart markup
     *
     * @param array $attributes Element attributes
     * @return string
     */
    public static function get_markup($builder, $attributes, $object = null)
    {
        // print_r($attributes);
        $attributes['product_id'] = $attributes['productId'];
        if ($builder === 'gutenberg' && defined('REST_REQUEST')) {
            Utils::load_template('block-editor-markup.php', $attributes);
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
}
