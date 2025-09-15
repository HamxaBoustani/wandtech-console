<?php
/**
 * Handles all AJAX requests for the WandTech Console.
 *
 * This class processes activation, deactivation, and deletion of modules.
 * Installation of new modules is handled by the dedicated 'module-installer' core module.
 *
 * @package    Wandtech_Console
 * @subpackage Ajax
 * @author     Hamxa Boustani
 * @since      1.0.0
 * @version    3.2.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Wandtech_Console_Ajax.
 *
 * Manages all AJAX endpoints for the console, ensuring proper security checks
 * and data validation for each request.
 */
final class Wandtech_Console_Ajax {

	/**
	 * A reference to the modules manager class instance.
	 *
	 * @since 2.0.0
	 * @var   Wandtech_Console_Modules
	 */
	private Wandtech_Console_Modules $modules_manager;

	/**
	 * Constructor. Registers all AJAX actions.
	 *
	 * @since 2.0.0
	 * @param Wandtech_Console_Modules $modules_manager An instance of the modules manager.
	 */
	public function __construct( Wandtech_Console_Modules $modules_manager ) {
		$this->modules_manager = $modules_manager;

		add_action('wp_ajax_wandtech_console_toggle_module', [ $this, 'handle_toggle_module' ]);
		add_action('wp_ajax_wandtech_console_delete_module', [ $this, 'handle_delete_module' ]);
	}

	/**
	 * Handles the AJAX request to toggle a module's status (activate/deactivate).
	 *
	 * Performs all necessary security and validation checks before activation.
	 *
	 * @since  2.0.0
	 * @return void This method terminates execution with a JSON response.
	 */
	public function handle_toggle_module(): void {
		// Security Check: Verify nonce and user capabilities.
		if (!check_ajax_referer('wandtech_console_module_nonce', 'nonce', false) || !current_user_can('manage_options')) {
			$this->send_error(__('Security check failed or insufficient permissions.', 'wandtech-console'), 403);
		}

		// Input Validation: Sanitize and validate the module slug.
		$slug              = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';
		$available_modules = $this->modules_manager->get_all_modules();
		if (empty($slug) || !array_key_exists($slug, $available_modules)) {
			$this->send_error(__('The specified module is invalid.', 'wandtech-console'), 400);
		}

		$wants_to_activate = isset($_POST['status']) && 'true' === $_POST['status'];

		if ($wants_to_activate) {
			$this->activate_module($slug, $available_modules[ $slug ]);
		} else {
			// Deactivation is simpler and doesn't require pre-checks.
			$this->modules_manager->deactivate_module($slug);
		}

		// If we reached here, the operation was successful.
		$this->send_success(__('Module status updated successfully.', 'wandtech-console'));
	}

	/**
	 * Validates and activates a single module.
	 *
	 * This helper method is called by `handle_toggle_module` to keep the code clean.
	 *
	 * @since 3.2.0
	 * @param string $slug        The slug of the module to activate.
	 * @param array  $module_data The header data for the module.
	 * @return void This method terminates execution with an error on failure.
	 */
	private function activate_module( string $slug, array $module_data ): void {
		// Validation 1: Check for a valid 'Scope' header.
		$scope        = strtolower(trim($module_data['Scope'] ?? ''));
		$valid_scopes = [ 'admin', 'frontend', 'all' ];

		if (empty($scope) || !in_array($scope, $valid_scopes, true)) {
			$error_message = sprintf(
				/* translators: 1: Module name, 2: List of valid scopes. */
				esc_html__('Cannot activate "%1$s". The "Scope" header is missing or invalid. Please use one of: %2$s.', 'wandtech-console'),
				esc_html($module_data['Name']),
				'<code>admin</code>, <code>frontend</code>, <code>all</code>'
			);
			$this->send_error($error_message, 409); // 409 Conflict is a suitable status code.
		}

		// Validation 2: Check for plugin dependencies.
		$dependency_error = $this->modules_manager->validate_dependencies_for_module($slug);
		if ($dependency_error) {
			$error_message = sprintf(
				/* translators: 1: Module name, 2: Required plugin name. */
				esc_html__('Cannot activate "%1$s". It requires: %2$s', 'wandtech-console'),
				esc_html($dependency_error['name']),
				'<strong>' . esc_html($dependency_error['required']) . '</strong>'
			);
			$this->send_error($error_message, 409);
		}

		// If all checks pass, activate the module.
		$this->modules_manager->activate_module($slug);
	}

