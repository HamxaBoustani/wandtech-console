<?php
/**
 * The core plugin class for WandTech Console.
 *
 * This class is responsible for initializing the plugin. It orchestrates the loading
 * of core (always-on) modules, optional user modules, and admin components.
 * It follows a robust Singleton pattern to ensure only one instance ever exists.
 *
 * @package    Wandtech_Console
 */

if (!defined('ABSPATH')) exit;

final class Wandtech_Console {

    /**
     * The single, static instance of this class.
     * @var Wandtech_Console|null
     */
    private static $instance = null;

    /**
     * The manager for user-configurable (optional) modules.
     * @var Wandtech_Console_Modules|null
     */
    public $modules;

    /**
     * The admin UI manager instance.
     * @var Wandtech_Console_Admin|null
     */
    public $admin;

    /**
     * The AJAX request handler instance.
     * @var Wandtech_Console_Ajax|null
     */
    public $ajax;

    /**
     * Get the single instance of the class.
     * This is the public access point for the singleton instance.
     *
     * @return Wandtech_Console
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Private to prevent direct instantiation.
     * Sets up the entire plugin in a structured, performant order.
     */
    private function __construct() {
        // STEP 1: Load Core System Modules
        // These modules provide the fundamental UI tabs (Dashboard, Modules, About)
        // and are always active. They are loaded first.
        $this->load_core_modules();

        // STEP 2: Initialize the Manager for Optional Modules
        // This class handles the modules located in the /modules/ directory.
        require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-modules.php';
        $this->modules = new Wandtech_Console_Modules();

        // STEP 3: Conditionally Load Admin and AJAX Components
        // These are only loaded in an admin context to keep the frontend fast.
        if (is_admin()) {
            require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-admin.php';
            $this->admin = new Wandtech_Console_Admin();
            
            if (wp_doing_ajax()) {
                require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-ajax.php';
                $this->ajax = new Wandtech_Console_Ajax($this->modules);
            }
        }

        // STEP 4: Defer Actions until WordPress is Fully Loaded
        // This ensures compatibility with other plugins.
        add_action('plugins_loaded', array($this->modules, 'load_active_modules'), 20);
        // add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Scans the `core-modules` directory and includes all found modules.
     * These modules are essential for the console's UI and are not user-configurable.
     * This method runs on every page load to ensure the core UI is always available.
     */
    private function load_core_modules() {
        $core_modules_dir = WANDTECH_CONSOLE_PATH . 'core-modules/';

        if (!is_dir($core_modules_dir)) {
            return;
        }

        // Use glob to find all module sub-directories.
        $module_paths = glob($core_modules_dir . '*/', GLOB_ONLYDIR);

        foreach ($module_paths as $module_path) {
            $module_slug = basename($module_path);
            $module_file = $module_path . $module_slug . '.php';

            if (file_exists($module_file)) {
                require_once $module_file;
            }
        }
    }

    /**
     * Prevents cloning of the instance (part of the Singleton pattern).
     */
    private function __clone() {
        // This is a private method to prevent cloning.
    }

    /**
     * Prevents unserializing of the instance (part of the Singleton pattern).
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Loads the main plugin text domain for core strings.
     */
    // public function load_textdomain() {
    //     load_plugin_textdomain(
    //         'wandtech-console',
    //         false,
    //         dirname(plugin_basename(WANDTECH_CONSOLE_MAIN_FILE)) . '/languages/'
    //     );
    // }
}