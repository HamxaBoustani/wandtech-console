<?php
/**
 * Handles all AJAX requests for the WandTech Console.
 * This class processes the activation and deactivation of modules.
 *
 * @package    Wandtech_Console
 */

if (!defined('ABSPATH')) exit;

class Wandtech_Console_Ajax {

    /**
     * A reference to the modules manager class.
     *
     * @var Wandtech_Console_Modules
     */
    private $modules_manager;

    /**
     * Constructor.
     *
     * @param Wandtech_Console_Modules $modules_manager An instance of the modules manager.
     */
    public function __construct(Wandtech_Console_Modules $modules_manager) {
        $this->modules_manager = $modules_manager;

        // Register the AJAX action hook. The action name must match the one sent from JavaScript.
        add_action('wp_ajax_wandtech_console_toggle_module', array($this, 'handle_toggle_module'));
    }

    /**
     * Handles the AJAX request to toggle a module's status (activate/deactivate).
     */
    public function handle_toggle_module() {
        // 1. Security Check: Verify the nonce sent from the client.
        if (!check_ajax_referer('wandtech_console_module_nonce', 'nonce', false)) {
            wp_send_json_error(
                ['message' => __('Security check failed. Please refresh the page and try again.', 'wandtech-console')],
                403 // Forbidden status code
            );
        }

        // 2. Permission Check: Ensure the current user has the required capabilities.
        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('You do not have permission to perform this action.', 'wandtech-console')],
                403 // Forbidden status code
            );
        }

        // 3. Sanitize Input: Get and sanitize the module slug and its desired status.
        $slug = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';
        $status_is_active = isset($_POST['status']) && $_POST['status'] === 'true';

        if (empty($slug)) {
            wp_send_json_error(
                ['message' => __('Invalid module specified.', 'wandtech-console')],
                400 // Bad Request status code
            );
        }

        // 4. Process the request.
        $result = false;
        if ($status_is_active) {
            $result = $this->modules_manager->activate_module($slug);
        } else {
            $result = $this->modules_manager->deactivate_module($slug);
        }

        // 5. Send the JSON response.
        if ($result) {
            wp_send_json_success([
                'message' => __('Module status updated successfully.', 'wandtech-console')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Could not update module status in the database.', 'wandtech-console')
            ]);
        }
    }
}