<?php
/**
 * Core Module: About Tab
 *
 * Provides the "About" tab for the WandTech Console, including author info
 * and links to the project website and repository. This is a system module
 * and is always active.
 *
 * @package    Wandtech_Console
 * @subpackage Core_Modules
 * @author     Hamxa Boustani
 * @since      2.0.0
 * @version    3.2.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

// Ensure the module is loaded only once to prevent conflicts.
if (defined('WANDTECH_ABOUT_TAB_LOADED')) {
	return;
}
define('WANDTECH_ABOUT_TAB_LOADED', true);

/**
 * Class Wandtech_About_Tab.
 *
 * Registers and renders the content for the "About" tab in the console.
 */
final class Wandtech_About_Tab {

	/**
	 * Constructor. Hooks into the framework's tab registration system.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter('wandtech_console_register_tabs', [ $this, 'register_tab' ]);
	}

	/**
	 * Registers the "About" tab with the console's UI.
	 *
	 * This method is hooked into the `wandtech_console_register_tabs` filter.
	 *
	 * @since  2.0.0
	 * @param  array $tabs The existing array of registered tabs.
	 * @return array The modified array of tabs including the "About" tab.
	 */
	public function register_tab( array $tabs ): array {
		$tabs['about'] = [
			'title'    => __('About', 'wandtech-console'),
			'callback' => [ $this, 'render_content' ],
			'priority' => 100, // A high priority ensures it appears as one of the last tabs.
		];
		return $tabs;
	}

	/**
	 * Renders the HTML content for the "About" tab.
	 *
	 * This is the callback function specified in the tab registration.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function render_content(): void {
		// Use a dedicated email for Gravatar to avoid exposing a personal email directly in the code.
		$author_email = 'Hamxa.Boustani@gmail.com'; // Example: Use a public-facing email.
		$hash         = md5(strtolower(trim($author_email)));
		$gravatar_url = sprintf('https://secure.gravatar.com/avatar/%s?s=128&d=mm&r=g', $hash);
		?>
		<div class="wandtech-about-container">

			<div class="wandtech-about-header">
				<h3><?php esc_html_e('More Power, More Performance.', 'wandtech-console'); ?></h3>
				<p><?php esc_html_e('Thank you for choosing WandTech Console!', 'wandtech-console'); ?></p>
			</div>
			<div class="wandtech-about-section creator-section">
				<img src="<?php echo esc_url($gravatar_url); ?>" alt="<?php esc_attr_e('Hamxa Boustani', 'wandtech-console'); ?>" width="128" height="128">
				<div class="content">
					<h4><?php esc_html_e('About the Author', 'wandtech-console'); ?></h4>
					<p>
						<?php
						$author_link = '<a href="https://github.com/HamxaBoustani/" target="_blank" rel="noopener noreferrer">Hamxa Boustani</a>';
						$about_text  = sprintf(
							/* translators: %s is the author's name linked to their GitHub profile. */
							__('This plugin was crafted with ❤️ by <strong>%s</strong>, a passionate developer dedicated to creating high-quality, performant, and user-friendly solutions for WordPress.', 'wandtech-console'),
							$author_link
						);

						// Then, escape the entire string with wp_kses before outputting.
						echo wp_kses(
							$about_text,
							[
								'strong' => [],
								'a'      => [
									'href'   => true,
									'target' => true,
									'rel'    => true,
								],
							]
						);
						?>
					</p>
				</div>
			</div>

			<div class="wandtech-about-section">
				<div class="content">
					<h4><?php esc_html_e('Get Involved & Stay Connected', 'wandtech-console'); ?></h4>
					<p><?php esc_html_e('Help us make this open-source project even better! Star us on GitHub, report issues, or connect on social media.', 'wandtech-console'); ?></p>
					<div class="wandtech-action-buttons">
						<a href="https://wandtech.ir" class="button button-primary" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-admin-site-alt3"></span>
							<?php esc_html_e('Visit WandTech', 'wandtech-console'); ?>
						</a>
						<a href="https://github.com/HamxaBoustani/wandtech-console/" class="button button-secondary" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-star-filled"></span>
							<?php esc_html_e('GitHub Repository', 'wandtech-console'); ?>
						</a>
						<a href="https://x.com/HamxaBoustani" class="button button-secondary" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-twitter"></span>
							<?php esc_html_e('Follow on X', 'wandtech-console'); ?>
						</a>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}

// Instantiate the class to register the tab and its content.
new Wandtech_About_Tab();