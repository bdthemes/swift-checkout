<?php

/**
 * Common Heading Render Class
 *
 * @package Gebkit
 * @subpackage Gebkit/includes/Features/Renders
 */

namespace SwiftCheckout\Renders;

if (!defined('ABSPATH')) {
    exit;
}

class Heading {

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
        return sprintf(
            '<%1$s class="gebkit-heading">%2$s</%1$s>',
            $attributes['tag'] ?? 'h2',
            $attributes['content'] ?? ''
        );
    }
    /**
     * Get heading markup
     *
     * @param array $attributes Element attributes
     * @return string
     */
    public static function get_markup($builder, $attributes, $object = null) {
        return sprintf(
            '%s%s%s',
            self::get_wrapper_start(['class' => 'gebkit-heading-wrapper', 'data-builder' => $builder], $builder, $object),
            self::get_original_markup($attributes),
            self::get_wrapper_end()
        );
    }
}
