<?php
/**
 * Core Module: Module Scaffolder
 *
 * Adds a UI to quickly create a new blank module from a boilerplate template.
 * This tool is only available when Developer Mode is enabled.
 *
 * @package    Wandtech_Console
 * @subpackage Core_Modules
 * @author     Hamxa Boustani
 * @since      2.3.0
 * @version    3.2.1
 *
 * Developer Mode: true
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

// Ensure the module is loaded only once to prevent conflicts.
if (defined('WANDTECH_MODULE_SCAFFOLDER_LOADED')) {
	return;
}
define('WANDTECH_MODULE_SCAFFOLDER_LOADED', true);

/**
 * Class Wandtech_Module_Scaffolder.
 *
 * Manages the UI and AJAX logic for creating new module skeletons.
 */
final class Wandtech_Module_Scaffolder {

	/**
	 * Constructor. Registers hooks for the scaffolder UI and AJAX handler.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		add_action('wandtech_console_module_manager_actions', [ $this, 'render_scaffold_button' ], 15);
		add_action('admin_footer', [ $this, 'render_modal_html' ]);
		add_action('wp_ajax_wandtech_console_scaffold_module', [ $this, 'handle_scaffold_module_ajax' ]);
	}

	/**
	 * Renders the "Create Module" button in the module manager header.
	 *
	 * This button is only rendered if the current user has the capability to install plugins.
	 *
	 * @since  2.3.0
	 * @return void
	 */
	public function render_scaffold_button(): void {
		if (!current_user_can('install_plugins')) {
			return;
		}
		?>
		<button type="button" class="button button-secondary" id="scaffold-module-button">
			<span class="dashicons dashicons-plus-alt" style="margin-top: 4px;"></span>
			<?php esc_html_e('Create Module', 'wandtech-console'); ?>
		</button>
		<?php
	}

