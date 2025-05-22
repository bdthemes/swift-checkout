<?php

/**
 * Elementor Add to Cart Widget
 *
 * @package SwiftCheckout
 * @subpackage SwiftCheckout/includes/Features/Elementor
 */

namespace SwiftCheckout\Features\Elementor\AddToCart;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Add to Cart Widget Class
 */
class AddToCart extends \Elementor\Widget_Base
{
    /**
     * Get widget name
     */
    public function get_name()
    {
        return 'swift-checkout-add-to-cart';
    }

    /**
     * Get widget title
     */
    public function get_title()
    {
        return esc_html__('Add to Cart', 'swift-checkout');
    }

    /**
     * Get widget icon
     */
    public function get_icon()
    {
        return 'eicon-cart';
    }

    /**
     * Get widget categories
     */
    public function get_categories()
    {
        return ['swift-checkout'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords()
    {
        return ['add to cart', 'cart', 'checkout'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'swift-checkout'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Style Selection Control
        $this->add_control(
            'stylePreset',
            [
                'label' => esc_html__('Preset', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'simple',
                'options' => [
                    'simple' => esc_html__('Simple', 'swift-checkout'),
                    'modern' => esc_html__('Modern', 'swift-checkout'),
                ],
                'separator' => 'after',
            ]
        );

        // Product Selection Control
        $this->add_control(
            'productId',
            [
                'label' => esc_html__('Product', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_products_list(),
                'default' => '',
                'label_block' => true,
                'description' => esc_html__('Select a product to display', 'swift-checkout'),
            ]
        );

        // Button Alignment Control

        $this->add_control(
            'cartButtonAlignment',
            [
                'label' => esc_html__('Button Alignment', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'button-left' => [
                        'title' => esc_html__('Left', 'swift-checkout'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'button-center' => [
                        'title' => esc_html__('Center', 'swift-checkout'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'button-right' => [
                        'title' => esc_html__('Right', 'swift-checkout'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'button-justify' => [
                        'title' => esc_html__('Justified', 'swift-checkout'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => 'button-left',
                'toggle' => true,
            ]
        );


        $this->end_controls_section();
    }

    /**
     * Get list of WooCommerce products
     *
     * @return array
     */
    private function get_products_list()
    {
        $products = [];

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                array(
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '='
                )
            )
        );

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);

                // Double check stock status
                if ($product && $product->is_in_stock()) {
                    $products[$product_id] = get_the_title();
                }
            }
        }
        wp_reset_postdata();

        return $products;
    }

    /**
     * Render widget output
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        echo wp_kses_post(\SwiftCheckout\Renders\AddToCart::get_markup('elementor', $settings, $this));
    }
}
