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

    protected static function get_wrapper_start($attributes = [], $builder = 'gutenberg', $object = null) {
        // Normalize class attribute (ensure array)
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = implode(' ', $attributes['class']);
        }

        switch ($builder) {
            case 'gutenberg':
                // Gutenberg expects just the array of attributes
                $wrapper_attributes = get_block_wrapper_attributes($attributes);
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

    protected static function get_wrapper_end() {
        return '</div>';
    }

    protected static function get_original_markup($attributes) {
        Utils::load_template('product-grid.php', $attributes);
    }

    /**
     * Get heading markup
     *
     * @param array $attributes Element attributes
     * @return string
     */
    public static function get_markup($builder, $attributes, $object = null) {
        $product_id = $attributes['productId'];
        $attributes['product_id'] = $product_id;

?>
        <div class="spc-container" data-builder="<?php echo esc_attr($builder); ?>">
            <?php Utils::load_template('product-grid.php', $attributes); ?>
            <div class="spc-mini-cart">
                <h2 class="spc-mini-cart-title"><?php esc_html_e('Your Cart', 'swift-checkout'); ?></h2>
                <?php Utils::load_template('mini-cart.php', $attributes); ?>
            </div>
            <?php Utils::load_template('checkout-form.php', $attributes); ?>
        </div>
<?php
    }
}
