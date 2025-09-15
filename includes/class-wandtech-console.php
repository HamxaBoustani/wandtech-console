<?php
/**
 * The core plugin class for WandTech Console.
 *
 * This class is responsible for initializing the plugin. It orchestrates the loading
 * of core (system) modules, optional user modules, and admin components.
 * It follows a robust Singleton pattern to ensure only one instance ever exists.
 *
 * @package    Wandtech_Console
 * @subpackage Core
 * @author     Hamxa Boustani
 * @since      1.0.0
 * @version    3.2.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Final class Wandtech_Console.
 *
 * The main plugin controller class. Using `final` prevents this core class
 * from being extended, promoting a more stable and predictable architecture.
 */
final class Wandtech_Console {

	/**
	 * The single, static instance of this class.
	 *
	 * @since 1.0.0
	 * @var   Wandtech_Console|null
	 */
	private static ?Wandtech_Console $instance = null;

	/**
	 * The manager for user-configurable (optional) modules.
	 *
	 * @since 2.0.0
	 * @var   Wandtech_Console_Modules
	 */
	public Wandtech_Console_Modules $modules;

	/**
	 * The admin UI manager instance.
	 *
	 * @since 2.0.0
	 * @var   Wandtech_Console_Admin|null
	 */
	public ?Wandtech_Console_Admin $admin = null;

	/**
	 * The AJAX request handler instance.
	 *
	 * @since 2.0.0
	 * @var   Wandtech_Console_Ajax|null
	 */
	public ?Wandtech_Console_Ajax $ajax = null;

	/**
	 * Get the single instance of the class.
	 *
	 * This is the public access point for the singleton instance. It ensures that
	 * only one instance of the Wandtech_Console class can exist at any time.
	 *
	 * @since  1.0.0
	 * @return Wandtech_Console The singleton instance of the class.
	 */
	public static function get_instance(): Wandtech_Console {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Private to prevent direct instantiation.
	 *
	 * Sets up the entire plugin in a structured, performant order.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Step 1: Foundational setup that runs on every request.
		$this->ensure_modules_directory_exists();
		$this->load_system_modules();

		// Step 2: Initialize the manager for optional (user) modules.
		require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-modules.php';
		$this->modules = new Wandtech_Console_Modules();

		// Step 3: Conditionally load admin and AJAX components only when needed.
		// This prevents loading unnecessary code on the frontend.
		if (is_admin()) {
			require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-admin.php';
			$this->admin = new Wandtech_Console_Admin();

			if (wp_doing_ajax()) {
				require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console-ajax.php';
				$this->ajax = new Wandtech_Console_Ajax($this->modules);
			}
		}

		// Step 4: Defer actions to the appropriate WordPress hooks.
		// add_action('init', [ $this, 'load_textdomain' ]);
		add_action('plugins_loaded', [ $this->modules, 'load_active_modules' ], 20);
	}

	/**
	 * Ensures the external modules directory exists and is secure.
	 *
	 * This method checks for the `wp-content/modules` directory. If it doesn't
	 * exist, it creates it using the WP_Filesystem API and adds a blank
	 * index.php file to prevent directory listing.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	private function ensure_modules_directory_exists(): void {
		$modules_path = WANDTECH_CONSOLE_MODULES_PATH;

		// Optimization: Avoid filesystem checks on every page load if the directory already exists.
		if (is_dir($modules_path)) {
			return;
		}

		// Use WP_Filesystem API for secure and reliable file operations.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Create the directory if it doesn't exist.
		if (!$wp_filesystem->is_dir($modules_path)) {
			// `wp_mkdir_p` is WordPress's way of doing a recursive mkdir.
			if (!wp_mkdir_p($modules_path)) {
				// If creation fails, we can't proceed, but we don't throw an error to avoid crashing the site.
				return;
			}
		}

		// Security Hardening: Add a blank index.php file to prevent directory browsing on poorly configured servers.
		$index_file = trailingslashit($modules_path) . 'index.php';
		if (!$wp_filesystem->exists($index_file)) {
			$wp_filesystem->put_contents($index_file, '<?php // Silence is golden.');
		}
	}

	/**
	 * Scans the `system` directory and includes core modules conditionally.
	 *
	 * This method reads module headers to check for a `Developer Mode` flag.
	 * System modules marked with this flag will only be loaded if Developer Mode
	 * is enabled in the console's settings.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	private function load_system_modules(): void {
		$system_modules_dir = WANDTECH_CONSOLE_PATH . 'system/';

		if (!is_dir($system_modules_dir)) {
			return;
		}

		// Fetch settings once to avoid multiple database calls within the loop.
		$settings         = get_option('wandtech_console_settings', []);
		$dev_mode_enabled = ! empty($settings['developer_mode_enabled']);

		$module_paths = glob($system_modules_dir . '*/', GLOB_ONLYDIR);

		if (empty($module_paths)) {
			return;
		}

		foreach ($module_paths as $module_path) {
			$module_slug = basename($module_path);
			$module_file = $module_path . $module_slug . '.php';

			if (file_exists($module_file)) {
				$module_data = get_file_data(
					$module_file,
					[ 'Developer Mode' => 'Developer Mode' ]
				);

				// A module is a "dev module" if the 'Developer Mode' header exists with any value.
				$is_dev_module = ! empty($module_data['Developer Mode']);

				// A module is loaded if:
				// 1. It is NOT a dev module (i.e., it's a standard system module).
				// OR
				// 2. It IS a dev module, AND the main developer mode setting is enabled.
				if (!$is_dev_module || ($is_dev_module && $dev_mode_enabled)) {
					require_once $module_file;
				}
			}
		}
	}

	/**
	 * Prevents cloning of the instance (part of the Singleton pattern).
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function __clone() {
		// This is a private method to prevent cloning of the singleton instance.
	}

	/**
	 * Prevents unserializing of the instance (part of the Singleton pattern).
	 *
	 * @since  1.0.0
	 * @throws \Exception When trying to unserialize the singleton.
	 */
	public function __wakeup() {
		// Throws an error if someone tries to unserialize the singleton instance.
		throw new \Exception('Cannot unserialize a singleton.');
	}
}