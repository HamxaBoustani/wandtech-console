<?php
/**
 * Handles the admin area UI "container" for the WandTech Console.
 *
 * This class is responsible for creating the admin menu, enqueuing assets,
 * and rendering the main page structure where tabs are displayed.
 *
 * @package    Wandtech_Console
 * @subpackage Admin
 * @author     Hamxa Boustani
 * @since      1.0.0
 * @version    3.2.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Wandtech_Console_Admin.
 *
 * Manages the admin-facing aspects of the plugin, including the main menu page,
 * asset loading, and integration with the WordPress admin interface.
 */
final class Wandtech_Console_Admin {

	/**
	 * Holds the registered and sorted tabs for the console page.
	 *
	 * @since 2.0.0
	 * @var   array
	 */
	private array $tabs = [];

	/**
	 * Constructor. Registers all admin-related hooks.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action('admin_menu', [ $this, 'add_plugin_page' ]);
		add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
		add_filter('admin_footer_text', [ $this, 'remove_admin_footer_text' ], 99);
		add_filter('update_footer', [ $this, 'remove_admin_footer_version' ], 99);
		add_filter('plugin_action_links_' . WANDTECH_CONSOLE_BASENAME, [ $this, 'add_console_action_link' ]);
	}

	/**
	 * Adds a "Console" link to the plugin's action links on the plugins page.
	 *
	 * @since  3.0.0
	 * @param  string[] $links The existing array of action links.
	 * @return string[] The modified array of links with the "Console" link added.
	 */
	public function add_console_action_link( array $links ): array {
		$console_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url(admin_url('admin.php?page=wandtech-console')),
			esc_html__('Console', 'wandtech-console')
		);
		// Add the link to the beginning of the array for prominence.
		array_unshift($links, $console_link);
		return $links;
	}

	/**
	 * Gets the SVG icon as a base64-encoded data URI for the admin menu.
	 *
	 * Caches the result in a static variable to avoid file operations on subsequent calls.
	 *
	 * @since  2.1.1
	 * @return string The data URI for the SVG icon or a Dashicon fallback string.
	 */
	private function get_menu_icon_svg(): string {
		static $svg_icon = null;

		// Return from static cache if already processed.
		if (null !== $svg_icon) {
			return $svg_icon;
		}

		$icon_path = WANDTECH_CONSOLE_PATH . 'assets/images/wandtech-logo.svg';
		if (!file_exists($icon_path)) {
			$svg_icon = 'dashicons-plugins-checked';
			return $svg_icon;
		}

		$svg_content = file_get_contents($icon_path);
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$svg_icon = 'data:image/svg+xml;base64,' . base64_encode($svg_content);

		return $svg_icon;
	}

	/**
	 * Adds the main plugin page to the WordPress admin menu.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function add_plugin_page(): void {
		add_menu_page(
			__('WandTech Console', 'wandtech-console'),
			__('WandTech', 'wandtech-console'),
			'manage_options',
			'wandtech-console',
			[ $this, 'create_admin_page' ],
			$this->get_menu_icon_svg(),
			79 // Position below "Settings".
		);
	}

	/**
	 * Enqueues scripts and styles only on the WandTech Console page.
	 *
	 * @since  2.0.0
	 * @param  string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Optimization: Only load assets on our specific admin page.
		if ('toplevel_page_wandtech-console' !== $hook_suffix) {
			return;
		}

		wp_enqueue_style(
			'wandtech-console-admin',
			WANDTECH_CONSOLE_URL . 'assets/css/admin.css',
			[],
			WANDTECH_CONSOLE_VERSION
		);

		wp_enqueue_script(
			'wandtech-console-admin',
			WANDTECH_CONSOLE_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WANDTECH_CONSOLE_VERSION,
			true
		);

		// Prepare and localize data for JavaScript.
		$js_data = $this->get_localized_js_data();
		wp_localize_script('wandtech-console-admin', 'wandtech_console_ajax', $js_data);
	}

	/**
	 * Gathers and structures all data to be passed to the admin JavaScript.
	 *
	 * @since 3.2.0
	 * @return array The array of data for `wp_localize_script`.
	 */
	private function get_localized_js_data(): array {
		$settings = get_option('wandtech_console_settings', []);

		// Retrieve and then immediately delete one-time deactivation notices.
		$deactivation_notices = get_transient('wandtech_console_deactivation_notices');
		if ($deactivation_notices) {
			delete_transient('wandtech_console_deactivation_notices');
		}

		// Create a map of modules that have settings pages for the "smart reload" feature.
		$all_modules         = Wandtech_Console::get_instance()->modules->get_all_modules();
		$module_settings_map = [];
		foreach ($all_modules as $slug => $data) {
			if (!empty($data['Settings Slug'])) {
				$module_settings_map[ $slug ] = true;
			}
		}

		$js_data = [
			'ajax_url'             => admin_url('admin-ajax.php'),
			'plugin_url'           => WANDTECH_CONSOLE_URL,
			'nonce_toggle'         => wp_create_nonce('wandtech_console_module_nonce'),
			'nonce_delete'         => wp_create_nonce('wandtech_console_delete_nonce'),
			'nonce_settings'       => wp_create_nonce('wandtech_console_settings_nonce'),
			'nonce_scaffold'       => wp_create_nonce('wandtech_console_scaffold_nonce'),
			'generic_error'        => __('An unexpected error occurred. Please try again.', 'wandtech-console'),
			'installing_text'      => __('Installing...', 'wandtech-console'),
			'install_now_text'     => __('Install Now', 'wandtech-console'),
			'creating_text'        => __('Creating...', 'wandtech-console'),
			'create_now_text'      => __('Create Now', 'wandtech-console'),
			'settings'             => $settings,
			'deactivation_notices' => $deactivation_notices ?: [],
			'module_settings_map'  => $module_settings_map,
		];

		/**
		 * Filters the data passed to the admin JavaScript.
		 *
		 * This allows other modules to add their own data or nonces for JS consumption.
		 *
		 * @since 2.5.0
		 * @param array $js_data The array of data to be localized.
		 */
		return apply_filters('wandtech_console_admin_js_data', $js_data);
	}

	/**
	 * Checks if the current admin screen is the WandTech Console page.
	 *
	 * @since  2.1.1
	 * @return bool True if it's the console page, false otherwise.
	 */
	private function is_console_page(): bool {
		if (!function_exists('get_current_screen')) {
			return false;
		}
		$screen = get_current_screen();
		return ($screen && 'toplevel_page_wandtech-console' === $screen->id);
	}

	/**
	 * Conditionally removes the "Thank you for creating..." text from the footer.
	 *
	 * @since  2.1.1
	 * @param  string $footer_text The original footer text.
	 * @return string The modified footer text, or an empty string on the console page.
	 */
	public function remove_admin_footer_text( string $footer_text ): string {
		return $this->is_console_page() ? '' : $footer_text;
	}

	/**
	 * Conditionally removes the WordPress version number from the footer.
	 *
	 * @since  2.1.1
	 * @param  string $footer_version The original footer version text.
	 * @return string The modified footer version text, or an empty string on the console page.
	 */
	public function remove_admin_footer_version( string $footer_version ): string {
		return $this->is_console_page() ? '' : $footer_version;
	}

	/**
	 * Sets up and sorts the tabs for the console page.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	private function setup_tabs(): void {
		/**
		 * Filters the array of tabs to be displayed in the WandTech Console.
		 *
		 * @since 2.0.0
		 * @param array $tabs An associative array of tabs, where the key is the tab slug
		 *                    and the value is an array with 'title', 'callback', and 'priority'.
		 */
		$registered_tabs = apply_filters('wandtech_console_register_tabs', []);

		if (!is_array($registered_tabs)) {
			$registered_tabs = [];
		}
		uasort($registered_tabs, fn( $a, $b ) => ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10));
		$this->tabs = $registered_tabs;
	}

	/**
	 * Renders the main admin page structure for the WandTech Console.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function create_admin_page(): void {
		$this->setup_tabs();
		?>
		<div class="wrap wandtech-wrap">
			<div class="wandtech-header-logo">
				<?php
				$logo_path = WANDTECH_CONSOLE_PATH . 'assets/images/wandtech-logo.svg';
				if (file_exists($logo_path)) {
					// The SVG is considered safe as it's a local file within the plugin.
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo file_get_contents($logo_path);
				}
				?>
			</div>
			<h1><?php esc_html_e('WandTech Console', 'wandtech-console'); ?></h1>
			<p class="wandtech-subtitle"><?php esc_html_e('The elegant, high-performance way to add and manage powerful features.', 'wandtech-console'); ?></p>
			
			<div id="wandtech-console-notices"></div>

			<?php if (!empty($this->tabs)) : ?>
				<h2 class="nav-tab-wrapper">
					<?php
					$is_first_tab = true;
					foreach ($this->tabs as $slug => $tab) :
						$class = 'nav-tab' . ($is_first_tab ? ' nav-tab-active' : '');
						?>
						<a href="#<?php echo esc_attr($slug); ?>" class="<?php echo esc_attr($class); ?>" data-tab-slug="<?php echo esc_attr($slug); ?>">
							<?php echo esc_html($tab['title']); ?>
						</a>
						<?php
						$is_first_tab = false;
					endforeach;
					?>
				</h2>
				<?php
				$is_first_tab = true;
				foreach ($this->tabs as $slug => $tab) :
					$class = 'tab-content' . ($is_first_tab ? ' active' : '');
					?>
					<div id="<?php echo esc_attr($slug); ?>" class="<?php echo esc_attr($class); ?>">
						<?php
						if (is_callable($tab['callback'])) {
							call_user_func($tab['callback']);
						}
						?>
					</div>
					<?php
					$is_first_tab = false;
				endforeach;
				?>
			<?php else : ?>
				<div class="notice notice-warning inline">
					<p>
						<strong><?php esc_html_e('No Console Modules Found', 'wandtech-console'); ?></strong><br>
						<?php esc_html_e('The WandTech Console is active, but no core modules were found to display the UI. Please ensure the `system` directory is present and not empty.', 'wandtech-console'); ?>
					</p>
				</div>
			<?php endif; ?>

			<footer class="wandtech-console-footer">
				<div class="footer-credit">
					<?php
					$author_link = '<a href="https://github.com/HamxaBoustani/" target="_blank" rel="noopener noreferrer">Hamxa Boustani</a>';
					
					$footer_html = sprintf(
						/* translators: 1: Heart icon HTML span, 2: Author link HTML anchor. */
						__('Made with %1$s by %2$s', 'wandtech-console'),
						'<span class="dashicons dashicons-heart"></span>',
						$author_link
					);

					echo wp_kses(
						$footer_html,
						[
							'span' => [ 'class' => true ],
							'a'    => [
								'href'   => true,
								'target' => true,
								'rel'    => true,
							],
						]
					);
					?>
				</div>
				<div class="footer-version">
					<?php
					/* translators: %s: Plugin version number. */
					printf(esc_html__('Version %s', 'wandtech-console'), esc_html(WANDTECH_CONSOLE_VERSION));
					?>
				</div>
			</footer>
		</div>
		<?php
	}
}