<?php
/**
 * Handles module scanning, activation, and a robust dependency management system.
 * This version centralizes the module header translation logic.
 *
 * @package    Wandtech_Console
 */

if (!defined('ABSPATH')) exit;

class Wandtech_Console_Modules {

    private $active_modules;
    private $all_modules_data_cache = null;
    private $unmet_dependencies = [];

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('plugins_loaded', [$this, 'init'], 10);
    }

    /**
     * Initializes the module logic after all plugins are loaded.
     */
    public function init() {
        $all_modules = $this->get_all_modules();
        $all_module_slugs = array_keys($all_modules);
        $db_active_modules = get_option('wandtech_console_active_modules', []);

        $this->active_modules = array_intersect($db_active_modules, $all_module_slugs);

        if (count($this->active_modules) !== count($db_active_modules)) {
            update_option('wandtech_console_active_modules', $this->active_modules);
        }

        $this->check_active_module_dependencies();
        
        add_action('admin_notices', [$this, 'display_dependency_notices']);
    }

    /**
     * Checks dependencies for the validated list of active modules.
     */
    private function check_active_module_dependencies() {
        if (empty($this->active_modules)) {
            return;
        }
        foreach ($this->active_modules as $slug) {
            $dependency_error = $this->validate_dependencies_for_module($slug);
            if ($dependency_error) {
                $this->unmet_dependencies[$slug] = $dependency_error;
            }
        }
    }

    /**
     * Validates dependencies for a SINGLE module.
     */
    public function validate_dependencies_for_module(string $slug): ?array {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $all_modules = $this->get_all_modules();
        if (!isset($all_modules[$slug])) {
            return null;
        }
        $module = $all_modules[$slug];

        if (!empty($module['Requires Plugins'])) {
            $required_plugins = array_map('trim', explode(',', $module['Requires Plugins']));
            foreach ($required_plugins as $plugin_file) {
                if (!is_plugin_active($plugin_file)) {
                    return ['name' => $module['Name'], 'required' => $plugin_file];
                }
            }
        }
        return null;
    }

    /**
     * Scans the modules directory and returns raw, untranslated metadata.
     */
    public function get_all_modules(): array {
        if ($this->all_modules_data_cache !== null) {
            return $this->all_modules_data_cache;
        }
        
        $modules_dir = WANDTECH_CONSOLE_PATH . 'modules/';
        $all_modules = [];
        if (!is_dir($modules_dir)) {
            $this->all_modules_data_cache = [];
            return [];
        }

        $iterator = new DirectoryIterator($modules_dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $module_slug = $fileinfo->getFilename();
                $module_file = $fileinfo->getPathname() . '/' . $module_slug . '.php';
                if (file_exists($module_file)) {
                    $module_data = get_file_data($module_file, [
                        'Name'             => 'Module Name', 'Description'      => 'Description',
                        'Version'          => 'Version', 'Author'           => 'Author',
                        'Scope'            => 'Scope', 'Requires Plugins' => 'Requires Plugins',
                        'Text Domain'      => 'Text Domain', 'Domain Path'      => 'Domain Path'
                    ]);
                    if (!empty($module_data['Name'])) {
                        $all_modules[$module_slug] = $module_data;
                    }
                }
            }
        }
        uasort($all_modules, fn($a, $b) => strcmp($a['Name'], $b['Name']));
        $this->all_modules_data_cache = $all_modules;
        return $this->all_modules_data_cache;
    }

    /**
     * Gets all module data and applies header translations.
     * This is the new primary method for the admin UI to use.
     */
    public function get_all_modules_with_translated_headers(): array {
        $all_modules = $this->get_all_modules();
        $plugin_rel_path = dirname(plugin_basename(WANDTECH_CONSOLE_MAIN_FILE));

        foreach ($all_modules as $slug => &$data) { // Use reference `&` to modify in place
            $domain = $data['Text Domain'] ?? '';
            $path = $data['Domain Path'] ?? '';

            if ($domain && $path) {
                // Construct the correct relative path for the language file.
                $relative_path = $plugin_rel_path . '/modules/' . $slug . $path;
                load_plugin_textdomain($domain, false, $relative_path);
            }
            
            // Translate the Name and Description fields using their specific text domain.
            $data['Name'] = __($data['Name'], $domain);
            $data['Description'] = __($data['Description'], $domain);
        }

        return $all_modules;
    }

    /**
     * Loads the main PHP file for active modules based on their Scope.
     */
    public function load_active_modules() {
        if (!is_array($this->active_modules) || empty($this->active_modules)) {
            return;
        }

        $all_modules_data = $this->get_all_modules();
        
        foreach ($this->active_modules as $slug) {
            if (array_key_exists($slug, $this->unmet_dependencies)) continue;

            $scope = isset($all_modules_data[$slug]['Scope']) ? strtolower(trim($all_modules_data[$slug]['Scope'])) : 'all';
            $should_load = false;
            switch ($scope) {
                case 'admin': if (is_admin()) $should_load = true; break;
                case 'frontend': if (!is_admin() && !wp_doing_cron()) $should_load = true; break;
                case 'all': default: $should_load = true; break;
            }

            if ($should_load) {
                $module_file_path = WANDTECH_CONSOLE_PATH . 'modules/' . $slug . '/' . $slug . '.php';
                if (file_exists($module_file_path)) {
                    require_once $module_file_path;
                }
            }
        }
    }
    
    /**
     * Displays admin notices for unmet dependencies.
     */
    public function display_dependency_notices() {
        if (empty($this->unmet_dependencies)) return;
        $screen = get_current_screen();
        if (!$screen || ($screen->id !== 'plugins' && $screen->id !== 'toplevel_page_wandtech-console')) return;
        foreach ($this->unmet_dependencies as $module) {
            ?>
            <div class="notice notice-error is-dismissible"><p><strong><?php printf(esc_html__('WandTech Console - Module Disabled: %s', 'wandtech-console'), esc_html($module['name'])); ?></strong><br><?php printf(esc_html__('This module requires the following plugin to be active: %s', 'wandtech-console'), '<strong>' . esc_html($module['required']) . '</strong>'); ?></p></div>
            <?php
        }
    }

    /**
     * Retrieves the clean, validated list of active module slugs.
     */
    public function get_active_modules(): array {
        if (!is_array($this->active_modules)) {
            $this->init();
        }
        return $this->active_modules;
    }

    /**
     * Activates a module.
     */
    public function activate_module(string $slug): bool {
        if ($this->is_module_active($slug)) return true;
        $this->active_modules[] = $slug;
        return update_option('wandtech_console_active_modules', array_unique($this->active_modules));
    }

    /**
     * Deactivates a module.
     */
    public function deactivate_module(string $slug): bool {
        if (!$this->is_module_active($slug)) return true;
        $this->active_modules = array_diff($this->active_modules, [$slug]);
        return update_option('wandtech_console_active_modules', $this->active_modules);
    }

    /**
     * Checks if a module is active.
     */
    public function is_module_active(string $slug): bool {
        return in_array($slug, $this->active_modules, true);
    }
}