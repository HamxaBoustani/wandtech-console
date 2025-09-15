<?php
/**
 * Core Module: Console Dashboard
 *
 * Provides the main "Dashboard" tab for the WandTech Console, including system
 * statistics and various panels with technical environment information.
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
if (defined('WANDTECH_DASHBOARD_MODULE_LOADED')) {
	return;
}
define('WANDTECH_DASHBOARD_MODULE_LOADED', true);

/**
 * Class Wandtech_Dashboard_Tab.
 *
 * Registers and renders the content for the "Dashboard" tab in the console.
 */
final class Wandtech_Dashboard_Tab {

	/**
	 * Constructor. Hooks into the framework's tab registration system.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter('wandtech_console_register_tabs', [ $this, 'register_tab' ]);
	}

	/**
	 * Registers the "Dashboard" tab with the console's UI.
	 *
	 * @since  2.1.0
	 * @param  array $tabs The existing array of registered tabs.
	 * @return array The modified array of tabs including the "Dashboard" tab.
	 */
	public function register_tab( array $tabs ): array {
		$tabs['dashboard'] = [
			'title'    => __('Dashboard', 'wandtech-console'),
			'callback' => [ $this, 'render_content' ],
			'priority' => 10,
		];
		return $tabs;
	}

	/**
	 * Renders the main HTML content for the "Dashboard" tab.
	 *
	 * This is the primary callback function specified in the tab registration.
	 *
	 * @since  2.1.0
	 * @return void
	 */
	public function render_content(): void {
		// Initialize the WP_Filesystem to be available for permission checks.
		if (false === WP_Filesystem()) {
			// Display an error if the filesystem cannot be initialized, but don't crash.
			echo '<div class="notice notice-error is-alt"><p>' . esc_html__('Could not initialize the WordPress Filesystem. File permissions cannot be checked.', 'wandtech-console') . '</p></div>';
		}
		?>
		<div class="wandtech-dashboard-grid">
			<div class="dashboard-main-content">
				<?php
				$this->render_welcome_and_stats();
				$this->render_server_info();
				?>
			</div>
			<div class="dashboard-sidebar">
				<?php
				$this->render_wordpress_constants();
				$this->render_file_permissions();
				$this->render_key_file_status();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the welcome message and module statistics section.
	 *
	 * @since  2.1.0
	 * @return void
	 */
	private function render_welcome_and_stats(): void {
		$modules_manager = Wandtech_Console::get_instance()->modules;
		$all_modules     = $modules_manager->get_all_modules();
		$active_modules  = $modules_manager->get_active_modules();

		$total_count    = count($all_modules);
		$active_count   = count($active_modules);
		$inactive_count = $total_count - $active_count;

		// Calculate the number of admin-only modules for the performance metric.
		$admin_only_active_modules = 0;
		foreach ($active_modules as $slug) {
			if (isset($all_modules[ $slug ]['Scope']) && 'admin' === strtolower(trim($all_modules[ $slug ]['Scope']))) {
				$admin_only_active_modules++;
			}
		}
		?>
		<div class="wandtech-dashboard-welcome">
			<h3><?php esc_html_e('Welcome to your Control Center', 'wandtech-console'); ?></h3>
			<p><?php esc_html_e('This is the central hub for all WandTech features. Manage powerful tools that enhance your site, all from one place.', 'wandtech-console'); ?></p>
		</div>

		<h4><?php esc_html_e('System Status At a Glance', 'wandtech-console'); ?></h4>
		<div class="wandtech-stats-grid">
			<div class="stat-card">
				<span class="dashicons dashicons-admin-plugins"></span>
				<div class="stat-content">
					<span class="stat-number" id="dashboard-stat-total"><?php echo esc_html($total_count); ?></span>
					<span class="stat-label"><?php esc_html_e('Total Modules', 'wandtech-console'); ?></span>
				</div>
			</div>
			<div class="stat-card active">
				<span class="dashicons dashicons-yes-alt"></span>
				<div class="stat-content">
					<span class="stat-number" id="dashboard-stat-active"><?php echo esc_html($active_count); ?></span>
					<span class="stat-label"><?php esc_html_e('Active Modules', 'wandtech-console'); ?></span>
				</div>
			</div>
			<div class="stat-card inactive">
				<span class="dashicons dashicons-dismiss"></span>
				<div class="stat-content">
					<span class="stat-number" id="dashboard-stat-inactive"><?php echo esc_html($inactive_count); ?></span>
					<span class="stat-label"><?php esc_html_e('Inactive Modules', 'wandtech-console'); ?></span>
				</div>
			</div>
			<div class="stat-card performance">
				<span class="dashicons dashicons-performance"></span>
				<div class="stat-content">
					<span class="stat-number" id="dashboard-stat-performance"><?php echo esc_html($admin_only_active_modules); ?></span>
					<span class="stat-label"><?php esc_html_e('Performance Optimizations', 'wandtech-console'); ?></span>
				</div>
			</div>
		</div>
		<div class="wandtech-performance-explainer" id="dashboard-performance-explainer" style="<?php echo $admin_only_active_modules > 0 ? '' : 'display:none;'; ?>">
			<p>
				<span class="dashicons dashicons-info-outline"></span>
				<span id="dashboard-performance-text">
					<?php
					printf(
						esc_html(
							/* translators: %d: The number of admin-only modules. */
							_n(
								'To keep your site fast for visitors, we prevented %d admin-specific module from loading on the frontend.',
								'To keep your site fast for visitors, we prevented %d admin-specific modules from loading on the frontend.',
								$admin_only_active_modules,
								'wandtech-console'
							)
						),
						absint($admin_only_active_modules)
					);
					?>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Renders the Server Environment information box.
	 *
	 * @since  2.4.0
	 * @return void
	 */
	private function render_server_info(): void {
		global $wpdb;

		// [FIXED] Safely retrieve and sanitize the server software string.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : __('Unknown', 'wandtech-console');

		// Data is gathered here and passed to a generic render method to promote reusability.
		$server_info = [
			'PHP Version'            => PHP_VERSION,
			'WordPress Version'      => get_bloginfo('version'),
			'Web Server'             => $server_software,
			'Database Version'       => $wpdb->db_version(),
			'PHP Memory Limit'       => ini_get('memory_limit'),
			'PHP Max Execution Time' => ini_get('max_execution_time') . 's',
		];

		$this->render_info_box(__('Server Environment', 'wandtech-console'), $server_info);
	}

	/**
	 * Renders the WordPress Constants information box.
	 *
	 * @since  2.4.0
	 * @return void
	 */
	private function render_wordpress_constants(): void {
		$constants = [
			'WP_DEBUG'            => defined('WP_DEBUG') && WP_DEBUG,
			'WP_DEBUG_LOG'        => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
			'WP_MEMORY_LIMIT'     => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : __('Not Set', 'wandtech-console'),
			'WP_MAX_MEMORY_LIMIT' => defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : __('Not Set', 'wandtech-console'),
			'DISABLE_WP_CRON'     => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
		];

		$this->render_info_box(__('WordPress Constants', 'wandtech-console'), $constants);
	}

	/**
	 * Renders the File System Permissions information box using WP_Filesystem.
	 *
	 * @since  2.4.0
	 * @return void
	 */
	private function render_file_permissions(): void {
		$upload_dir = wp_upload_dir();

		$paths = [
			'WP_CONTENT_DIR'       => WP_CONTENT_DIR,
			'Plugins Directory'    => WP_PLUGIN_DIR,
			'MU-Plugins Directory' => WPMU_PLUGIN_DIR,
			'Uploads Directory'    => $upload_dir['basedir'],
			'Modules Directory'    => WANDTECH_CONSOLE_MODULES_PATH,
		];

		$this->render_info_box(__('File System Permissions', 'wandtech-console'), $paths, 'permissions');
	}

	/**
	 * Renders the Key File Status information box.
	 *
	 * @since  2.4.0
	 * @return void
	 */
	private function render_key_file_status(): void {
		$files = [
			'wp-config.php' => ABSPATH . 'wp-config.php',
			'.htaccess'     => ABSPATH . '.htaccess',
			'robots.txt'    => ABSPATH . 'robots.txt',
		];

		// [FIXED] Safely retrieve and sanitize the server software string.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))) : '';
		
		// The .htaccess check is only relevant for Apache/LiteSpeed-based servers.
		if (!str_contains($server_software, 'apache') && !str_contains($server_software, 'litespeed')) {
			unset($files['.htaccess']);
		}

		$this->render_info_box(__('Key File Status', 'wandtech-console'), $files, 'files');
	}

	/**
	 * A generic, reusable function to render an information box with a table.
	 *
	 * @since 3.2.0
	 *
	 * @param string $title The title of the information box.
	 * @param array  $data  An associative array of data to display in the table.
	 * @param string $type  The type of data being displayed, for special rendering logic.
	 *                      Accepted values: 'default', 'permissions', 'files'.
	 * @return void
	 */
	private function render_info_box( string $title, array $data, string $type = 'default' ): void {
		?>
		<div class="dashboard-info-box">
			<h4><?php echo esc_html($title); ?></h4>
			<table class="wandtech-info-table">
				<tbody>
					<?php foreach ($data as $label => $value) : ?>
						<tr>
							<th>
								<?php echo esc_html($label); ?>
								<?php if ('permissions' === $type) : ?>
									<span class="path-subtitle"><?php echo esc_html(str_replace(ABSPATH, '', $value)); ?></span>
								<?php endif; ?>
							</th>
							<td class="info-value">
								<?php $this->render_info_value($value, $type); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders the value cell in an information table based on its type.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed  $value The value to render.
	 * @param string $type  The type of data, for special rendering logic.
	 * @return void
	 */
	private function render_info_value( $value, string $type ): void {
		if (is_bool($value)) {
			// Handles boolean values like WP_DEBUG.
			$status_class = $value ? 'is-enabled' : 'is-disabled';
			$status_text  = $value ? __('Enabled', 'wandtech-console') : __('Disabled', 'wandtech-console');
			echo '<span class="status-indicator ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
			return;
		}

		switch ($type) {
			case 'permissions':
				// [FIXED] Use WP_Filesystem for reliable permission checks.
				global $wp_filesystem;
				if (!$wp_filesystem) {
					echo '<span class="status-indicator is-disabled">' . esc_html__('Unknown', 'wandtech-console') . '</span>';
					return;
				}
				$is_writable  = $wp_filesystem->is_writable($value);
				$status_class = $is_writable ? 'is-enabled' : 'is-disabled';
				$status_text  = $is_writable ? __('Writable', 'wandtech-console') : __('Not Writable', 'wandtech-console');
				echo '<span class="status-indicator ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
				break;

			case 'files':
				$file_exists  = file_exists($value);
				$status_class = $file_exists ? 'is-enabled' : 'is-disabled';
				$status_text  = $file_exists ? __('Present', 'wandtech-console') : __('Missing', 'wandtech-console');
				echo '<span class="status-indicator ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
				break;

			default:
				echo esc_html($value);
				break;
		}
	}
}

// Instantiate the class to register the tab and its content.
new Wandtech_Dashboard_Tab();