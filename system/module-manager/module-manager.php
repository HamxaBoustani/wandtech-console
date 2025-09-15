<?php
/**
 * Core Module: Module Manager Tab
 *
 * Provides the "Modules" tab for managing all optional modules in the WandTech Console.
 * This includes the UI for listing, activating, searching, and filtering modules.
 * This is a system module and is always active.
 *
 * @package    Wandtech_Console
 * @subpackage Core_Modules
 * @author     Hamxa Boustani
 * @since      2.1.0
 * @version    3.2.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

// Ensure the module is loaded only once to prevent conflicts.
if (defined('WANDTECH_MODULE_MANAGER_LOADED')) {
	return;
}
define('WANDTECH_MODULE_MANAGER_LOADED', true);

/**
 * Class Wandtech_Module_Manager_Tab.
 *
 * Registers and renders the content for the "Modules" tab in the console.
 */
final class Wandtech_Module_Manager_Tab {

	/**
	 * Constructor. Hooks into the framework's tab and footer systems.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter('wandtech_console_register_tabs', [ $this, 'register_tab' ]);
		add_action('admin_footer', [ $this, 'render_delete_confirmation_modal' ]);
	}

	/**
	 * Registers the "Modules" tab with the console's UI.
	 *
	 * This method is hooked into the `wandtech_console_register_tabs` filter.
	 *
	 * @since  2.1.0
	 * @param  array $tabs The existing array of registered tabs.
	 * @return array The modified array of tabs including the "Modules" tab.
	 */
	public function register_tab( array $tabs ): array {
		$tabs['modules'] = [
			'title'    => __('Modules', 'wandtech-console'),
			'callback' => [ $this, 'render_content' ],
			'priority' => 40,
		];
		return $tabs;
	}

