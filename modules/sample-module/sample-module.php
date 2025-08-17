<?php
/**
 * Module Name:     Sample Module
 * Description:     A perfect boilerplate and example of how to build a high-quality, translatable module for WandTech Console.
 * Version:         1.3.0 - Redesigned to be an informative mini-dashboard.
 * Author:          WandTech Team
 * Scope:           admin
 * Text Domain:     wandtech-sample-module
 * Domain Path:     /languages/
 */

if (!defined('ABSPATH')) exit;

if (defined('WANDTECH_SAMPLE_MODULE_LOADED')) {
    return;
}
define('WANDTECH_SAMPLE_MODULE_LOADED', true);

class Wandtech_Sample_Module {

    /**
     * Constructor. Registers all necessary hooks.
     */
    public function __construct() {
        add_action('init', [$this, 'load_module_textdomain']);
        add_action('wp_dashboard_setup', [$this, 'register_widget']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Loads the text domain for this specific module, making it translatable.
     */
    public function load_module_textdomain() {
        load_plugin_textdomain(
            'wandtech-sample-module',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Enqueues the CSS file for the dashboard widget.
     * We no longer need a separate JS file for this version.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_assets(string $hook) {
        if ('index.php' !== $hook) {
            return;
        }
        wp_enqueue_style(
            'wandtech-sample-module-css',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            [],
            '1.3.0'
        );
    }

    /**
     * Registers the dashboard widget with WordPress.
     */
    public function register_widget() {
        if (!current_user_can('read')) {
            return;
        }
        wp_add_dashboard_widget(
            'wandtech_console_sample_widget',
            __('WandTech Console Overview', 'wandtech-sample-module'),
            [$this, 'render_widget_content']
        );
    }

    /**
     * Renders the HTML content for the dashboard widget.
     */
    public function render_widget_content() {
        // --- Data Fetching ---
        $modules_manager = Wandtech_Console::get_instance()->modules;
        $all_modules = $modules_manager->get_all_modules();
        $active_modules = $modules_manager->get_active_modules();
        
        $total_count = count($all_modules);
        $active_count = count($active_modules);
        $inactive_count = $total_count - $active_count;

        $admin_only_active_modules = 0;
        foreach ($active_modules as $slug) {
            if (isset($all_modules[$slug]['Scope']) && $all_modules[$slug]['Scope'] === 'admin') {
                $admin_only_active_modules++;
            }
        }
        ?>
        <div class="wt-sample-module-container">

            <!-- 1. Stats Grid - Mirrored from the main dashboard -->
            <div class="wt-sample-module-stats-grid">
                <div class="stat-card">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($total_count); ?></span>
                        <span class="stat-label"><?php esc_html_e('Total Modules', 'wandtech-sample-module'); ?></span>
                    </div>
                </div>
                <div class="stat-card active">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($active_count); ?></span>
                        <span class="stat-label"><?php esc_html_e('Active', 'wandtech-sample-module'); ?></span>
                    </div>
                </div>
                <div class="stat-card inactive">
                    <span class="dashicons dashicons-dismiss"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($inactive_count); ?></span>
                        <span class="stat-label"><?php esc_html_e('Inactive', 'wandtech-sample-module'); ?></span>
                    </div>
                </div>
                <div class="stat-card performance">
                    <span class="dashicons dashicons-performance"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($admin_only_active_modules); ?></span>
                        <span class="stat-label"><?php esc_html_e('Optimized', 'wandtech-sample-module'); ?></span>
                    </div>
                </div>
            </div>

            <!-- 2. Mission & "Why WandTech?" Section -->
            <div class="wt-sample-module-mission">
                <h4><?php esc_html_e('The Mission: Power Meets Performance', 'wandtech-sample-module'); ?></h4>
                <p><?php esc_html_e('WandTech Console is designed to be the last optimization and feature plugin you\'ll ever need, providing maximum functionality with minimal performance impact.', 'wandtech-sample-module'); ?></p>
                
                <ul class="why-wandtech-list">
                    <li>
                        <span class="dashicons dashicons-shield-alt"></span>
                        <div>
                            <strong><?php esc_html_e('Reduce Bloat:', 'wandtech-sample-module'); ?></strong>
                            <?php esc_html_e('Replace dozens of single-feature plugins with one lightweight, modular core.', 'wandtech-sample-module'); ?>
                        </div>
                    </li>
                    <li>
                        <span class="dashicons dashicons-superhero"></span>
                        <div>
                            <strong><?php esc_html_e('Boost Speed:', 'wandtech-sample-module'); ?></strong>
                            <?php esc_html_e('Only the code you use is loaded. Admin modules have zero impact on your site\'s frontend speed.', 'wandtech-sample-module'); ?>
                        </div>
                    </li>
                    <li>
                        <span class="dashicons dashicons-admin-settings-alt"></span>
                        <div>
                            <strong><?php esc_html_e('Total Control:', 'wandtech-sample-module'); ?></strong>
                            <?php esc_html_e('Activate and deactivate features with a single click from a centralized, beautiful console.', 'wandtech-sample-module'); ?>
                        </div>
                    </li>
                </ul>
            </div>
            
            <!-- 3. Footer Link -->
            <div class="wt-sample-module-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wandtech-console')); ?>" class="button button-primary">
                    <?php esc_html_e('Go to Full Console', 'wandtech-sample-module'); ?>
                </a>
                <a href="https://github.com/HamxaBoustani/wandtech-console/" class="button button-secondary" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Star on GitHub', 'wandtech-sample-module'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}

new Wandtech_Sample_Module();