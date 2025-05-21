<?php

/**
 * Product grid template
 *
 * @package swift_checkout
 */

namespace SwiftCheckout\Templates\Frontend;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="spc-add-to-cart">
    <button class="spc-add-to-cart-button">
        <?php esc_html_e('Add to Cart', 'swift-checkout'); ?>
    </button>
</div>