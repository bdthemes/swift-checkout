<?php

use SwiftCheckout\Renders\AddToCart;

error_log(print_r($attributes, true));

echo wp_kses_post(AddToCart::get_markup('gutenberg', $attributes, $block)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
