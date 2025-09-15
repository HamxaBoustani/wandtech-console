<?php
/**
 * Core Module: Module Installer
 *
 * Adds a UI for installing new optional modules from a .zip file via the console.
 * This is a system module and is always active.
 *
 * @package    Wandtech_Console
 * @subpackage Core_Modules
 * @author     Hamxa Boustani
 * @since      2.1.0
 * @version    3.2.1
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

// Ensure the module is loaded only once to prevent conflicts.
if (defined('WANDTECH_MODULE_INSTALLER_LOADED')) {
	return;
}
define('WANDTECH_MODULE_INSTALLER_LOADED', true);

/**
 * Class Wandtech_Module_Installer
 *
 * Manages the entire module installation process, from rendering the UI
 * to securely handling the file upload and validation.
 */
final class Wandtech_Module_Installer {

	/**
	 * Constructor. Hooks into the main console framework.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_action('wandtech_console_module_manager_actions', [ $this, 'render_install_button' ]);
		add_action('wp_ajax_wandtech_console_install_module', [ $this, 'handle_install_module_ajax' ]);
		add_filter('wandtech_console_admin_js_data', [ $this, 'add_installer_nonce' ]);
	}

	/**
	 * Adds the installer nonce to the data passed to admin.js.
	 *
	 * @since  2.1.0
	 * @param  array $data The existing JS data array.
	 * @return array The modified array with the installer nonce.
	 */
	public function add_installer_nonce( array $data ): array {
		$data['nonce_install'] = wp_create_nonce('wandtech_console_install_nonce');
		return $data;
	}

	/**
	 * Renders the "Install Module" button in the module manager header.
	 *
	 * @since  2.1.0
	 * @return void
	 */
	public function render_install_button(): void {
		?>
		<button type="button" class="button button-primary" id="install-module-button">
			<span class="dashicons dashicons-upload" style="margin-top: 4px;"></span>
			<?php esc_html_e('Install Module', 'wandtech-console'); ?>
		</button>
		<?php
		// The modal HTML is hooked to the admin_footer to ensure it's rendered at the end of the page.
		add_action('admin_footer', [ $this, 'render_modal_html' ]);
	}

