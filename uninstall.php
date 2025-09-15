<?php
/**
 * WandTech Console Uninstall Script.
 *
 * This script is executed when the user deletes the WandTech Console plugin
 * from the WordPress plugins page. It handles the complete removal of all
 * plugin-related data from the database, but only if the user has explicitly
 * opted-in for data cleanup in the plugin's settings.
 *
 * @package    Wandtech_Console
 * @subpackage Core
 * @author     Hamxa Boustani
 * @since      2.6.0
 * @version    3.2.0
 */

// If uninstall.php is not called by WordPress, exit for security.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Step 1: Check the user's data cleanup preference.
// We retrieve the main settings array to see if the user has enabled full cleanup.
$settings = get_option('wandtech_console_settings');

// Determine the cleanup policy. The default behavior is to preserve data for safety.
// We explicitly check for a boolean true value.
$enable_full_cleanup = isset($settings['enable_full_cleanup']) && true === $settings['enable_full_cleanup'];

// Step 2: If the user has NOT opted for full cleanup, we stop the script here.
// This is the default and safest behavior, preventing accidental data loss.
if (!$enable_full_cleanup) {
	return;
}

// Step 3: Proceed with the full data cleanup process.
// Note: It's good practice to also remove the API cache transient if it exists.
delete_option('wandtech_console_active_modules');
delete_option('wandtech_console_settings');
delete_transient('wandtech_console_api_products'); // For good measure.
delete_transient('wandtech_console_all_modules');
delete_transient('wandtech_console_deactivation_notices');

// Step 4: Clean up all related transients using a direct, performant database query.
// This includes transients created by the core framework and any modules
// that correctly followed the 'wtc_' prefix naming convention.
global $wpdb;

// Define the prefix for all WandTech Console transients.
$transient_prefix = 'wandtech_';

// Prepare the LIKE patterns for the SQL query.
// This targets both the transient value (`_transient_wtc_%`) and its timeout (`_transient_timeout_wtc_%`).
// The third pattern is for the site-wide transients.
$transient_pattern        = '_transient_' . $wpdb->esc_like($transient_prefix) . '%';
$timeout_pattern          = '_transient_timeout_' . $wpdb->esc_like($transient_prefix) . '%';
$site_transient_pattern   = '_site_transient_' . $wpdb->esc_like($transient_prefix) . '%';
$site_timeout_pattern     = '_site_transient_timeout_' . $wpdb->esc_like($transient_prefix) . '%';

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// We use a direct query for performance reasons, as deleting transients one by one is inefficient.
// The NoCaching warning is a false positive here, as this script runs only on uninstall.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
		$transient_pattern,
		$timeout_pattern,
		$site_transient_pattern,
		$site_timeout_pattern
	)
);
// phpcs:enable