	/**
	 * Renders the modal HTML for the scaffolder in the admin footer.
	 *
	 * @since  2.3.0
	 * @return void
	 */
	public function render_modal_html(): void {
		// Only render the modal on the console page to avoid unnecessary HTML elsewhere.
		$screen = get_current_screen();
		if (!$screen || 'toplevel_page_wandtech-console' !== $screen->id) {
			return;
		}
		?>
		<div id="scaffold-module-modal" class="wandtech-modal-overlay" style="display: none;">
			<div class="wandtech-modal-content">
				<button type="button" class="wandtech-modal-close" aria-label="<?php esc_attr_e('Close', 'wandtech-console'); ?>">&times;</button>
				<h2><?php esc_html_e('Create a New Module', 'wandtech-console'); ?></h2>
				<p><?php esc_html_e('Fill out the details below. A new folder and boilerplate file will be created.', 'wandtech-console'); ?></p>
				
				<form id="scaffold-module-form">
					<div class="wandtech-modal-body">
						<p>
							<label for="new_module_slug"><strong><?php esc_html_e('Module Slug (Required)', 'wandtech-console'); ?></strong></label>
							<input type="text" id="new_module_slug" name="module_slug" class="large-text" 
								   placeholder="e.g., my-awesome-feature" required 
								   pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
							<em class="description"><?php esc_html_e('Use lowercase letters, numbers, and hyphens only.', 'wandtech-console'); ?></em>
						</p>
						<p>
							<label for="new_module_description"><strong><?php esc_html_e('Description (Required)', 'wandtech-console'); ?></strong></label>
							<input type="text" id="new_module_description" name="module_description" class="large-text" 
								   placeholder="A brief description of what this module does." required>
						</p>
						<p>
							<label><strong><?php esc_html_e('Scope (Required)', 'wandtech-console'); ?></strong></label>
							<select id="new_module_scope" name="module_scope" style="width:100%;">
								<option value="all" selected><?php esc_html_e('All: Loads everywhere (default)', 'wandtech-console'); ?></option>
								<option value="admin"><?php esc_html_e('Admin: Only loads in the WP dashboard', 'wandtech-console'); ?></option>
								<option value="frontend"><?php esc_html_e('Frontend: Only loads on the public-facing site', 'wandtech-console'); ?></option>
							</select>
						</p>
						<p>
							<label for="new_module_requires"><strong><?php esc_html_e('Requires Plugins (Optional)', 'wandtech-console'); ?></strong></label>
							<input type="text" id="new_module_requires" name="module_requires" class="large-text" 
								   placeholder="e.g., woocommerce/woocommerce.php">
							<em class="description"><?php esc_html_e('Comma-separated list of required plugin files.', 'wandtech-console'); ?></em>
						</p>
					</div>
					<div class="wandtech-modal-footer">
						<span class="spinner"></span>
						<button type="submit" class="button button-primary" id="scaffold-module-submit" disabled>
							<?php esc_html_e('Create Now', 'wandtech-console'); ?>
						</button>
					</div>
				</form>
				<div class="wandtech-modal-notice" style="display: none;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the AJAX request for creating a new module.
	 *
	 * Validates input, creates the directory and file structure, generates the
	 * boilerplate content, and returns the new module's data to the client.
	 *
	 * @since  2.3.0
	 * @return void This method terminates execution with a JSON response.
	 */
	public function handle_scaffold_module_ajax(): void {
		// Security Check: Verify nonce and user capabilities.
		if (!check_ajax_referer('wandtech_console_scaffold_nonce', 'nonce', false) || !current_user_can('install_plugins')) {
			wp_send_json_error([ 'message' => __('Security check failed or insufficient permissions.', 'wandtech-console') ], 403);
		}

		// Unslash and then sanitize all inputs.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$slug        = isset($_POST['module_slug']) ? sanitize_key(wp_unslash($_POST['module_slug'])) : '';
		$description = isset($_POST['module_description']) ? sanitize_text_field(wp_unslash($_POST['module_description'])) : '';
		$scope       = isset($_POST['module_scope']) ? sanitize_key(wp_unslash($_POST['module_scope'])) : '';
		$requires    = isset($_POST['module_requires']) ? sanitize_text_field(wp_unslash($_POST['module_requires'])) : '';
		// phpcs:enable

		// Input Validation.
		if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
			wp_send_json_error([ 'message' => __('Invalid slug format. Use lowercase letters, numbers, and hyphens only.', 'wandtech-console') ], 400);
		}
		if (empty($description)) {
			wp_send_json_error([ 'message' => __('Description is a required field.', 'wandtech-console') ], 400);
		}
		if (empty($scope) || !in_array($scope, [ 'admin', 'frontend', 'all' ], true)) {
			wp_send_json_error([ 'message' => __('Invalid scope selected.', 'wandtech-console') ], 400);
		}

		// Initialize WordPress Filesystem.
		if (false === WP_Filesystem()) {
			wp_send_json_error([ 'message' => __('Could not initialize the WordPress Filesystem.', 'wandtech-console') ], 500);
		}
		global $wp_filesystem;

		// Create module directories.
		$new_module_path = WANDTECH_CONSOLE_MODULES_PATH . $slug;
		if ($wp_filesystem->exists($new_module_path)) {
			$message = sprintf(
				/* translators: %s: The module slug that already exists. */
				__('A module with the slug "%s" already exists.', 'wandtech-console'),
				$slug
			);
			wp_send_json_error([ 'message' => $message ], 409);
		}
		if (!$wp_filesystem->mkdir($new_module_path) || !$wp_filesystem->mkdir($new_module_path . '/languages')) {
			wp_send_json_error([ 'message' => __('Could not create the module directory. Check file permissions.', 'wandtech-console') ], 500);
		}

		// Prepare data for the boilerplate template.
		$current_user = wp_get_current_user();
		$author       = $current_user->display_name ?: 'Developer';
		$module_name  = ucwords(str_replace('-', ' ', $slug));

		$template_data = [
			'slug'        => $slug,
			'module_name' => $module_name,
			'description' => $description,
			'scope'       => $scope,
			'requires'    => $requires,
			'author'      => $author,
		];

		// Generate and write the main module file.
		$module_file_content = $this->generate_boilerplate_content($template_data);
		$module_file_path    = $new_module_path . '/' . $slug . '.php';

		if (!$wp_filesystem->put_contents($module_file_path, $module_file_content)) {
			$wp_filesystem->delete($new_module_path, true); // Clean up failed attempt.
			wp_send_json_error([ 'message' => __('Could not create the main module file. Check file permissions.', 'wandtech-console') ], 500);
		}

		// Prepare data for the successful JSON response.
		$new_module_data = [
			'slug'          => $slug,
			'Name'          => $module_name,
			'Description'   => $description,
			'Version'       => '1.0.0',
			'Author'        => $author,
			'Scope'         => $scope,
			'Module URI'    => '', // Empty by default.
			'Settings Slug' => '', // Empty by default.
			'thumbnail_url' => WANDTECH_CONSOLE_URL . 'assets/images/module-placeholder.svg', // Default placeholder.
		];

		// Invalidate the module cache to reflect the change immediately.
		Wandtech_Console_Modules::clear_cache();

		wp_send_json_success([
			'message'    => __('Module created successfully!', 'wandtech-console'),
			'new_module' => $new_module_data,
		]);
	}