	/**
	 * Renders the main HTML content for the "Modules" tab.
	 *
	 * This is the callback function specified in the tab registration.
	 * It displays the complete module management interface.
	 *
	 * @since  2.1.0
	 * @return void
	 */
	public function render_content(): void {
		$modules_manager = Wandtech_Console::get_instance()->modules;
		$all_modules     = $modules_manager->get_all_modules_with_translated_headers();
		$active_modules  = $modules_manager->get_active_modules();

		$total_count    = count($all_modules);
		$active_count   = count($active_modules);
		$inactive_count = $total_count - $active_count;
		$has_modules    = ! empty($all_modules);
		?>
		
		<div class="modules-header">
			<div class="module-filters" <?php echo $has_modules ? '' : 'style="display:none;"'; ?>>
				<a href="#" class="filter-link current" data-filter="all">
					<?php esc_html_e('All', 'wandtech-console'); ?> <span class="count" id="filter-count-all">(<?php echo esc_html($total_count); ?>)</span>
				</a>
				<a href="#" class="filter-link" data-filter="active">
					<?php esc_html_e('Active', 'wandtech-console'); ?> <span class="count" id="filter-count-active">(<?php echo esc_html($active_count); ?>)</span>
				</a>
				<a href="#" class="filter-link" data-filter="inactive">
					<?php esc_html_e('Inactive', 'wandtech-console'); ?> <span class="count" id="filter-count-inactive">(<?php echo esc_html($inactive_count); ?>)</span>
				</a>
			</div>
			<div class="module-actions">
				<input type="search" id="module-search-input" class="wp-filter-search" 
					   placeholder="<?php esc_attr_e('Search modules...', 'wandtech-console'); ?>"
					   <?php echo $has_modules ? '' : 'style="display:none;"'; ?>>
				
				<div class="header-actions-container" <?php echo $has_modules ? '' : 'style="display:none;"'; ?>>
					<?php
					/**
					 * Fires in the header of the Modules tab to allow adding action buttons.
					 *
					 * @since 2.1.0
					 */
					do_action('wandtech_console_module_manager_actions');
					?>
				</div>
			</div>
		</div>

		<div class="module-cards">
			<?php if ($has_modules) : ?>
				<?php foreach ($all_modules as $slug => $module_data) {
					$this->render_module_card($slug, $module_data, $active_modules);
				} ?>
				<p class="no-results-message" style="display: none;">
					<?php esc_html_e('No modules found matching your search criteria.', 'wandtech-console'); ?>
				</p>
			<?php endif; ?>

			<div class="modules-empty-state" <?php echo $has_modules ? '' : 'style="display:none;"'; ?>>
				<span class="dashicons dashicons-plugins-checked"></span>
				<h3><?php esc_html_e('Your Module Library is Empty', 'wandtech-console'); ?></h3>
				<p><?php esc_html_e('Start adding powerful features to your site right now.', 'wandtech-console'); ?></p>
				<div class="empty-state-actions">
					<?php
					/**
					 * Fires in the "empty state" placeholder of the Modules tab.
					 * Allows actions like "Install" or "Create" to be displayed.
					 *
					 * @since 2.7.0
					 */
					do_action('wandtech_console_module_manager_actions');
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a single module card's HTML.
	 *
	 * This helper function encapsulates the logic for displaying one module,
	 * making the main render method cleaner and more readable.
	 *
	 * @since 3.2.0
	 *
	 * @param string   $slug           The unique slug of the module.
	 * @param array    $module_data    An array of the module's header data.
	 * @param string[] $active_modules A list of all active module slugs.
	 * @return void
	 */
	private function render_module_card( string $slug, array $module_data, array $active_modules ): void {
		$is_active      = in_array($slug, $active_modules, true);
		$scope          = strtolower(trim($module_data['Scope'] ?? 'all'));
		$module_uri     = ! empty($module_data['Module URI']) ? esc_url($module_data['Module URI']) : '';
		$settings_slug  = ! empty($module_data['Settings Slug']) ? sanitize_key($module_data['Settings Slug']) : '';
		$thumbnail_url  = ! empty($module_data['thumbnail_url'])
						? esc_url($module_data['thumbnail_url'])
						: WANDTECH_CONSOLE_URL . 'assets/images/module-placeholder.svg';
		?>
		<div class="module-card<?php echo $is_active ? ' is-active' : ''; ?>" data-scope="<?php echo esc_attr($scope); ?>">
			
			<div class="module-card-thumbnail">
				<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($module_data['Name']); ?> thumbnail" loading="lazy">

				<?php if ($settings_slug) : ?>
					<a href="<?php echo esc_url(admin_url('admin.php?page=wandtech-console#settings')); ?>" 
					   class="module-settings-link" 
					   data-settings-slug="<?php echo esc_attr($settings_slug); ?>" 
					   aria-label="<?php esc_attr_e('Module Settings', 'wandtech-console'); ?>"
					   <?php echo $is_active ? '' : 'style="display:none;"'; ?>>
						<span class="dashicons dashicons-admin-generic"></span>
					</a>
				<?php endif; ?>
			</div>

			<div class="module-card-content">
				<div class="module-card-header">
					<h3><?php echo esc_html($module_data['Name']); ?></h3>
					<label class="switch">
						<input type="checkbox" class="module-toggle" data-module="<?php echo esc_attr($slug); ?>" <?php checked($is_active); ?>>
						<span class="slider"></span>
					</label>
				</div>
				<div class="module-card-body">
					<p><?php echo wp_kses_post($module_data['Description']); ?></p>
					<div class="module-card-error-notice" style="display:none;"></div>
				</div>
			</div>

			<div class="module-card-footer">
				<div class="module-meta">
					<small>
						<?php
						/* translators: %s: Module version number. */
						printf(esc_html__('Version: %s', 'wandtech-console'), esc_html($module_data['Version']));
						?>
					</small>
					<small>
						<?php
						/* translators: %s: Module author name. */
						printf(esc_html__('Author: %s', 'wandtech-console'), esc_html($module_data['Author']));
						?>
						<?php if ($module_uri) : ?>
							| <a href="<?php echo esc_url($module_uri); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Details', 'wandtech-console'); ?></a>
						<?php endif; ?>
					</small>
				</div>
				<div class="module-action-links">
					<a href="#" class="delete-module-link" 
					   data-module-slug="<?php echo esc_attr($slug); ?>" 
					   data-module-name="<?php echo esc_attr($module_data['Name']); ?>" 
					   <?php echo $is_active ? 'style="display:none;"' : ''; ?>>
						<?php esc_html_e('Delete', 'wandtech-console'); ?>
					</a>
				</div>
			</div>
			<div class="spinner-overlay"></div>
		</div>
		<?php
	}
	
	/**
	 * Renders the delete confirmation modal HTML in the admin footer.
	 *
	 * This modal is used for a safer and more consistent UX when deleting modules.
	 *
	 * @since  2.3.0
	 * @return void
	 */
	public function render_delete_confirmation_modal(): void {
		// Only render the modal on the console page to avoid unnecessary HTML elsewhere.
		$screen = get_current_screen();
		if (!$screen || 'toplevel_page_wandtech-console' !== $screen->id) {
			return;
		}
		?>
		<div id="delete-module-modal" class="wandtech-modal-overlay" style="display: none;">
			<div class="wandtech-modal-content">
				<button type="button" class="wandtech-modal-close" aria-label="<?php esc_attr_e('Close', 'wandtech-console'); ?>">&times;</button>
				<h2><?php esc_html_e('Are you sure?', 'wandtech-console'); ?></h2>
				
				<div class="wandtech-modal-body">
					<p id="delete-modal-text">
						<?php /* This text will be dynamically replaced by JavaScript. */ ?>
					</p>
					<p><strong><?php esc_html_e('This action cannot be undone.', 'wandtech-console'); ?></strong></p>
				</div>

				<div class="wandtech-modal-footer">
					<button type="button" class="button button-secondary" id="cancel-delete-button">
						<?php esc_html_e('Cancel', 'wandtech-console'); ?>
					</button>
					<button type="button" class="button button-danger" id="confirm-delete-button">
						<span class="dashicons dashicons-trash" style="vertical-align: text-top;"></span>
						<?php esc_html_e('Yes, Delete Module', 'wandtech-console'); ?>
					</button>
					<span class="spinner"></span>
				</div>
			</div>
		</div>
		<?php
	}
}

// Instantiate the class to register the tab and its content.
new Wandtech_Module_Manager_Tab();