	/**
	 * Handles the secure deletion of a module's directory.
	 *
	 * @since  2.1.0
	 * @return void This method terminates execution with a JSON response.
	 */
	public function handle_delete_module(): void {
		// Security Check 1: Verify nonce and user capabilities.
		if (!check_ajax_referer('wandtech_console_delete_nonce', 'nonce', false) || !current_user_can('delete_plugins')) {
			$this->send_error(__('Security check failed or insufficient permissions.', 'wandtech-console'), 403);
		}

		// Input Validation: Sanitize and validate the module slug.
		$slug = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';
		if (empty($slug) || str_contains($slug, '.') || str_contains($slug, '/')) {
			$this->send_error(__('Invalid module slug format.', 'wandtech-console'), 400);
		}

		// Logic Check 1: Ensure the module exists.
		if (!array_key_exists($slug, $this->modules_manager->get_all_modules())) {
			$this->send_error(__('The specified module does not exist.', 'wandtech-console'), 404);
		}

		// Logic Check 2: Ensure the module is not active before deletion.
		if ($this->modules_manager->is_module_active($slug)) {
			$this->send_error(__('Please deactivate the module before deleting it.', 'wandtech-console'), 409);
		}

		// Initialize WordPress Filesystem API for secure file operations.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if (false === WP_Filesystem()) {
			$this->send_error(__('Could not initialize the WordPress Filesystem.', 'wandtech-console'), 500);
		}
		global $wp_filesystem;

		$module_path = WANDTECH_CONSOLE_MODULES_PATH . $slug;

		// Security Check 2: Prevent Path Traversal attacks.
		// Ensure the resolved module path is genuinely inside the allowed modules directory.
		$base_path            = wp_normalize_path(WANDTECH_CONSOLE_MODULES_PATH);
		$module_path_to_check = wp_normalize_path($module_path);

		if (strpos($module_path_to_check, $base_path) !== 0) {
			$this->send_error(__('Security risk: Invalid module path detected.', 'wandtech-console'), 400);
		}

		// Final check to see if the directory physically exists before trying to delete it.
		if (!$wp_filesystem->is_dir($module_path)) {
			$this->send_error(__('Module directory not found. It may have already been deleted.', 'wandtech-console'), 404);
		}

		$result = $wp_filesystem->delete($module_path, true); // true for recursive delete.

		if ($result) {
			// On success, invalidate the module cache to reflect the change immediately.
			Wandtech_Console_Modules::clear_cache();
			$this->send_success(__('Module deleted successfully.', 'wandtech-console'));
		} else {
			$this->send_error(__('Could not delete the module folder. This is usually a file permission issue.', 'wandtech-console'), 500);
		}
	}

	/**
	 * Helper function to send a standardized JSON success response and terminate.
	 *
	 * @since  2.0.0
	 * @param  string $message The success message.
	 * @return void
	 */
	private function send_success( string $message ): void {
		wp_send_json_success([ 'message' => $message ]);
	}

	/**
	 * Helper function to send a standardized JSON error response and terminate.
	 *
	 * @since  2.0.0
	 * @param  string $message The error message.
	 * @param  int    $status  The HTTP status code to send.
	 * @return void
	 */
	private function send_error( string $message, int $status = 400 ): void {
		wp_send_json_error([ 'message' => $message ], $status);
	}
}