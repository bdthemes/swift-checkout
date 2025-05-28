<?php

use SwiftCheckout\Renders\AddToCart;


$markup = AddToCart::get_markup('gutenberg', $attributes, $block);
if ($markup !== null) {
    echo wp_kses_post($markup); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
    echo ''; // Return empty string if markup is null
}
