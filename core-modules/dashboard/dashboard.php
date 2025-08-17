<?php
/**
 * Core Module Name: Console Dashboard
 * Description:      Provides the main "Dashboard" tab for the WandTech Console, including system stats.
 * Version:          1.1.0
 * Author:           Hamxa
 * Scope:            admin
 */

if (!defined('ABSPATH')) exit;

if (defined('WANDTECH_DASHBOARD_MODULE_LOADED')) {
    return;
}
define('WANDTECH_DASHBOARD_MODULE_LOADED', true);

class Wandtech_Dashboard_Tab {

    /**
     * Constructor. Hooks into the framework.
     */
    public function __construct() {
        // This core module uses the main plugin's text domain.
        add_filter('wandtech_console_register_tabs', [$this, 'register_tab']);
    }

    /**
     * Registers the "Dashboard" tab with the console's UI.
     *
     * @param array $tabs The existing array of tabs.
     * @return array The modified array of tabs.
     */
    public function register_tab(array $tabs): array {
        $tabs['dashboard'] = [
            'title'    => __('Dashboard', 'wandtech-console'),
            'callback' => [$this, 'render_content'],
            'priority' => 10, // A low priority to ensure it's the first tab.
        ];
        return $tabs;
    }

    /**
     * Renders the HTML content for the "Dashboard" tab.
     */
    public function render_content() {
        // --- Data Fetching ---
        $modules_manager = Wandtech_Console::get_instance()->modules;
        $all_modules = $modules_manager->get_all_modules();
        $active_modules = $modules_manager->get_active_modules();
        
        $total_count = count($all_modules);
        $active_count = count($active_modules);
        $inactive_count = $total_count - $active_count;

        // --- Performance Calculation ---
        $admin_only_active_modules = 0;
        foreach ($active_modules as $slug) {
            // Check if the module data exists and its scope is 'admin'.
            if (isset($all_modules[$slug]) && isset($all_modules[$slug]['Scope']) && $all_modules[$slug]['Scope'] === 'admin') {
                $admin_only_active_modules++;
            }
        }
        ?>

        <div class="wandtech-dashboard-welcome">
            <h3><?php esc_html_e('Welcome to your Control Center', 'wandtech-console'); ?></h3>
            <p><?php esc_html_e('This is the central hub for all WandTech features. From here, you can manage powerful tools that enhance your WordPress site. Use the tabs above to navigate and configure your modules.', 'wandtech-console'); ?></p>
        </div>

        <h4><?php esc_html_e('System Status At a Glance', 'wandtech-console'); ?></h4>
        <div class="wandtech-stats-grid">
            <div class="stat-card">
                <span class="dashicons dashicons-admin-plugins"></span>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($total_count); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total Modules Available', 'wandtech-console'); ?></span>
                </div>
            </div>
            <div class="stat-card active">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($active_count); ?></span>
                    <span class="stat-label"><?php esc_html_e('Active Modules', 'wandtech-console'); ?></span>
                </div>
            </div>
            <div class="stat-card inactive">
                <span class="dashicons dashicons-dismiss"></span>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($inactive_count); ?></span>
                    <span class="stat-label"><?php esc_html_e('Inactive Modules', 'wandtech-console'); ?></span>
                </div>
            </div>
            <div class="stat-card performance">
                <span class="dashicons dashicons-performance"></span>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($admin_only_active_modules); ?></span>
                    <span class="stat-label"><?php esc_html_e('Performance Optimizations', 'wandtech-console'); ?></span>
                </div>
            </div>
        </div>

        <?php if ($admin_only_active_modules > 0) : ?>
            <div class="wandtech-performance-explainer">
                <p>
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php
                    printf(
                        /* translators: %d is the number of admin-only modules. */
                        esc_html(_n(
                            'To keep your site fast for visitors, we prevented %d admin-specific module from loading on the frontend.',
                            'To keep your site fast for visitors, we prevented %d admin-specific modules from loading on the frontend.',
                            $admin_only_active_modules,
                            'wandtech-console'
                        )),
                        $admin_only_active_modules
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <?php
    }
}

new Wandtech_Dashboard_Tab();