	/**
	 * Prints the module installer modal HTML into the admin footer.
	 *
	 * @since  2.1.0
	 * @return void
	 */
	public function render_modal_html(): void {
		// Only render the modal on the console page to avoid unnecessary HTML elsewhere.
		$screen = get_current_screen();
		if (!$screen || 'toplevel_page_wandtech-console' !== $screen->id) {
			return;
		}
		?>
		<div id="install-module-modal" class="wandtech-modal-overlay" style="display: none;">
			<div class="wandtech-modal-content">
				<button type="button" class="wandtech-modal-close" aria-label="<?php esc_attr_e('Close', 'wandtech-console'); ?>">&times;</button>
				<h2><?php esc_html_e('Install a New Module', 'wandtech-console'); ?></h2>
				<p><?php esc_html_e('Upload a module in a .zip format to install it.', 'wandtech-console'); ?></p>
				
				<form id="install-module-form" enctype="multipart/form-data">
					<div class="wandtech-modal-body">
						<input type="file" id="module_zip_file" name="module_zip" accept=".zip" required>
						<p class="description">
							<?php esc_html_e('The module must be a correctly formatted .zip file.', 'wandtech-console'); ?>
						</p>
					</div>
					<div class="wandtech-modal-footer">
						<span class="spinner"></span>
						<button type="submit" class="button button-primary" id="install-module-submit" disabled>
							<?php esc_html_e('Install Now', 'wandtech-console'); ?>
						</button>
					</div>
				</form>
				<div class="wandtech-modal-notice" style="display: none;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the AJAX request for securely installing a module from a .zip file.
	 *
	 * @since  2.1.0
	 * @return void This method terminates execution with a JSON response.
	 */
	public function handle_install_module_ajax(): void {
		// Security Check: Verify nonce and user capabilities.
		if (!check_ajax_referer('wandtech_console_install_nonce', 'nonce', false) || !current_user_can('install_plugins')) {
			wp_send_json_error([ 'message' => __('Security check failed or insufficient permissions.', 'wandtech-console') ], 403);
		}

		// Isolate the superglobal access to a single, ignored line.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$uploaded_file = $_FILES['module_zip'] ?? null;

		// Input Validation: Now check the local variable thoroughly.
		if (
			empty($uploaded_file) ||
			!isset($uploaded_file['error'], $uploaded_file['name'], $uploaded_file['tmp_name']) ||
			UPLOAD_ERR_OK !== $uploaded_file['error']
		) {
			wp_send_json_error([ 'message' => __('No file was uploaded or an error occurred during upload.', 'wandtech-console') ], 400);
		}

		// Sanitize the filename before using it with pathinfo.
		$file_name = sanitize_file_name($uploaded_file['name']);
		if ('zip' !== pathinfo($file_name, PATHINFO_EXTENSION)) {
			wp_send_json_error([ 'message' => __('Invalid file type. Only .zip files are allowed.', 'wandtech-console') ], 415);
		}
		$file_tmp_name = $uploaded_file['tmp_name'];

		// Initialize WordPress Filesystem API.
		if (false === WP_Filesystem()) {
			wp_send_json_error([ 'message' => __('Could not initialize the WordPress Filesystem.', 'wandtech-console') ], 500);
		}

		$temp_dir = trailingslashit(get_temp_dir()) . 'wandtech_module_' . time();

		// Step 1: Unzip the uploaded file to a temporary directory.
		$result = unzip_file($file_tmp_name, $temp_dir);
		if (is_wp_error($result)) {
			$message = sprintf(
				/* translators: %s: The error message from the unzipping process. */
				__('Failed to unzip file: %s', 'wandtech-console'),
				$result->get_error_message()
			);
			$this->cleanup_and_fail($file_tmp_name, $temp_dir, $message);
		}

		// Step 2: Validate the structure and headers of the unzipped module.
		$this->validate_unzipped_module($file_tmp_name, $temp_dir);

		// Step 3: Move the validated module to its final destination.
		$module_slug = $this->get_unzipped_module_slug($temp_dir);
		$final_dest  = WANDTECH_CONSOLE_MODULES_PATH . $module_slug . '/';
		$move_result = move_dir(trailingslashit($temp_dir) . $module_slug, $final_dest);

		// Step 4: Final cleanup of temporary files.
		$this->cleanup_and_fail($file_tmp_name, $temp_dir); // No error message means it's a success cleanup.

		if (is_wp_error($move_result)) {
			wp_send_json_error([ 'message' => __('Could not move the module to the correct directory. This may be a file permission issue.', 'wandtech-console') ], 500);
		}

		// Step 5: Send a success response with the new module's data.
		$new_module_data = $this->get_new_module_data($final_dest, $module_slug);
		Wandtech_Console_Modules::clear_cache(); // Invalidate the module cache.

		wp_send_json_success([
			'message'    => __('Module installed successfully!', 'wandtech-console'),
			'new_module' => $new_module_data,
		]);
	}

	/**
	 * Gathers header and thumbnail data for a newly installed module.
	 *
	 * @since 3.2.0
	 * @param string $module_path The full path to the new module's directory.
	 * @param string $module_slug The slug of the new module.
	 * @return array An array of data for the new module.
	 */
	private function get_new_module_data( string $module_path, string $module_slug ): array {
		$main_file_path = $module_path . $module_slug . '.php';

		$module_data = get_file_data(
			$main_file_path,
			[
				'Name'          => 'Module Name',
				'Module URI'    => 'Module URI',
				'Description'   => 'Description',
				'Version'       => 'Version',
				'Author'        => 'Author',
				'Scope'         => 'Scope',
				'Settings Slug' => 'Settings Slug',
			]
		);
		$module_data['slug'] = $module_slug;

		// Find and add the thumbnail URL.
		$thumbnail_dir_path          = $module_path . 'assets/images/';
		$thumbnail_dir_url           = WANDTECH_CONSOLE_MODULES_URL . $module_slug . '/assets/images/';
		$module_data['thumbnail_url'] = '';

		foreach ([ 'png', 'jpg', 'jpeg', 'svg' ] as $format) {
			if (file_exists($thumbnail_dir_path . 'thumbnail.' . $format)) {
				$module_data['thumbnail_url'] = $thumbnail_dir_url . 'thumbnail.' . $format;
				break;
			}
		}

		return $module_data;
	}

	/**
	 * Validates the structure and headers of an unzipped module in a temporary directory.
	 *
	 * @since  2.1.0
	 * @param  string $tmp_zip_file Path to the temporary zip file for cleanup.
	 * @param  string $tmp_dir      Path to the temporary directory where the module was unzipped.
	 * @return void This method terminates with an error on validation failure.
	 */
	private function validate_unzipped_module( string $tmp_zip_file, string $tmp_dir ): void {
		global $wp_filesystem;

		$module_slug = $this->get_unzipped_module_slug($tmp_dir);
		if (empty($module_slug)) {
			$this->cleanup_and_fail($tmp_zip_file, $tmp_dir, __('The ZIP file does not contain a valid module directory structure.', 'wandtech-console'));
		}

		// Check if a module with the same slug already exists.
		if ($wp_filesystem->exists(WANDTECH_CONSOLE_MODULES_PATH . $module_slug . '/')) {
			$message = sprintf(
				/* translators: %s: The module slug that already exists. */
				__('A module with the slug "%s" already exists. Please delete it first if you want to reinstall.', 'wandtech-console'),
				$module_slug
			);
			$this->cleanup_and_fail($tmp_zip_file, $tmp_dir, $message, 409);
		}

		// Check for the main module file.
		$main_module_file_path = trailingslashit($tmp_dir) . trailingslashit($module_slug) . $module_slug . '.php';
		if (!$wp_filesystem->exists($main_module_file_path)) {
			$message = sprintf(
				/* translators: %s: The name of the missing required file (e.g., "my-module.php"). */
				__('Invalid module structure. The required file "%s" is missing.', 'wandtech-console'),
				$module_slug . '.php'
			);
			$this->cleanup_and_fail($tmp_zip_file, $tmp_dir, $message);
		}

		// Validate required headers.
		$header_data  = get_file_data($main_module_file_path, [ 'Name' => 'Module Name', 'Scope' => 'Scope' ]);
		$scope        = strtolower(trim($header_data['Scope'] ?? ''));
		$valid_scopes = [ 'admin', 'frontend', 'all' ];

		if (empty($header_data['Name'])) {
			$this->cleanup_and_fail($tmp_zip_file, $tmp_dir, __('This does not appear to be a valid WandTech module. The "Module Name" header is missing.', 'wandtech-console'));
		}
		if (empty($scope) || !in_array($scope, $valid_scopes, true)) {
			$message = sprintf(
				/* translators: %s: A code block showing the valid scope options. */
				__('Installation failed. The "Scope" header is missing or invalid. Please use one of: %s.', 'wandtech-console'),
				'<code>admin</code>, <code>frontend</code>, <code>all</code>'
			);
			$this->cleanup_and_fail($tmp_zip_file, $tmp_dir, $message);
		}
	}

	/**
	 * Gets the primary directory name (slug) from an unzipped temporary folder.
	 *
	 * Assumes the ZIP file contains a single root directory for the module.
	 *
	 * @since  2.1.0
	 * @param  string $tmp_dir The path to the temporary directory.
	 * @return string The found directory name, or an empty string.
	 */
	private function get_unzipped_module_slug( string $tmp_dir ): string {
		global $wp_filesystem;

		$unzipped_contents = $wp_filesystem->dirlist($tmp_dir);
		if (empty($unzipped_contents)) {
			return '';
		}

		// Find the first (and should be only) directory in the unzipped contents.
		foreach ($unzipped_contents as $name => $details) {
			if ('d' === $details['type']) {
				return rtrim($name, '/');
			}
		}
		return '';
	}

	/**
	 * Cleans up temporary files and optionally sends a JSON error response.
	 *
	 * @since  2.1.0
	 * @param  string      $tmp_zip_file Path to the temporary zip file to delete.
	 * @param  string      $tmp_dir      Path to the temporary directory to delete.
	 * @param  string|null $message      Optional. If provided, an error response is sent with this message.
	 * @param  int         $status       Optional. The HTTP status code for the error response.
	 * @return void This method may terminate execution.
	 */
	private function cleanup_and_fail( string $tmp_zip_file, string $tmp_dir, ?string $message = null, int $status = 400 ): void {
		global $wp_filesystem;
		if ($wp_filesystem) {
			$wp_filesystem->delete($tmp_zip_file, false, 'f');
			$wp_filesystem->delete($tmp_dir, true);
		}

		if ($message) {
			wp_send_json_error([ 'message' => $message ], $status);
		}
	}
}

// Instantiate the class to register its hooks.
new Wandtech_Module_Installer();