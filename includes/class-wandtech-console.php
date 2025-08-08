<?php
/**
 * The core plugin class for WandTech Console.
 *
 * This class is responsible for initializing the plugin, loading dependencies
 * conditionally for better performance, and setting up the main components.
 * It follows the Singleton pattern to ensure only one instance exists.
 *
 * @package    Wandtech_Console
 */

if (!defined('ABSPATH')) exit;

final class Wandtech_Console {

    /**
     * The single instance of the class.
     * @var Wandtech_Console|null
     */
    private static $instance = null;

    /**
     * The modules manager instance.
     * @var Wandtech_Console_Modules|null
     */
    public $modules;

    /**
     * The admin manager instance.
     * @var Wandtech_Console_Admin|null
     */
    public $admin;

    /**
     * The AJAX manager instance.
     * @var Wandtech_Console_Ajax|null
     */
    public $ajax;

    /**
     * Get the single instance of the class.
     * Ensures only one instance of the console is loaded.
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
     * This is where the magic happens: dependencies are loaded conditionally.
     */
    private function __construct() {
        // --- Conditional Dependency Loading for Performance ---

        // The Modules class is essential for determining which modules to load.
        // It's lightweight and needed on both admin and frontend.
        require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-modules.php';
        $this->modules = new Wandtech_Console_Modules();

        // Load admin-specific components only if we are in an admin context.
        // This prevents loading unnecessary admin classes on the frontend.
        if (is_admin()) {
            require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-admin.php';
            $this->admin = new Wandtech_Console_Admin();
            
            // The AJAX handler is a specific type of admin request.
            // We load it only when an AJAX request is being processed.
            if (wp_doing_ajax()) {
                require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-ajax.php';
                $this->ajax = new Wandtech_Console_Ajax($this->modules);
            }
        }

        // Defer the loading of active module files until all plugins are loaded.
        // This ensures compatibility with other plugins (e.g., WooCommerce).
        add_action('plugins_loaded', array($this->modules, 'load_active_modules'), 20);

        // Load the text domain for the core plugin.
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wandtech-console',
            false,
            dirname(plugin_basename(WANDTECH_CONSOLE_MAIN_FILE)) . '/languages/'
        );
    }
}