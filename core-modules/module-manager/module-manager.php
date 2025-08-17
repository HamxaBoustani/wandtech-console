<?php
/**
 * Core Module Name: Module Manager Tab
 * Description:      Provides the "Modules" tab for managing all optional modules in the WandTech Console.
 * Version:          1.3.3
 * Author:           Hamxa
 * Scope:            admin
 */

if (!defined('ABSPATH')) exit;

if (defined('WANDTECH_MODULE_MANAGER_LOADED')) {
    return;
}
define('WANDTECH_MODULE_MANAGER_LOADED', true);

class Wandtech_Module_Manager_Tab {

    /**
     * Constructor. Registers the necessary hooks.
     */
    public function __construct() {
        add_filter('wandtech_console_register_tabs', [$this, 'register_tab']);
    }

    /**
     * Registers the "Modules" tab with the console's UI.
     */
    public function register_tab($tabs) {
        $tabs['modules'] = [
            'title'    => __('Modules', 'wandtech-console'),
            'callback' => [$this, 'render_content'],
            'priority' => 40,
        ];
        return $tabs;
    }

    /**
     * Renders the HTML content for the "Modules" tab.
     */
    public function render_content() {
        $modules_manager = Wandtech_Console::get_instance()->modules;
        
        // Use the new centralized method to get module data with translated headers.
        $display_modules = $modules_manager->get_all_modules_with_translated_headers();
        
        $active_modules = $modules_manager->get_active_modules();
        
        $total_count = count($display_modules);
        $active_count = count($active_modules);
        $inactive_count = $total_count - $active_count;
        ?>
        
        <div class="modules-header">
            <div class="module-filters">
                <a href="#" class="filter-link current" data-filter="all">
                    <?php _e('All', 'wandtech-console'); ?> <span class="count" id="filter-count-all">(<?php echo $total_count; ?>)</span>
                </a>
                <a href="#" class="filter-link" data-filter="active">
                    <?php _e('Active', 'wandtech-console'); ?> <span class="count" id="filter-count-active">(<?php echo $active_count; ?>)</span>
                </a>
                <a href="#" class="filter-link" data-filter="inactive">
                    <?php _e('Inactive', 'wandtech-console'); ?> <span class="count" id="filter-count-inactive">(<?php echo $inactive_count; ?>)</span>
                </a>
            </div>

            <div class="module-actions">
                <input type="search" id="module-search-input" class="wp-filter-search" 
                       placeholder="<?php esc_attr_e('Search modules...', 'wandtech-console'); ?>">
                
                <?php
                /**
                 * Fires in the module manager header, allowing other modules to add buttons or actions.
                 */
                do_action('wandtech_console_module_manager_actions');
                ?>
            </div>
        </div>

        <div class="module-cards">
            <?php if (!empty($display_modules)) : ?>
                <?php foreach ($display_modules as $slug => $module_data) :
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
                            <div class="module-meta">
                                <small><?php esc_html_e('Version:', 'wandtech-console'); ?> <?php echo esc_html($module_data['Version']); ?></small>
                                <small><?php esc_html_e('Author:', 'wandtech-console'); ?> <?php echo esc_html($module_data['Author']); ?></small>
                            </div>
                            <div class="module-action-links">
                                <a href="#" class="delete-module-link" data-module="<?php echo esc_attr($slug); ?>" style="<?php echo $is_active ? 'display:none;' : ''; ?>">
                                    <?php esc_html_e('Delete', 'wandtech-console'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="spinner-overlay"></div>
                    </div>
                <?php endforeach; ?>
                
                <p class="no-results-message" style="display: none;">
                    <?php esc_html_e('No modules found matching your search criteria.', 'wandtech-console'); ?>
                </p>

            <?php else : ?>
                <p><?php esc_html_e('No optional modules found. Click the "Install Module" button to upload one, or add it manually to the `/modules` directory.', 'wandtech-console'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}

new Wandtech_Module_Manager_Tab();