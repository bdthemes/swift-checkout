<?php

use SwiftCheckout\Renders\AddToCart;

echo wp_kses_post(AddToCart::get_markup('gutenberg', $attributes, $block)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
