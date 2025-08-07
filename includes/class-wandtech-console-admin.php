<?php
/**
 * Handles the admin area functionality for the WandTech Console.
 * Creates the admin menu, page, and enqueues necessary assets.
 *
 * @package    Wandtech_Console
 */

if (!defined('WPINC')) die;

class Wandtech_Console_Admin {

    /**
     * Constructor to hook into WordPress.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Adds the main "WandTech Console" menu page to the WordPress dashboard.
     */
    public function add_plugin_page() {
        add_menu_page(
            __('WandTech Console', 'wandtech-console'), // Page Title
            'WandTech',                                 // Menu Title (short)
            'manage_options',                           // Capability
            'wandtech-console',                         // Menu Slug
            array($this, 'create_admin_page'),          // Callback function to render the page
            'dashicons-layout',                         // Icon
            20                                          // Position
        );
    }

    /**
     * Enqueues admin-specific CSS and JavaScript.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_assets($hook) {
        // Only load assets on our specific admin page to improve performance.
        if ('toplevel_page_wandtech-console' !== $hook) {
            return;
        }

        // Enqueue the main admin stylesheet.
        wp_enqueue_style(
            'wandtech-console-admin-css',
            WANDTECH_CONSOLE_URL . 'assets/css/admin.css',
            [],
            WANDTECH_CONSOLE_VERSION
        );

        // Enqueue the main admin JavaScript file.
        wp_enqueue_script(
            'wandtech-console-admin-js',
            WANDTECH_CONSOLE_URL . 'assets/js/admin.js',
            ['jquery'],
            WANDTECH_CONSOLE_VERSION,
            true
        );
        
        // Pass localized data to the JavaScript file.
        wp_localize_script('wandtech-console-admin-js', 'wandtech_console_ajax', [
            'ajax_url'      => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('wandtech_console_module_nonce'),
            'generic_error' => __('An unexpected error occurred. Please try again.', 'wandtech-console'),
        ]);
    }

    /**
     * Renders the main admin page with its tabs and content.
     */
    public function create_admin_page() {
        // Get the module manager instance from the main console class.
        $modules_manager = Wandtech_Console::get_instance()->modules;
        $all_modules = $modules_manager->get_all_modules();
        $active_modules = $modules_manager->get_active_modules();
        ?>
        <div class="wrap wandtech-wrap">
            <h1><?php esc_html_e('WandTech Console', 'wandtech-console'); ?></h1>
            <p class="wandtech-subtitle"><?php esc_html_e('A modular console for adding powerful features to WordPress.', 'wandtech-console'); ?></p>

            <h2 class="nav-tab-wrapper">
                <a href="#dashboard" class="nav-tab nav-tab-active"><?php esc_html_e('Dashboard', 'wandtech-console'); ?></a>
                <a href="#modules" class="nav-tab"><?php esc_html_e('Modules', 'wandtech-console'); ?></a>
                <a href="#about" class="nav-tab"><?php esc_html_e('About', 'wandtech-console'); ?></a>
            </h2>

            <div id="dashboard" class="tab-content active">
                <h3><?php esc_html_e('Welcome to the Console', 'wandtech-console'); ?></h3>
                <p><?php esc_html_e('This is the central hub for all WandTech features. Use the tabs above to navigate.', 'wandtech-console'); ?></p>
                <p><?php esc_html_e('Activate or deactivate powerful tools for your site from the "Modules" tab.', 'wandtech-console'); ?></p>
            </div>

            <div id="modules" class="tab-content">
                <div class="module-cards">
                    <?php if (!empty($all_modules)) : ?>
                        <?php foreach ($all_modules as $slug => $module_data) :
                            $is_active = in_array($slug, $active_modules, true);
                            ?>
                            <div class="module-card<?php echo $is_active ? ' is-active' : ''; ?>">
                                <div class="module-card-header">
                                    <h3><?php echo esc_html($module_data['Name']); ?></h3>
                                    <label class="switch">
                                        <input type="checkbox" class="module-toggle" data-module="<?php echo esc_attr($slug); ?>" <?php checked($is_active); ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <div class="module-card-body">
                                    <p><?php echo esc_html($module_data['Description']); ?></p>
                                </div>
                                <div class="module-card-footer">
                                    <small><?php esc_html_e('Version:', 'wandtech-console'); ?> <?php echo esc_html($module_data['Version']); ?></small>
                                    <small><?php esc_html_e('Author:', 'wandtech-console'); ?> <?php echo esc_html($module_data['Author']); ?></small>
                                </div>
                                <div class="spinner-overlay"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p><?php esc_html_e('No modules found in the /modules directory. Add your first module to get started!', 'wandtech-console'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="about" class="tab-content">
                <h3><?php esc_html_e('About WandTech Console', 'wandtech-console'); ?></h3>
                <p><?php esc_html_e('This console was designed by the WandTech team to be a lightweight, powerful, and developer-friendly framework.', 'wandtech-console'); ?></p>
                <p><?php esc_html_e('We believe in modularity and performance, allowing you to enable only the features you need.', 'wandtech-console'); ?></p>
            </div>
        </div>
        <?php
    }
}