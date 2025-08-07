<?php
/**
 * Fired when the plugin is uninstalled.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Delete the option that stores active modules with the new name
delete_option('wandtech_console_active_modules');