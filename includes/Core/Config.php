<?php

/**
 * Element Configuration Class
 *
 * @package Gebkit
 * @subpackage Gebkit/includes/Core
 */

namespace SwiftCheckout\Core;

use SwiftCheckout\Traits\Singleton;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Element Configuration Class
 *
 * Handles the configuration for elements/blocks/widgets
 */
class Config {
    use Singleton;

    public function __construct() {
        BuilderConfig::get_instance();
        ElementConfig::get_instance();
    }
}
