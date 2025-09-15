<?php
/**
 * Core Module: Settings Tab
 *
 * Adds a multi-section "Settings" tab to the WandTech Console, providing a
 * scalable and extensible way to manage plugin and module options.
 * This is a system module and is always active.
 *
 * @package    Wandtech_Console
 * @subpackage Core_Modules
 * @author     Hamxa Boustani
 * @since      2.2.0
 * @version    3.2.1
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

// Ensure the module is loaded only once to prevent conflicts.
if (defined('WANDTECH_SETTINGS_TAB_LOADED')) {
	return;
}
define('WANDTECH_SETTINGS_TAB_LOADED', true);

/**
 * Class Wandtech_Settings_Tab.
 *
 * Manages the registration, rendering, and data handling for the Settings tab.
 */
final class Wandtech_Settings_Tab {

	/**
	 * The key used to store all console settings in the wp_options table.
	 *
	 * @since 2.2.0
	 * @const string
	 */
	const OPTION_KEY = 'wandtech_console_settings';

	/**
	 * Holds the array of all saved settings for the console.
	 *
	 * @since 2.2.0
	 * @var   array
	 */
	private array $settings = [];

	/**
	 * Holds the registered and sorted sections for the settings page UI.
	 *
	 * @since 2.6.0
	 * @var   array
	 */
	private array $sections = [];

	/**
	 * Constructor. Loads settings and registers all necessary hooks.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		// Load settings once from the database to be used throughout the class.
		$this->settings = get_option(self::OPTION_KEY, []);

		// Hooks for the core functionality of the settings system.
		add_filter('wandtech_console_get_setting', [ $this, 'get_setting' ], 10, 2);
		add_filter('wandtech_console_register_tabs', [ $this, 'register_tab' ]);
		add_action('wp_ajax_wandtech_console_save_settings', [ $this, 'handle_save_settings_ajax' ]);

		// Register the default "General" settings section and its content hook.
		add_filter('wandtech_console_register_settings_sections', [ $this, 'register_general_section' ]);
	}

	/**
	 * Retrieves a specific setting value from the stored options.
	 *
	 * This method is the public API for other modules to safely access settings.
	 * It's hooked into the 'wandtech_console_get_setting' filter.
	 *
	 * @since  2.2.0
	 * @param  mixed  $default The default value to return if the key is not found.
	 * @param  string $key     The key of the setting to retrieve.
	 * @return mixed  The value of the setting or the provided default value.
	 */
	public function get_setting( $default, string $key ) {
		return $this->settings[ $key ] ?? $default;
	}

	/**
	 * Registers the "Settings" tab with the console's UI.
	 *
	 * @since  2.2.0
	 * @param  array $tabs The existing array of registered tabs.
	 * @return array The modified array of tabs including the "Settings" tab.
	 */
	public function register_tab( array $tabs ): array {
		$tabs['settings'] = [
			'title'    => __('Settings', 'wandtech-console'),
			'callback' => [ $this, 'render_content' ],
			'priority' => 90,
		];
		return $tabs;
	}

	/**
	 * Renders the main HTML structure for the "Settings" tab.
	 *
	 * @since  2.2.0
	 * @return void
	 */
	public function render_content(): void {
		$this->setup_sections();
		$first_section_slug = ! empty($this->sections) ? key($this->sections) : '';
		?>
		<div id="wandtech-settings-container" class="wandtech-settings-layout">
			<div class="settings-sidebar">
				<div class="settings-search-wrapper">
					<input type="search" id="wandtech-settings-search" placeholder="<?php esc_attr_e('Search settings...', 'wandtech-console'); ?>">
				</div>
				<ul class="settings-nav">
					<?php foreach ($this->sections as $slug => $section) : ?>
						<li class="<?php echo ($slug === $first_section_slug) ? 'active' : ''; ?>">
							<a href="#<?php echo esc_attr($slug); ?>"><?php echo esc_html($section['title']); ?></a>
						</li>
					<?php endforeach; ?>
					<li class="no-results-message" style="display:none;"><?php esc_html_e('No sections found.', 'wandtech-console'); ?></li>
				</ul>
			</div>
			<div class="settings-content-wrapper">
				<div class="settings-content">
					<?php foreach ($this->sections as $slug => $section) : ?>
						<div id="section-<?php echo esc_attr($slug); ?>" class="settings-section <?php echo ($slug === $first_section_slug) ? 'active' : ''; ?>">
							<?php
							if (is_callable($section['callback'])) {
								call_user_func($section['callback'], $this->settings);
							}
							?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="setting-row-footer">
					<span class="spinner"></span>
					<button type="button" class="button button-primary" id="save-wandtech-settings-button">
						<?php esc_html_e('Save Changes', 'wandtech-console'); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Gathers and sorts all registered settings sections.
	 *
	 * @since 3.2.0
	 * @return void
	 */
	private function setup_sections(): void {
		/**
		 * Filters the array of settings sections to be displayed in the Settings tab.
		 *
		 * @since 2.6.0
		 * @param array $sections An associative array of sections, where the key is the section slug
		 *                        and the value is an array with 'title', 'callback', and 'priority'.
		 */
		$registered_sections = apply_filters('wandtech_console_register_settings_sections', []);

		if (!is_array($registered_sections)) {
			$registered_sections = [];
		}

		// Sort sections by priority, allowing modules to control their order.
		uasort($registered_sections, fn( $a, $b ) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));
		$this->sections = $registered_sections;
	}

