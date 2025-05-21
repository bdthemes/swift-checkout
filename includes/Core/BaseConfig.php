<?php

/**
 * Base Configuration Class
 *
 * @package Gebkit
 * @subpackage Gebkit/includes/Core
 */

namespace SwiftCheckout\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Base Configuration Class
 *
 * Handles the basic configuration management for the plugin
 */
abstract class BaseConfig {
    /**
     * Option name prefix
     *
     * @var string
     */
    protected string $prefix = 'spc_';

    /**
     * Settings group
     *
     * @var string
     */
    protected string $settings_group = 'swift_checkout';

    /**
     * Get the option name for this configuration
     *
     * @return string
     */
    abstract protected function get_settings_name(): string;

    /**
     * Get the default configuration
     *
     * @return array
     */
    abstract protected function get_defaults(): array;

    /**
     * Private constructor to prevent direct instantiation
     */
    public function __construct() {
        // add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register the rest routes
     */
    public function register_rest_routes(): void {
        register_rest_route($this->settings_group, '/settings', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Register the settings
     */
    public function register_settings(): void {
        register_setting($this->settings_group, $this->get_option_name(), array(
            'type' => 'string',
            'show_in_rest' => true,
            'default' => json_encode($this->get_defaults()),
        ));

        $this->sync_settings();
    }

    /**
     * Get the full option name
     *
     * @return string
     */
    protected function get_option_name(): string {
        return $this->prefix . $this->get_settings_name();
    }

    /**
     * Get the settings
     *
     * @return array
     */
    public function get_settings(): array {
        $settings = get_option($this->get_option_name());
        if (empty($settings)) {
            return $this->get_defaults();
        }

        return json_decode($settings, true);
    }

    /**
     * Update the settings
     *
     * @param array $settings
     * @return bool
     */
    public function update_settings(array $settings): bool {
        $settings = json_encode($settings);
        return update_option($this->get_option_name(), $settings);
    }

    /**
     * Sync the settings with the defaults
     *
     * @return bool
     */
    public function sync_settings(): bool {
        $settings = $this->get_settings(); // user settings from DB
        $defaults = $this->get_defaults(); // reference/default structure

        // Add new keys from defaults
        $newKeys = array_diff_key($defaults, $settings);
        $removedKeys = array_diff_key($settings, $defaults);

        if (empty($newKeys) && empty($removedKeys)) {
            return true; // No change needed
        }

        // Add missing keys
        foreach ($newKeys as $key => $value) {
            $settings[$key] = $value;
        }

        // Remove deprecated keys
        foreach (array_keys($removedKeys) as $key) {
            unset($settings[$key]);
        }

        return $this->update_settings($settings);
    }
}
