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
class AddToCart extends \Elementor\Widget_Base {
    /**
     * Get widget name
     */
    public function get_name() {
        return 'swift-checkout-add-to-cart';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__('Swift Checkout', 'swift-checkout');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-cart';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['swift-checkout'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['add to cart', 'cart', 'checkout'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'swift-checkout'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
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

        $this->add_control(
            'auto_add_to_cart',
            [
                'label' => esc_html__('Auto Add to Cart', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'swift-checkout'),
                'label_off' => esc_html__('No', 'swift-checkout'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__('Automatically add the selected product to cart when page loads', 'swift-checkout'),
            ]
        );

        // Button Alignment Control
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
                'separator' => 'before',
            ]
        );
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

        // Checkout Fields Section
        $this->start_controls_section(
            'checkout_fields_section',
            [
                'label' => esc_html__('Checkout Fields', 'swift-checkout'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_custom_fields',
            [
                'label' => esc_html__('Customize Checkout Fields', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'swift-checkout'),
                'label_off' => esc_html__('No', 'swift-checkout'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__('Enable to customize which checkout fields to display', 'swift-checkout'),
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'field_type',
            [
                'label' => esc_html__('Field Type', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'name',
                'options' => [
                    // Personal fields
                    'name' => esc_html__('Full Name', 'swift-checkout'),
                    'first_name' => esc_html__('First Name', 'swift-checkout'),
                    'last_name' => esc_html__('Last Name', 'swift-checkout'),
                    'email' => esc_html__('Email Address', 'swift-checkout'),
                    'phone' => esc_html__('Phone', 'swift-checkout'),
                    'company' => esc_html__('Company Name', 'swift-checkout'),

                    // Address fields
                    'address' => esc_html__('Full Address', 'swift-checkout'),
                    'address_1' => esc_html__('Address Line 1', 'swift-checkout'),
                    'address_2' => esc_html__('Address Line 2', 'swift-checkout'),
                    'city' => esc_html__('City', 'swift-checkout'),
                    'state' => esc_html__('State/County', 'swift-checkout'),
                    'postcode' => esc_html__('Postcode/ZIP', 'swift-checkout'),
                    'country' => esc_html__('Country', 'swift-checkout'),

                    // Order fields
                    'order_notes' => esc_html__('Order Notes', 'swift-checkout'),
                    // 'create_account' => esc_html__('Create Account', 'swift-checkout'),
                    'shipping_address' => esc_html__('Different Shipping Address', 'swift-checkout'),
                ],
            ]
        );

        $repeater->add_control(
            'field_required',
            [
                'label' => esc_html__('Required', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'swift-checkout'),
                'label_off' => esc_html__('No', 'swift-checkout'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $repeater->add_control(
            'field_label',
            [
                'label' => esc_html__('Field Label', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('Field Label', 'swift-checkout'),
            ]
        );

        $repeater->add_control(
            'field_placeholder',
            [
                'label' => esc_html__('Placeholder', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('Placeholder text', 'swift-checkout'),
            ]
        );

        $this->add_control(
            'checkout_fields',
            [
                'label' => esc_html__('Fields', 'swift-checkout'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'field_type' => 'first_name',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('First Name', 'swift-checkout'),
                        'field_placeholder' => '',
                    ],
                    [
                        'field_type' => 'last_name',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('Last Name', 'swift-checkout'),
                        'field_placeholder' => '',
                    ],
                    [
                        'field_type' => 'phone',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('Phone', 'swift-checkout'),
                        'field_placeholder' => '',
                    ],
                    [
                        'field_type' => 'email',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('Email Address', 'swift-checkout'),
                        'field_placeholder' => '',
                    ],
                    [
                        'field_type' => 'address_1',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('Street Address', 'swift-checkout'),
                        'field_placeholder' => '',
                    ],
                    [
                        'field_type' => 'city',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('City', 'swift-checkout'),
                        'field_placeholder' => '',
                    ],
                    [
                        'field_type' => 'postcode',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('ZIP / Postal Code', 'swift-checkout'),
                        'field_placeholder' => '',
                    ],
                    [
                        'field_type' => 'country',
                        'field_required' => 'yes',
                        'field_label' => esc_html__('Country', 'swift-checkout'),
                        'field_placeholder' => 'Select a country',
                    ],
                ],
                'title_field' => '{{{ field_label }}}',
                'condition' => [
                    'enable_custom_fields' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get list of WooCommerce products
     *
     * @return array
     */
    private function get_products_list() {
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
    protected function render() {
        $settings = $this->get_settings_for_display();
        echo wp_kses_post(\SwiftCheckout\Renders\AddToCart::get_markup('elementor', $settings, $this));
    }
}
