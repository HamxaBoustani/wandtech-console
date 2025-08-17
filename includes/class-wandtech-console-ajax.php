<?php
/**
 * Handles all AJAX requests for the WandTech Console.
 * This class processes activation, deactivation, installation, and deletion of modules.
 *
 * @package    Wandtech_Console
 */

if (!defined('ABSPATH')) exit;

class Wandtech_Console_Ajax {

    /**
     * A reference to the modules manager class.
     * @var Wandtech_Console_Modules
     */
    private $modules_manager;

    /**
     * Constructor. Registers all AJAX actions.
     * @param Wandtech_Console_Modules $modules_manager An instance of the modules manager.
     */
    public function __construct(Wandtech_Console_Modules $modules_manager) {
        $this->modules_manager = $modules_manager;
        add_action('wp_ajax_wandtech_console_toggle_module', [$this, 'handle_toggle_module']);
        add_action('wp_ajax_wandtech_console_install_module', [$this, 'handle_install_module']);
        add_action('wp_ajax_wandtech_console_delete_module', [$this, 'handle_delete_module']);
    }

    /**
     * Handles the secure installation of a module from a .zip file.
     */
    public function handle_install_module() {
        if (!check_ajax_referer('wandtech_console_install_nonce', 'nonce', false) || !current_user_can('install_plugins')) {
            $this->send_error(__('Security check failed or insufficient permissions.', 'wandtech-console'), 403);
        }
        if (!isset($_FILES['module_zip']) || $_FILES['module_zip']['error'] !== UPLOAD_ERR_OK) {
            $this->send_error(__('No file was uploaded or an error occurred.', 'wandtech-console'), 400);
        }
        if (pathinfo($_FILES['module_zip']['name'], PATHINFO_EXTENSION) !== 'zip') {
            $this->send_error(__('Invalid file type. Only .zip files are allowed.', 'wandtech-console'), 415);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        $result = unzip_file($_FILES['module_zip']['tmp_name'], WANDTECH_CONSOLE_PATH . 'modules/');
        $wp_filesystem->delete($_FILES['module_zip']['tmp_name']);

        if (is_wp_error($result)) {
            $this->send_error(sprintf(__('Failed to install module: %s', 'wandtech-console'), $result->get_error_message()), 500);
        } else {
            $this->send_success(__('Module installed successfully! The page will now reload.', 'wandtech-console'));
        }
    }

    /**
     * Handles the AJAX request to toggle a module's status.
     */
    public function handle_toggle_module() {
        if (!check_ajax_referer('wandtech_console_module_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            $this->send_error(__('Security check failed or insufficient permissions.', 'wandtech-console'), 403);
        }

        $slug = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';
        $available_modules = $this->modules_manager->get_all_modules();
        if (empty($slug) || !array_key_exists($slug, $available_modules)) {
            $this->send_error(__('The specified module is invalid.', 'wandtech-console'), 400);
        }

        $wants_to_activate = isset($_POST['status']) && $_POST['status'] === 'true';

        if ($wants_to_activate) {
            $dependency_error = $this->modules_manager->validate_dependencies_for_module($slug);
            if ($dependency_error) {
                $error_message = sprintf(
                    esc_html__('Cannot activate "%1$s". It requires: %2$s', 'wandtech-console'),
                    esc_html($dependency_error['name']),
                    '<strong>' . esc_html($dependency_error['required']) . '</strong>'
                );
                $this->send_error($error_message, 409);
            }
            $result = $this->modules_manager->activate_module($slug);
        } else {
            $result = $this->modules_manager->deactivate_module($slug);
        }

        if ($result) {
            $this->send_success(__('Module status updated successfully.', 'wandtech-console'));
        } else {
            $this->send_error(__('Could not update the module status.', 'wandtech-console'), 500);
        }
    }
    
    /**
     * Handles the secure deletion of a module directory.
     */
    public function handle_delete_module() {
        if (!check_ajax_referer('wandtech_console_delete_nonce', 'nonce', false) || !current_user_can('delete_plugins')) {
            $this->send_error(__('Security check failed or insufficient permissions.', 'wandtech-console'), 403);
        }

        $slug = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';
        $available_modules = $this->modules_manager->get_all_modules();
        if (empty($slug) || !array_key_exists($slug, $available_modules)) {
            $this->send_error(__('The specified module is invalid.', 'wandtech-console'), 400);
        }

        if ($this->modules_manager->is_module_active($slug)) {
            $this->send_error(__('Please deactivate the module before deleting it.', 'wandtech-console'), 409);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        
        $module_path = WANDTECH_CONSOLE_PATH . 'modules/' . $slug;
        
        // Final security check to ensure we are deleting within the modules directory.
        if (strpos(realpath($module_path), realpath(WANDTECH_CONSOLE_PATH . 'modules')) !== 0) {
            $this->send_error(__('Invalid module path detected.', 'wandtech-console'), 400);
        }

        $result = $wp_filesystem->delete($module_path, true); // true for recursive delete.

        if ($result) {
            $this->send_success(__('Module deleted successfully.', 'wandtech-console'));
        } else {
            $this->send_error(__('Could not delete the module folder. This is usually a file permission issue.', 'wandtech-console'), 500);
        }
    }

    /**
     * [HELPER] Sends a JSON success response.
     */
    private function send_success($message) {
        wp_send_json_success(['message' => $message]);
    }

    /**
     * [HELPER] Sends a JSON error response.
     */
    private function send_error($message, $status = 400) {
        wp_send_json_error(['message' => $message], $status);
    }
}