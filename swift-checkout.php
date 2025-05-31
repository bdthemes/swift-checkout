<?php

/**
 * Plugin Name:      Swift Checkout
 * Description:      Swift Checkout for WooCommerce.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      7.2
 * Author:            bdthemes
 * Author URI:        https://bdthemes.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       swift-checkout
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

if (! defined('SWIFT_CHECKOUT_VERSION')) {
    define('SWIFT_CHECKOUT_VERSION', '0.1.0');
}

// Load traits
require_once plugin_dir_path(__FILE__) . 'includes/Traits/Singleton.php';

use SwiftCheckout\Traits\Singleton;
use SwiftCheckout\Core\Register;
use SwiftCheckout\Classes\Dependencies;
use SwiftCheckout\Classes\Enqueue;
use SwiftCheckout\Classes\Ajax;
use SwiftCheckout\Classes\Admin;

/**
 * Main SwiftCheckout Class.
 * Implements the singleton pattern to ensure only one instance is running.
 */
final class SwiftCheckout {
    use Singleton;

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = SWIFT_CHECKOUT_VERSION;

    /**
     * Path to the plugin directory
     *
     * @var string
     */
    public $plugin_path;

    /**
     * URL to the plugin directory
     *
     * @var string
     */
    public $plugin_url;

    /**
     * Private constructor for singleton pattern.
     * Prevents the direct creation of an object from this class.
     */
    private function __construct() {
        add_action('init', [$this, 'init'], 10);
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Define plugin constants and paths
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        $this->define_constants();

        // Load after plugin activation
        register_activation_hook(__FILE__, [$this, 'activated_plugin']);

        // Load autoloader if exists
        if (file_exists($this->plugin_path . 'vendor/autoload.php')) {
            require_once $this->plugin_path . 'vendor/autoload.php';
        }

        // Register hooks
        $this->register_hooks();
        $this->setup();
    }

    /**
     * Register all hooks
     */
    private function register_hooks() {
        // Add init hook with priority 10
        add_filter('admin_body_class', [$this, 'add_body_classes']);
        add_filter('body_class', [$this, 'add_body_classes']);
    }

    /**
     * Setup plugin components after all plugins are loaded
     */
    public function setup() {
        // Check dependencies and initialize components
        if (Dependencies::check_dependencies()) {
            // Initialize registration handler
            Register::get_instance();
            Enqueue::get_instance();
            // Initialize AJAX handler
            Ajax::init();
        }

        // Load the admin class
        add_action('admin_init', function () {
            Admin::get_instance();
        });

        // Initialize variable prodct handler
        // require_once SWIFT_CHECKOUT_PLUGIN_DIR . 'includes/class-swift-checkout-variable-product.php';
        // new \SwiftCheckout\Swift_Checkout_Variable_Product();
    }

    /**
     * Defines plugin constants for easy access across the plugin.
     *
     * @return void
     */

    public function define_constants() {
        define('SWIFT_CHECKOUT_PLUGIN_NAME', 'Swift Checkout');
        define('SWIFT_CHECKOUT_PLUGIN_URL', trailingslashit($this->plugin_url));
        define('SWIFT_CHECKOUT_PLUGIN_DIR', trailingslashit($this->plugin_path));
        define('SWIFT_CHECKOUT_INCLUDES_DIR', SWIFT_CHECKOUT_PLUGIN_DIR . 'includes/');
        define('SWIFT_CHECKOUT_FEATURES_DIR', SWIFT_CHECKOUT_INCLUDES_DIR . 'Features/');
    }

    /**
     * Handles tasks to run upon plugin activation.
     *
     * @return void
     */
    public function activated_plugin() {
        // Update plugin version in the options table
        update_option('swift_checkout_version', SWIFT_CHECKOUT_VERSION);

        // Set installed time if it doesn't exist
        if (! get_option('swift_checkout_installed_time')) {
            add_option('swift_checkout_installed_time', time());
        }
    }

    /**
     * Add custom classes to body tags
     *
     * @param string|array $classes Current body classes
     * @return string|array Modified body classes
     */
    public function add_body_classes($classes) {
        $swift_checkout_classes = ['swift-checkout'];

        // Add frontend specific class
        if (! is_admin()) {
            $swift_checkout_classes[] = 'swift-checkout-frontend';
        }

        return is_array($classes)
            ? array_merge($classes, $swift_checkout_classes)
            : $classes . ' ' . implode(' ', $swift_checkout_classes);
    }
}

/**
 * Returns the main instance of SwiftCheckout.
 *
 * @return SwiftCheckout
 */
function swift_checkout() {
    return SwiftCheckout::get_instance();
}

// Initialize the plugin
swift_checkout();
