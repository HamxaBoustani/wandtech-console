<?php
/**
 * Plugin Name:       WandTech Console
 * Plugin URI:        https://github.com/HamxaBoustani/wandtech-console/
 * Description:       A modular console for adding powerful features to WordPress.
 * Version:           1.0.0
 * Author:            Hamxa
 * Author URI:        https://github.com/HamxaBoustani/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wandtech-console
 * Domain Path:       /languages
 */

if (!defined('WPINC')) die;

// Define plugin constants with the new branding
define('WANDTECH_CONSOLE_VERSION', '2.0.0');
define('WANDTECH_CONSOLE_PATH', plugin_dir_path(__FILE__));
define('WANDTECH_CONSOLE_URL', plugin_dir_url(__FILE__));
define('WANDTECH_CONSOLE_MAIN_FILE', __FILE__);

// Include the main plugin class
require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console.php';

/**
 * Begins execution of the plugin.
 */
function wandtech_console_run() {
    Wandtech_Console::get_instance();
}

wandtech_console_run();