	/**
	 * Generates the boilerplate PHP content for a new module file.
	 *
	 * This template is designed to enforce best practices from the start,
	 * including PHPDoc, type hinting, and proper structure.
	 *
	 * @since  2.3.0
	 * @param  array $data Data to populate the boilerplate template.
	 * @return string The generated PHP code as a string.
	 */
	private function generate_boilerplate_content( array $data ): string {
		$slug        = $data['slug'];
		$module_name = $data['module_name'];
		$description = $data['description'];
		$scope       = $data['scope'];
		$requires    = $data['requires'];
		$author      = $data['author'];

		// Prepare class and constant names based on the slug.
		$class_name_parts = array_map('ucfirst', explode('-', $slug));
		$class_name       = 'WANDTECH_' . implode('_', $class_name_parts) . '_Module';
		$constant_name    = 'WANDTECH_' . strtoupper(str_replace('-', '_', $slug)) . '_LOADED';

		// Conditionally add the 'Requires Plugins' header line.
		$requires_header = ! empty($requires) ? " * Requires Plugins:  {$requires}\n" : '';
		$module_uri      = "https://wandtech.ir/modules/{$slug}/"; // A more realistic default URI.

		// [FIXED] Replaced HEREDOC with standard string concatenation to comply with coding standards.
		$content  = "<?php\n";
		$content .= "/**\n";
		$content .= " * Module Name:       {$module_name}\n";
		$content .= " * Module URI:        {$module_uri}\n";
		$content .= " * Description:       {$description}\n";
		$content .= " * Version:           1.0.0\n";
		$content .= " * Author:            {$author}\n";
		$content .= " * Scope:             {$scope}\n";
		$content .= $requires_header;
		$content .= " * Text Domain:       {$slug}\n";
		$content .= " * Domain Path:       /languages/\n";
		$content .= " *\n";
		$content .= " * @package           Wandtech_Console_Modules\n";
		$content .= " * @author            {$author}\n";
		$content .= " * @version           1.0.0\n";
		$content .= " */\n\n";
		$content .= "// Exit if accessed directly to prevent direct script access.\n";
		$content .= "if (!defined('ABSPATH')) {\n\texit;\n}\n\n";
		$content .= "// Ensure the module is loaded only once to prevent conflicts.\n";
		$content .= "if (defined('{$constant_name}')) {\n\treturn;\n}\n";
		$content .= "define('{$constant_name}', true);\n\n";
		$content .= "/**\n * Class {$class_name}.\n *\n * Main class for the {$module_name} module.\n */\n";
		$content .= "final class {$class_name} {\n\n";
		$content .= "\t/**\n\t * The unique slug for this module.\n\t *\n\t * @since 1.0.0\n\t * @var   string\n\t */\n";
		$content .= "\tprivate string \$slug = '{$slug}';\n\n";
		$content .= "\t/**\n\t * Constructor.\n\t *\n\t * The best practice is to register all hooks here.\n\t *\n\t * @since 1.0.0\n\t */\n";
		$content .= "\tpublic function __construct() {\n";
		$content .= "\t\tadd_action('init', [ \$this, 'load_module_textdomain' ]);\n\n";
		$content .= "\t\t// Example hooks. Uncomment and use as needed.\n";
		$content .= "\t\t// add_action('wp_enqueue_scripts', [ \$this, 'enqueue_frontend_assets' ]);\n";
		$content .= "\t\t// add_action('admin_enqueue_scripts', [ \$this, 'enqueue_admin_assets' ]);\n";
		$content .= "\t}\n\n";
		$content .= "\t/**\n\t * Loads the module text domain for translation.\n\t *\n\t * @since 1.0.0\n\t * @return void\n\t */\n";
		$content .= "\tpublic function load_module_textdomain(): void {\n";
		$content .= "\t\tif (!defined('WANDTECH_CONSOLE_MODULES_PATH')) {\n\t\t\treturn;\n\t\t}\n\n";
		$content .= "\t\t\$domain      = \$this->slug;\n";
		$content .= "\t\t\$locale      = apply_filters('plugin_locale', get_locale(), \$domain);\n";
		$content .= "\t\t\$mofile      = \"{\$domain}-{\$locale}.mo\";\n";
		$content .= "\t\t\$mofile_path = WANDTECH_CONSOLE_MODULES_PATH . \"{\$this->slug}/languages/{\$mofile}\";\n\n";
		$content .= "\t\tload_textdomain(\$domain, \$mofile_path);\n";
		$content .= "\t}\n\n";
		$content .= "\t/*\n\t * Example of an asset enqueueing function for the frontend.\n\t *\n\t * @since 1.0.0\n\t * @return void\n\t */\n";
		$content .= "\t/*\n\tpublic function enqueue_frontend_assets(): void {\n";
		$content .= "\t\t// \$style_url = WANDTECH_CONSOLE_MODULES_URL . \"{\$this->slug}/assets/css/frontend.css\";\n";
		$content .= "\t\t// wp_enqueue_style(\"{\$this->slug}-frontend\", \$style_url, [], '1.0.0');\n\t}\n\t*/\n\n";
		$content .= "\t/*\n\t * Example of an asset enqueueing function for the admin area.\n\t *\n\t * @since 1.0.0\n\t * @param string \$hook_suffix The current admin page hook.\n\t * @return void\n\t */\n";
		$content .= "\t/*\n\tpublic function enqueue_admin_assets( string \$hook_suffix ): void {\n";
		$content .= "\t\t// Example: Load only on the WandTech Console page.\n";
		$content .= "\t\t// if ('toplevel_page_wandtech-console' !== \$hook_suffix) {\n\t\t//     return;\n\t\t// }\n";
		$content .= "\t\t// \$script_url = WANDTECH_CONSOLE_MODULES_URL . \"{\$this->slug}/assets/js/admin.js\";\n";
		$content .= "\t\t// wp_enqueue_script(\"{\$this->slug}-admin\", \$script_url, [ 'jquery' ], '1.0.0', true);\n\t}\n\t*/\n";
		$content .= "}\n\n";
		$content .= "// Begins execution of the module.\n";
		$content .= "new {$class_name}();\n";

		return $content;
	}
}

// Instantiate the class to register its hooks.
new Wandtech_Module_Scaffolder();