	/**
	 * Registers the default "General" settings section.
	 *
	 * @since  2.6.0
	 * @param  array $sections The existing array of settings sections.
	 * @return array The modified array of settings sections.
	 */
	public function register_general_section( array $sections ): array {
		$sections['general'] = [
			'title'    => __('General', 'wandtech-console'),
			'callback' => [ $this, 'render_general_section_content' ],
			'priority' => 10,
		];
		return $sections;
	}

	/**
	 * Renders the content for the "General" settings section.
	 * This acts as a container for core fields and the extension hook.
	 *
	 * @since  3.2.1
	 * @param array $settings The array of all saved WandTech Console settings.
	 * @return void
	 */
	public function render_general_section_content( array $settings ): void {
		// Render the core fields for this section.
		$this->render_general_settings_fields($settings);

		/**
		 * Fires at the end of the General settings section.
		 *
		 * Allows other modules to add their own fields to the General settings tab.
		 *
		 * @since 3.2.1
		 * @param array $settings The array of all saved settings.
		 */
		do_action('wandtech_console_after_general_settings', $settings);
	}

	/**
	 * Renders the HTML for the core fields in the "General" settings section.
	 *
	 * @since  2.2.0
	 * @param array $settings The array of all saved WandTech Console settings.
	 * @return void
	 */
	public function render_general_settings_fields( array $settings ): void {
		$dev_mode            = $settings['developer_mode_enabled'] ?? false;
		$enable_full_cleanup = $settings['enable_full_cleanup'] ?? false;
		?>
		<div class="setting-row-toggle">
			<div class="setting-row-header">
				<label class="switch">
					<input type="checkbox" id="developer_mode_enabled" name="developer_mode_enabled" <?php checked($dev_mode); ?>>
					<span class="slider"></span>
				</label>
				<h3><?php esc_html_e('Developer Mode', 'wandtech-console'); ?></h3>
			</div>
			<p class="description">
				<?php esc_html_e('Enables the loading of developer-specific system modules, such as the Module Scaffolder.', 'wandtech-console'); ?>
			</p>
		</div>
		
		<div class="setting-row-toggle">
			<div class="setting-row-header">
				<label class="switch">
					<input type="checkbox" id="enable_full_cleanup" name="enable_full_cleanup" <?php checked($enable_full_cleanup); ?>>
					<span class="slider"></span>
				</label>
				<h3><?php esc_html_e('Data Cleanup on Uninstall', 'wandtech-console'); ?></h3>
			</div>
			<p class="description">
				<?php esc_html_e('If enabled, all WandTech Console data will be permanently deleted from your database when the plugin is uninstalled.', 'wandtech-console'); ?><br>
				<strong><?php esc_html_e('Default behavior is to keep data for safety.', 'wandtech-console'); ?></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles the AJAX request for saving all settings.
	 *
	 * This method sanitizes the core settings and then passes the data through
	 * a filter to allow other modules to sanitize and save their own settings.
	 *
	 * @since  2.2.0
	 * @return void This method terminates execution with a JSON response.
	 */
	public function handle_save_settings_ajax(): void {
		// Security Check: Verify nonce and user capabilities.
		if (!check_ajax_referer('wandtech_console_settings_nonce', 'nonce', false) || !current_user_can('manage_options')) {
			wp_send_json_error([ 'message' => __('Security check failed.', 'wandtech-console') ], 403);
		}

		// Step 1: Safely retrieve and validate the input structure using filter_input.
		// This is the most secure method and satisfies the WPCS linter.
		$posted_data = filter_input(
			INPUT_POST,
			'settings',
			FILTER_DEFAULT,
			FILTER_REQUIRE_ARRAY
		);

		// If 'settings' is not a valid array or doesn't exist, default to an empty array.
		if (empty($posted_data)) {
			$posted_data = [];
		}

		// Step 2: WordPress automatically adds slashes, so we must unslash the sanitized input.
		$posted_data = wp_unslash($posted_data);

		// Step 3: Initialize the new settings array with existing values from the database.
		$new_settings = get_option(self::OPTION_KEY, []);

		// Step 4: Sanitize and update the core settings from the now-safe $posted_data.
		$new_settings['developer_mode_enabled'] = isset($posted_data['developer_mode_enabled']) && rest_sanitize_boolean($posted_data['developer_mode_enabled']);
		$new_settings['enable_full_cleanup']    = isset($posted_data['enable_full_cleanup']) && rest_sanitize_boolean($posted_data['enable_full_cleanup']);

		/**
		 * Filters the settings array before it is saved to the database.
		 *
		 * This is the primary extension point for modules to save their own settings.
		 * Modules should hook into this filter, sanitize their data from `$posted_data` (the unslashed array),
		 * and add it to the `$new_settings` array.
		 *
		 * @since 2.6.0
		 *
		 * @param array $new_settings The array of settings to be saved, already containing sanitized core settings.
		 * @param array $posted_data  The unslashed, but otherwise raw, array of settings from `$_POST['settings']`.
		 */
		$final_settings = apply_filters('wandtech_console_save_settings_data', $new_settings, $posted_data);

		// Step 5: Update the database with the fully sanitized settings.
		update_option(self::OPTION_KEY, $final_settings);

		wp_send_json_success([
			'message'  => __('Settings saved successfully.', 'wandtech-console'),
			'settings' => $final_settings,
		]);
	}
}

// Instantiate the class to register the tab and its hooks.
new Wandtech_Settings_Tab();