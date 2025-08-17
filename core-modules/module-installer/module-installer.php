<?php
/**
 * Core Module Name: Module Installer
 * Description:      Adds a UI for installing new optional modules from a .zip file via the console.
 * Version:          1.1.1
 * Author:           Hamxa
 * Scope:            admin
 */

if (!defined('ABSPATH')) exit;

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
class Wandtech_Module_Installer {

    /**
     * Constructor. Hooks into the main console framework.
     */
    public function __construct() {
        add_action('wandtech_console_module_manager_actions', [$this, 'render_install_button']);
        add_action('wp_ajax_wandtech_console_install_module', [$this, 'handle_install_module_ajax']);
        add_filter('wandtech_console_admin_js_data', [$this, 'add_installer_nonce']);
    }

    /**
     * Adds the installer nonce to the data passed to admin.js.
     * This ensures that the AJAX request is secure and can be verified.
     *
     * @param array $data The existing JS data array.
     * @return array The modified array with the installer nonce.
     */
    public function add_installer_nonce(array $data): array {
        $data['nonce_install'] = wp_create_nonce('wandtech_console_install_nonce');
        return $data;
    }

    /**
     * Renders the "Install Module" button in the module manager header.
     * Also hooks the modal HTML into the admin footer for better DOM placement.
     */
    public function render_install_button() {
        ?>
        <button type="button" class="button button-primary" id="install-module-button">
            <span class="dashicons dashicons-upload" style="margin-top: 4px;"></span>
            <?php esc_html_e('Install Module', 'wandtech-console'); ?>
        </button>
        <?php
        add_action('admin_footer', [$this, 'render_modal_html']);
    }
    
    /**
     * Prints the module installer modal HTML into the admin footer.
     * This is only rendered on the WandTech Console page.
     */
    public function render_modal_html() {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_wandtech-console') {
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
     * This method follows a multi-step validation process for maximum security.
     */
    public function handle_install_module_ajax() {
        // Step 1: Security checks for nonce and user capabilities.
        if (!check_ajax_referer('wandtech_console_install_nonce', 'nonce', false) || !current_user_can('install_plugins')) {
            wp_send_json_error(['message' => __('Security check failed or insufficient permissions.', 'wandtech-console')], 403);
        }

        // Step 2: Validate the uploaded file itself.
        if (!isset($_FILES['module_zip']) || $_FILES['module_zip']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('No file was uploaded or an error occurred during upload.', 'wandtech-console')], 400);
        }
        if (pathinfo($_FILES['module_zip']['name'], PATHINFO_EXTENSION) !== 'zip') {
            wp_send_json_error(['message' => __('Invalid file type. Only .zip files are allowed.', 'wandtech-console')], 415);
        }

        // Step 3: Initialize WordPress Filesystem API.
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        $file = $_FILES['module_zip'];
        $temp_dir = trailingslashit(get_temp_dir()) . 'wandtech_module_' . time();

        // Step 4: Unzip the file to a temporary, isolated directory.
        $result = unzip_file($file['tmp_name'], $temp_dir);
        if (is_wp_error($result)) {
            $this->cleanup_and_fail($file['tmp_name'], $temp_dir, sprintf(__('Failed to unzip file: %s', 'wandtech-console'), $result->get_error_message()));
        }

        // Step 5: Validate the structure and content of the unzipped folder.
        $this->validate_unzipped_module($file['tmp_name'], $temp_dir);
        
        // Step 6: If all validation passes, move the module to its final destination.
        $module_slug = $this->get_unzipped_module_slug($temp_dir);
        $final_dest = WANDTECH_CONSOLE_PATH . 'modules/' . $module_slug . '/';
        $move_result = move_dir(trailingslashit($temp_dir) . $module_slug, $final_dest);

        // Step 7: Final cleanup and response.
        $this->cleanup_and_fail($file['tmp_name'], $temp_dir); // Cleanup any remaining temp files.

        if (is_wp_error($move_result)) {
            wp_send_json_error(['message' => __('Could not move the module to the correct directory. This may be a file permission issue.', 'wandtech-console')], 500);
        } else {
            wp_send_json_success(['message' => __('Module installed successfully! The page will now reload.', 'wandtech-console')]);
        }
    }

    /**
     * [PRIVATE] Validates the structure and headers of an unzipped module in a temporary directory.
     *
     * @param string $tmp_zip_file Path to the temporary zip file for cleanup.
     * @param string $tmp_dir      Path to the temporary directory for cleanup.
     */
    private function validate_unzipped_module(string $tmp_zip_file, string $tmp_dir) {
        global $wp_filesystem;

        $module_slug = $this->get_unzipped_module_slug($tmp_dir);
        if (empty($module_slug)) {
            $this->cleanup_and_fail($tmp_zip_file, $tmp_dir, __('The ZIP file does not contain a valid module directory structure.', 'wandtech-console'));
        }

        $main_module_file_path = trailingslashit($tmp_dir) . trailingslashit($module_slug) . $module_slug . '.php';
        if (!$wp_filesystem->exists($main_module_file_path)) {
            $this->cleanup_and_fail($tmp_zip_file, $tmp_dir, sprintf(__('Invalid module structure. The required file "%s" is missing.', 'wandtech-console'), $module_slug . '.php'));
        }

        $header_data = get_file_data($main_module_file_path, ['Name' => 'Module Name', 'Scope' => 'Scope']);
        if (empty($header_data['Name'])) {
            $this->cleanup_and_fail($tmp_zip_file, $tmp_dir, __('This does not appear to be a valid WandTech module. The "Module Name" header is missing.', 'wandtech-console'));
        }
        if (empty($header_data['Scope'])) {
            $this->cleanup_and_fail($tmp_zip_file, $tmp_dir, __('This does not appear to be a valid WandTech module. The "Scope" header is missing.', 'wandtech-console'));
        }
        
        $final_dest = WANDTECH_CONSOLE_PATH . 'modules/' . $module_slug . '/';
        if ($wp_filesystem->exists($final_dest)) {
            $this->cleanup_and_fail($tmp_zip_file, $tmp_dir, sprintf(__('A module with the slug "%s" already exists. Please delete it first if you want to reinstall.', 'wandtech-console'), $module_slug), 409);
        }
    }
    
    /**
     * [PRIVATE] Gets the primary directory name (slug) from an unzipped temporary folder.
     */
    private function get_unzipped_module_slug(string $tmp_dir): string {
        global $wp_filesystem;
        $unzipped_contents = $wp_filesystem->dirlist($tmp_dir);
        if (empty($unzipped_contents)) {
            return '';
        }
        foreach ($unzipped_contents as $name => $details) {
            if ($details['type'] === 'd') {
                return rtrim($name, '/');
            }
        }
        return '';
    }

    /**
     * [PRIVATE HELPER] Cleans up temporary files and, if a message is provided, sends a JSON error.
     */
    private function cleanup_and_fail(string $tmp_zip_file, string $tmp_dir, string $message = '', int $status = 400) {
        global $wp_filesystem;
        if ($wp_filesystem) {
            $wp_filesystem->delete($tmp_zip_file, false, 'f');
            $wp_filesystem->delete($tmp_dir, true);
        }
        if ($message) {
            wp_send_json_error(['message' => $message], $status);
        }
    }
}

new Wandtech_Module_Installer();