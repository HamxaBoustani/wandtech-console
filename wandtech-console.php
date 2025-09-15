<?php
/**
 * Plugin Name:       WandTech Console
 * Plugin URI:        https://wandtech.ir
 * Description:       A high-performance, developer-centric framework for adding modular functionality to WordPress.
 * Version:           3.2.0
 * Author:            Hamxa Boustani
 * Author URI:        https://github.com/HamxaBoustani/
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      6.8
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wandtech-console
 * Domain Path:       /languages
 *
 * @package           Wandtech_Console
 * @author            Hamxa Boustani
 * @copyright         Copyright (C) 2025, Hamxa Boustani
 * @version           3.2.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * The current version of the WandTech Console plugin.
 * Used for asset versioning and database updates.
 *
 * @since 1.0.0
 * @var string
 */
define('WANDTECH_CONSOLE_VERSION', '3.2.0');

/**
 * The absolute filesystem path to the main plugin directory.
 * Includes a trailing slash.
 * Example: /var/www/example.com/wp-content/plugins/wandtech-console/
 *
 * @since 1.0.0
 * @var string
 */
define('WANDTECH_CONSOLE_PATH', plugin_dir_path(__FILE__));

/**
 * The web-accessible URL to the main plugin directory.
 * Includes a trailing slash.
 * Example: https://example.com/wp-content/plugins/wandtech-console/
 *
 * @since 1.0.0
 * @var string
 */
define('WANDTECH_CONSOLE_URL', plugin_dir_url(__FILE__));

/**
 * The full path to the main plugin file.
 * Used for hooks and core WordPress functions.
 * Example: /var/www/example.com/wp-content/plugins/wandtech-console/wandtech-console.php
 *
 * @since 2.8.1
 * @var string
 */
define('WANDTECH_CONSOLE_MAIN_FILE', __FILE__);

/**
 * The plugin basename, used for WordPress hooks like `plugin_action_links`.
 * Example: "wandtech-console/wandtech-console.php"
 *
 * @since 2.8.1
 * @var string
 */
define('WANDTECH_CONSOLE_BASENAME', plugin_basename(__FILE__));

/**
 * The absolute filesystem path to the external modules directory.
 * Includes a trailing slash.
 * Example: /var/www/example.com/wp-content/modules/
 *
 * @since 3.0.0
 * @var string
 */
define('WANDTECH_CONSOLE_MODULES_PATH', WP_CONTENT_DIR . '/modules/');

/**
 * The web-accessible URL to the external modules directory.
 * Includes a trailing slash.
 * Example: https://example.com/wp-content/modules/
 *
 * @since 3.0.0
 * @var string
 */
define('WANDTECH_CONSOLE_MODULES_URL', content_url('modules/'));

// Require the main plugin class which acts as the orchestrator.
require_once WANDTECH_CONSOLE_PATH . 'includes/class-wandtech-console.php';

/**
 * The main function responsible for launching the plugin.
 *
 * This function instantiates the main Wandtech_Console class, effectively
 * starting the plugin and registering all its hooks.
 *
 * @since  1.0.0
 * @return void
 */
function wandtech_console_run(): void {
	Wandtech_Console::get_instance();
}

// Fire it up!
wandtech_console_run();