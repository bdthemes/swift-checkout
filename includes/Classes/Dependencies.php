<?php

/**
 * Dependencies Handler Class
 *
 * @package SwiftCheckout
 * @subpackage SwiftCheckout/includes/Classes
 */

namespace SwiftCheckout\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


use SwiftCheckout\Core\BuilderConfig;

class Dependencies {

    /**
     * Required WooCommerce version
     *
     * @var string
     */
    private static $required_wc_version = '6.0.0';

    /**
     * Required Elementor version
     *
     * @var string
     */
    private static $required_elementor_version = '3.7.0';

    /**
     * Core plugin details
     *
     * @var array
     */
    private static $plugins = [
        'woocommerce' => [
            'name' => 'WooCommerce',
            'slug' => 'woocommerce/woocommerce.php',
            'required' => true
        ],
        'elementor' => [
            'name' => 'Elementor',
            'slug' => 'elementor/elementor.php',
            'required' => false
        ],
    ];

    /**
     * Check if a plugin is active
     *
     * @param string $plugin_slug Plugin slug (plugin-dir/plugin-file.php)
     * @return bool
     */
    public static function is_plugin_active($plugin_slug) {
        return in_array($plugin_slug, \apply_filters('active_plugins', \get_option('active_plugins'))) ||
            (\is_multisite() && array_key_exists($plugin_slug, \get_site_option('active_sitewide_plugins', array())));
    }

    /**
     * Check if a plugin is installed
     *
     * @param string $plugin_slug Plugin slug (plugin-dir/plugin-file.php)
     * @return bool
     */
    public static function is_plugin_installed($plugin_slug) {
        if (!\function_exists('get_plugins')) {
            require_once \ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = \get_plugins();
        return isset($plugins[$plugin_slug]);
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    public static function woocommerce_active() {
        return self::is_plugin_active(self::$plugins['woocommerce']['slug']);
    }

    /**
     * Check if WooCommerce is installed
     *
     * @return bool
     */
    public static function woocommerce_installed() {
        return self::is_plugin_installed(self::$plugins['woocommerce']['slug']);
    }

    /**
     * Check if Elementor is active
     *
     * @return bool
     */
    public static function elementor_active() {
        return self::is_plugin_active(self::$plugins['elementor']['slug']);
    }

    /**
     * Check if Elementor is installed
     *
     * @return bool
     */
    public static function elementor_installed() {
        return self::is_plugin_installed(self::$plugins['elementor']['slug']);
    }




    /**
     * Check if required WooCommerce version is active
     *
     * @return bool
     */
    public static function has_required_wc_version() {
        if (!self::woocommerce_active()) {
            return false;
        }

        // Get WooCommerce version
        $wc_version = '';
        if (\function_exists('WC')) {
            $wc_version = \WC()->version;
        } elseif (\defined('WC_VERSION')) {
            $wc_version = \constant('WC_VERSION');
        }

        return !empty($wc_version) && \version_compare($wc_version, self::$required_wc_version, '>=');
    }

    /**
     * Check if required Elementor version is active
     *
     * @return bool
     */
    public static function has_required_elementor_version() {
        if (!self::elementor_active()) {
            return false;
        }

        // Get Elementor version
        $elementor_version = '';
        if (\defined('ELEMENTOR_VERSION')) {
            $elementor_version = \constant('ELEMENTOR_VERSION');
        }

        return !empty($elementor_version) && \version_compare($elementor_version, self::$required_elementor_version, '>=');
    }



    /**
     * Get active builder from settings
     *
     * @return array
     */
    private static function get_active_builders() {
        $active_builders = [];

        // Check if BuilderConfig class exists
        if (class_exists('\\SwiftCheckout\\Core\\BuilderConfig')) {
            $builders = BuilderConfig::get_instance()->get_settings();

            foreach ($builders as $slug => $builder) {
                if (!empty($builder['active'])) {
                    $active_builders[$slug] = $builder;
                }
            }
        }

        return $active_builders;
    }

    /**
     * Check core plugin dependencies and display notices
     *
     * @return bool
     */
    public static function check_dependencies() {
        $result = true;

        // WooCommerce is always required
        if (!self::woocommerce_installed()) {
            if (\is_admin()) {
                \add_action('admin_notices', [__CLASS__, 'display_woocommerce_install_notice']);
            }
            $result = false;
        } elseif (!self::woocommerce_active()) {
            if (\is_admin()) {
                \add_action('admin_notices', [__CLASS__, 'display_woocommerce_activate_notice']);
            }
            $result = false;
        } elseif (!self::has_required_wc_version()) {
            if (\is_admin()) {
                \add_action('admin_notices', [__CLASS__, 'display_woocommerce_version_notice']);
            }
            $result = false;
        }

        return $result;
    }

    /**
     * Display notice for WooCommerce installation
     */
    public static function display_woocommerce_install_notice() {
        if (!\current_user_can('install_plugins')) {
            return;
        }

        $install_url = \wp_nonce_url(
            \add_query_arg(
                array(
                    'action' => 'install-plugin',
                    'plugin' => 'woocommerce'
                ),
                \admin_url('update.php')
            ),
            'install-plugin_woocommerce'
        );
?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    /* translators: %s: WooCommerce plugin name */
                    esc_html__('Swift Checkout requires %s to be installed and activated.', 'swift-checkout'),
                    '<strong>' . \esc_html(self::$plugins['woocommerce']['name']) . '</strong>'
                ); ?>
                <a href="<?php echo \esc_url($install_url); ?>" class="button button-primary">
                    <?php \esc_html_e('Install Now', 'swift-checkout'); ?>
                </a>
            </p>
        </div>
    <?php
    }

    /**
     * Display notice for WooCommerce activation
     */
    public static function display_woocommerce_activate_notice() {
        if (!\current_user_can('activate_plugins')) {
            return;
        }

        $activate_url = \wp_nonce_url(
            \add_query_arg(
                array(
                    'action' => 'activate',
                    'plugin' => self::$plugins['woocommerce']['slug']
                ),
                \admin_url('plugins.php')
            ),
            'activate-plugin_' . self::$plugins['woocommerce']['slug']
        );
    ?>
        <div class="notice notice-error">
            <p>
                <?php printf(
                    /* translators: %s: WooCommerce plugin name */
                    esc_html__('Swift Checkout requires %s to be activated.', 'swift-checkout'),
                    '<strong>' . esc_html(self::$plugins['woocommerce']['name']) . '</strong>'
                ); ?>
                <a href="<?php echo \esc_url($activate_url); ?>" class="button button-primary">
                    <?php \esc_html_e('Activate Now', 'swift-checkout'); ?>
                </a>
            </p>
        </div>
    <?php
    }

    /**
     * Display notice for WooCommerce version requirement
     */
    public static function display_woocommerce_version_notice() {
    ?>
        <div class="notice notice-error">
            <p>
                <?php printf(
                    /* translators: %1$s: Swift Checkout plugin name, %2$s: Required WooCommerce version */
                    esc_html__('Swift Checkout requires %1$s version %2$s or higher.', 'swift-checkout'),
                    '<strong>' . esc_html(self::$plugins['woocommerce']['name']) . '</strong>',
                    esc_html(self::$required_wc_version)
                ); ?>
            </p>
        </div>
<?php
    }
}
