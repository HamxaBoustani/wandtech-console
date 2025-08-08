<?php
/**
 * Module Name:     Dashboard Welcome Widget
 * Description:     Adds a personalized welcome widget to the WordPress dashboard.
 * Version:         1.0.0
 * Author:          Hamxa
 * Scope:           admin  
 * Text Domain:     welcome-widget
 * Domain Path:     /languages
 */

// Prevent direct access to the file.
if (!defined('ABSPATH')) exit;

// Use a constant to ensure this file is loaded only once.
if (defined('WANDTECH_WELCOME_WIDGET_LOADED')) {
    return;
}
define('WANDTECH_WELCOME_WIDGET_LOADED', true);

class Wandtech_Welcome_Widget_Module {

    /**
     * Constructor. Registers all necessary hooks.
     */
    public function __construct() {
        // Load the module-specific text domain for translation.
        add_action('init', array($this, 'load_module_textdomain'));

        // Register the dashboard widget. This hook only runs in the admin area.
        add_action('wp_dashboard_setup', array($this, 'register_widget'));
    }

    /**
     * Loads the text domain for this specific module, making it translatable.
     */
    public function load_module_textdomain() {
        load_plugin_textdomain(
            'welcome-widget',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Registers the dashboard widget with WordPress.
     */
    public function register_widget() {
        // Ensure the user has the capability to view the dashboard.
        if (!current_user_can('read')) {
            return;
        }

        wp_add_dashboard_widget(
            'wandtech_welcome_widget',                                  // Widget slug.
            __('Personal Welcome', 'welcome-widget'),                   // Widget title.
            array($this, 'render_widget_content')                       // Callback to display content.
        );
    }

    /**
     * Renders the HTML content for the dashboard widget.
     */
    public function render_widget_content() {
        $current_user = wp_get_current_user();
        
        if (!$current_user->exists()) {
            echo '<p>' . esc_html__('Welcome, Guest!', 'welcome-widget') . '</p>';
            return;
        }

        // Get the user's display name.
        $display_name = $current_user->display_name;

        // Prepare the welcome message using printf for better translation flexibility.
        printf(
            '<p>' . esc_html__('Hello, %s! Welcome back to your dashboard.', 'welcome-widget') . '</p>',
            '<strong>' . esc_html($display_name) . '</strong>'
        );

        // Display the user's role.
        $user_roles = $current_user->roles;
        $role_name = !empty($user_roles) ? ucfirst($user_roles[0]) : __('No role', 'welcome-widget');
        
        printf(
            '<p>' . esc_html__('Your current role is: %s.', 'welcome-widget') . '</p>',
            '<em>' . esc_html($role_name) . '</em>'
        );

        echo '<p>' . esc_html__('Have a great day!', 'welcome-widget') . '</p>';
    }
}

// Instantiate the class to get the module running.
new Wandtech_Welcome_Widget_Module();