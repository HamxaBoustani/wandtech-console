<?php
/**
 * Handles module scanning, activation, dependency management, and loading.
 *
 * This class is the core engine for managing all optional modules located
 * in the `wp-content/modules` directory.
 *
 * @package    Wandtech_Console
 * @subpackage Modules
 * @author     Hamxa Boustani
 * @since      2.0.0
 * @version    3.2.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Wandtech_Console_Modules.
 *
 * Manages the entire lifecycle of optional modules, from discovery and
 * validation to conditional loading based on scope and dependencies.
 */
final class Wandtech_Console_Modules {

	/**
	 * The transient key for caching the scanned module list.
	 *
	 * @since 3.1.0
	 * @const string
	 */
	const MODULE_CACHE_KEY = 'wandtech_console_all_modules';

	/**
	 * A validated list of currently active module slugs.
	 *
	 * This property is populated during the `init` method.
	 *
	 * @since 2.0.0
	 * @var   string[]
	 */
	private array $active_modules = [];

	/**
	 * A request-level cache for all found module data.
	 *
	 * This prevents multiple scans or transient lookups within a single page load.
	 *
	 * @since 2.0.0
	 * @var   array|null
	 */
	private ?array $all_modules_data_cache = null;

	/**
	 * Constructor. Registers the main init hook.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// `plugins_loaded` is the correct hook to ensure all plugins are loaded
		// before we check for dependencies.
		add_action('plugins_loaded', [ $this, 'init' ], 10);
	}

	/**
	 * Clears the cached list of modules from the transient cache.
	 *
	 * This static method is called when modules are installed, deleted, or updated
	 * to ensure the module list is refreshed on the next page load.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	public static function clear_cache(): void {
		delete_transient(self::MODULE_CACHE_KEY);
	}

	/**
	 * Initializes the module manager.
	 *
	 * This method performs critical "self-healing" tasks:
	 * 1. It reconciles the list of active modules from the database with the modules that physically exist.
	 * 2. It checks dependencies for all active modules and auto-deactivates any that have unmet requirements.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function init(): void {
		$all_modules          = $this->get_all_modules();
		$all_module_slugs     = array_keys($all_modules);
		$db_active_modules    = get_option('wandtech_console_active_modules', []);
		$modules_to_deactivate = [];
		$deactivation_notices = [];

		// Self-healing: Ensure active modules stored in the DB actually exist on the filesystem.
		$this->active_modules = array_intersect($db_active_modules, $all_module_slugs);

		foreach ($this->active_modules as $slug) {
			// Dependency Check: Validate required plugins.
			$dependency_error = $this->validate_dependencies_for_module($slug);
			if ($dependency_error) {
				$message = sprintf(
					/* translators: 1: Module name, 2: Required plugin name. */
					__('<strong>WandTech Module Auto-Deactivated: %1$s</strong><br>This module was deactivated because it requires the following plugin to be active: %2$s', 'wandtech-console'),
					esc_html($dependency_error['name']),
					'<strong>' . esc_html($dependency_error['required']) . '</strong>'
				);
				$deactivation_notices[]  = $message;
				$modules_to_deactivate[] = $slug;
				continue; // No need to check scope if it's already being deactivated.
			}

			// Scope validation: Ensure the 'Scope' header is valid.
			$valid_scopes = [ 'admin', 'frontend', 'all' ];
			$scope        = strtolower(trim($all_modules[ $slug ]['Scope'] ?? ''));
			if (empty($scope) || !in_array($scope, $valid_scopes, true)) {
				$message = sprintf(
					/* translators: 1: Module name, 2: The invalid scope value found. */
					__('<strong>WandTech Module Auto-Deactivated: %1$s</strong><br>It was deactivated because the "Scope" header is missing or has an invalid value ("%2$s"). Valid scopes are: admin, frontend, all.', 'wandtech-console'),
					esc_html($all_modules[ $slug ]['Name']),
					esc_html($scope)
				);
				$deactivation_notices[]  = $message;
				$modules_to_deactivate[] = $slug;
			}
		}

		// If any modules failed validation, update the database and prepare notices.
		if (!empty($modules_to_deactivate)) {
			$this->active_modules = array_diff($this->active_modules, $modules_to_deactivate);
			update_option('wandtech_console_active_modules', $this->active_modules);

			if (!empty($deactivation_notices)) {
				set_transient('wandtech_console_deactivation_notices', $deactivation_notices, 60);
			}
		} elseif (count($this->active_modules) !== count($db_active_modules)) {
			// This handles cases where a module was manually deleted, so we sync the DB.
			update_option('wandtech_console_active_modules', $this->active_modules);
		}
	}

	/**
	 * Validates dependencies for a single module.
	 *
	 * @since  2.2.0
	 * @param  string $slug The slug of the module to validate.
	 * @return array<string, string>|null An associative array with error details on failure, or null on success.
	 */
	public function validate_dependencies_for_module( string $slug ): ?array {
		if (!function_exists('is_plugin_active')) {
			// This function is only available in the admin area by default, so we must include it.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_modules = $this->get_all_modules();
		if (!isset($all_modules[ $slug ])) {
			return null;
		}

		$module = $all_modules[ $slug ];
		if (!empty($module['Requires Plugins'])) {
			$required_plugins = array_map('trim', explode(',', $module['Requires Plugins']));
			foreach ($required_plugins as $plugin_file) {
				if (!is_plugin_active($plugin_file)) {
					return [
						'name'     => $module['Name'],
						'required' => $plugin_file,
					];
				}
			}
		}
		return null;
	}

	/**
	 * Scans the modules directory or retrieves the list from a persistent cache.
	 *
	 * This method uses a two-layer caching strategy:
	 * 1. A request-level cache (`$this->all_modules_data_cache`) to avoid multiple lookups in one request.
	 * 2. A persistent transient cache (`self::MODULE_CACHE_KEY`) to avoid rescanning the filesystem on every page load.
	 *
	 * @since  2.0.0
	 * @return array A list of all found modules with their header data.
	 */
	public function get_all_modules(): array {
		if (null !== $this->all_modules_data_cache) {
			return $this->all_modules_data_cache;
		}

		$cached_modules = get_transient(self::MODULE_CACHE_KEY);
		if (is_array($cached_modules)) {
			$this->all_modules_data_cache = $cached_modules;
			return $this->all_modules_data_cache;
		}

		$all_modules = $this->scan_module_directory();
		set_transient(self::MODULE_CACHE_KEY, $all_modules, 12 * HOUR_IN_SECONDS);

		$this->all_modules_data_cache = $all_modules;
		return $this->all_modules_data_cache;
	}

	/**
	 * Performs the actual scan of the modules directory.
	 *
	 * @since  3.1.0
	 * @return array The list of found modules.
	 */
	private function scan_module_directory(): array {
		$modules_dir = WANDTECH_CONSOLE_MODULES_PATH;
		$all_modules = [];
		if (!is_dir($modules_dir)) {
			return [];
		}

		try {
			$iterator = new DirectoryIterator($modules_dir);
			foreach ($iterator as $fileinfo) {
				if ($fileinfo->isDir() && !$fileinfo->isDot()) {
					$module_slug = $fileinfo->getFilename();
					$module_file = $fileinfo->getPathname() . '/' . $module_slug . '.php';

					if (file_exists($module_file)) {
						$module_data = get_file_data(
							$module_file,
							[
								'Name'             => 'Module Name',
								'Module URI'       => 'Module URI',
								'Description'      => 'Description',
								'Version'          => 'Version',
								'Author'           => 'Author',
								'Scope'            => 'Scope',
								'Settings Slug'    => 'Settings Slug',
								'Requires Plugins' => 'Requires Plugins',
								'Text Domain'      => 'Text Domain',
								'Domain Path'      => 'Domain Path',
							]
						);

						if (!empty($module_data['Name'])) {
							$module_data['path']          = $module_file;
							$module_data['thumbnail_url'] = $this->find_module_thumbnail($fileinfo, $module_slug);
							$all_modules[ $module_slug ]  = $module_data;
						}
					}
				}
			}
		} catch (Exception $e) {
			// Prevent filesystem errors from crashing the site. Log the error for debugging.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('WandTech Console: Error scanning modules directory - ' . $e->getMessage());
			return [];
		}

		// Sort modules alphabetically by name for a consistent UI.
		uasort($all_modules, fn( $a, $b ) => strcmp($a['Name'], $b['Name']));
		return $all_modules;
	}

	/**
	 * Finds the thumbnail URL for a given module.
	 *
	 * @since 3.2.0
	 * @param DirectoryIterator $fileinfo    The DirectoryIterator object for the module's folder.
	 * @param string            $module_slug The slug of the module.
	 * @return string The URL of the thumbnail, or an empty string if not found.
	 */
	private function find_module_thumbnail( DirectoryIterator $fileinfo, string $module_slug ): string {
		$thumbnail_dir_path = $fileinfo->getPathname() . '/assets/images/';
		$thumbnail_dir_url  = WANDTECH_CONSOLE_MODULES_URL . $module_slug . '/assets/images/';
		$supported_formats  = [ 'png', 'jpg', 'jpeg', 'svg' ];

		foreach ($supported_formats as $format) {
			if (file_exists($thumbnail_dir_path . 'thumbnail.' . $format)) {
				return $thumbnail_dir_url . 'thumbnail.' . $format;
			}
		}
		return '';
	}

	/**
	 * Gets all module data and applies header translations.
	 *
	 * This method is responsible for loading the translation files specifically
	 * for the module headers (Name, Description) before they are displayed in the Console.
	 *
	 * @since  2.1.0
	 * @return array A list of all modules with translated headers.
	 */
	public function get_all_modules_with_translated_headers(): array {
		$all_modules = $this->get_all_modules();

		foreach ($all_modules as &$data) { // Use reference to modify the array directly.
			$domain = $data['Text Domain'] ?? '';
			$path   = $data['Domain Path'] ?? '';

			// [FIXED] The text domain must be loaded *before* calling the translation functions.
			if ($domain && $path) {
				$locale      = determine_locale(); // Use WordPress's function to get the current locale.
				$mofile      = "{$domain}-{$locale}.mo";
				$mofile_path = trailingslashit(dirname($data['path'])) . trim($path, '/') . '/' . $mofile;

				if (file_exists($mofile_path)) {
					load_textdomain($domain, $mofile_path);
				}
			}

			// Now that the text domain is loaded (if available), we can translate.
			if ($domain) {
				// phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain
				// We are intentionally using variables for translation here.
				// This is a calculated decision because the i18n tools cannot parse custom headers like 'Module Name'.
				// We manually ensure the source strings are added to the .pot file.
				$data['Name']        = __($data['Name'], $domain);
				$data['Description'] = __($data['Description'], $domain);
				// phpcs:enable
			}
		}
		return $all_modules;
	}

	/**
	 * Loads the main PHP file for active modules based on their scope.
	 *
	 * This is the core performance feature of the framework. It ensures that
	 * code is only loaded in the context where it is actually needed.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function load_active_modules(): void {
		if (empty($this->active_modules)) {
			return;
		}

		$all_modules_data = $this->get_all_modules();

		foreach ($this->active_modules as $slug) {
			if (!isset($all_modules_data[ $slug ])) {
				continue;
			}
			$module_data = $all_modules_data[ $slug ];
			$scope       = strtolower(trim($module_data['Scope'] ?? 'all'));
			$should_load = false;

			switch ($scope) {
				case 'admin':
					$should_load = is_admin();
					break;
				case 'frontend':
					$should_load = !is_admin() && !wp_doing_cron();
					break;
				case 'all':
					$should_load = true;
					break;
			}

			if ($should_load) {
				$module_file_path = $module_data['path'];
				if (file_exists($module_file_path)) {
					require_once $module_file_path;
				}
			}
		}
	}

	/**
	 * Retrieves the list of active module slugs.
	 *
	 * @since  2.0.0
	 * @return string[] The list of active module slugs.
	 */
	public function get_active_modules(): array {
		return $this->active_modules;
	}

	/**
	 * Activates a module by adding its slug to the database options.
	 *
	 * @since  2.0.0
	 * @param  string $slug The slug of the module to activate.
	 * @return bool True on success, false on failure.
	 */
	public function activate_module( string $slug ): bool {
		if ($this->is_module_active($slug)) {
			return true; // Already active, no action needed.
		}
		$this->active_modules[] = $slug;
		$this->active_modules   = array_unique($this->active_modules);
		return update_option('wandtech_console_active_modules', $this->active_modules);
	}

	/**
	 * Deactivates a module by removing its slug from the database options.
	 *
	 * @since  2.0.0
	 * @param  string $slug The slug of the module to deactivate.
	 * @return bool True on success, false on failure.
	 */
	public function deactivate_module( string $slug ): bool {
		if (!$this->is_module_active($slug)) {
			return true; // Already inactive, no action needed.
		}
		$this->active_modules = array_diff($this->active_modules, [ $slug ]);
		return update_option('wandtech_console_active_modules', $this->active_modules);
	}

	/**
	 * Checks if a specific module is active.
	 *
	 * @since  2.0.0
	 * @param  string $slug The slug of the module to check.
	 * @return bool True if the module is active, false otherwise.
	 */
	public function is_module_active( string $slug ): bool {
		// The init() method should have already run and populated this property.
		return in_array($slug, $this->active_modules, true);
	}
}