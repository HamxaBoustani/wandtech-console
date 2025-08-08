<?php
/**
 * Handles module scanning, activation, deactivation, and conditional loading.
 */

if (!defined('ABSPATH')) exit;

class Wandtech_Console_Modules {

    private $active_modules;
    private $all_modules_data_cache = null; // Cache for module metadata.

    public function __construct() {
        $this->active_modules = $this->get_active_modules();
    }

    /**
     * Scans the modules directory and returns metadata for all valid modules.
     * Caches the result to prevent multiple file scans in a single request.
     */
    public function get_all_modules() {
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
                        'Name'        => 'Module Name',
                        'Description' => 'Description',
                        'Version'     => 'Version',
                        'Author'      => 'Author',
                        'Scope'       => 'Scope' // Read the new Scope header.
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
     * Loads the main PHP file for active modules based on their defined Scope.
     */
    public function load_active_modules() {
        if (empty($this->active_modules)) {
            return;
        }

        $all_modules_data = $this->get_all_modules();

        foreach ($this->active_modules as $slug) {
            // Default scope is 'all' if not specified.
            $scope = isset($all_modules_data[$slug]['Scope']) ? strtolower(trim($all_modules_data[$slug]['Scope'])) : 'all';

            $should_load = false;
            switch ($scope) {
                case 'admin':
                    if (is_admin()) $should_load = true;
                    break;
                case 'frontend':
                    if (!is_admin() && !wp_doing_cron()) $should_load = true;
                    break;
                case 'all':
                default:
                    $should_load = true;
                    break;
            }

            if ($should_load) {
                $module_file = WANDTECH_CONSOLE_PATH . 'modules/' . $slug . '/' . $slug . '.php';
                if (file_exists($module_file)) {
                    require_once $module_file;
                }
            }
        }
    }
    
    // The rest of the functions (get_active_modules, activate_module, etc.) remain the same.
    public function get_active_modules() { return get_option('wandtech_console_active_modules', []); }
    public function activate_module($slug) { if ($this->is_module_active($slug)) return true; $this->active_modules[] = $slug; return update_option('wandtech_console_active_modules', array_unique($this->active_modules)); }
    public function deactivate_module($slug) { if (!$this->is_module_active($slug)) return true; $this->active_modules = array_diff($this->active_modules, [$slug]); return update_option('wandtech_console_active_modules', $this->active_modules); }
    public function is_module_active($slug) { return in_array($slug, $this->active_modules, true); }
}