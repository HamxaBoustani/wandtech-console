# WandTech Console - Developer Guide

Welcome to the official developer guide for WandTech Console! This document provides everything you need to build powerful, performant, and secure modules that integrate seamlessly with the framework.

---

## Table of Contents

- [1. Quick Start: Building a "Custom Login Logo" Module](#1-quick-start-building-a-custom-login-logo-module)
  - [Step 1: Create the File Structure](#step-1-create-the-file-structure)
  - [Step 2: Create the Main Module File (`custom-login-logo.php`)](#step-2-create-the-main-module-file-custom-login-logophp)
  - [Step 3: Add the Setting to the JavaScript Data Collector](#step-3-add-the-setting-to-the-javascript-data-collector)
  - [Step 4: Activate and Configure](#step-4-activate-and-configure)
- [2. The Module Header: The Control Center](#2-the-module-header-the-control-center)
- [3. Extending the Console: Actions & Filters API](#3-extending-the-console-actions--filters-api)
  - [3.1. Registering a New Tab](#31-registering-a-new-tab)
  - [3.2. Adding Actions to the "Modules" Tab Header](#32-adding-actions-to-the-modules-tab-header)
  - [3.3. Interacting with the "Settings" Tab](#33-interacting-with-the-settings-tab)
    - [3.3.1. Adding Settings to the "General" Section](#331-adding-settings-to-the-general-section)
  - [3.4. Accessing Settings from Your Module](#34-accessing-settings-from-your-module)
- [4. Built-in Developer Tools](#4-built-in-developer-tools)
- [5. Best Practices & Advanced Topics](#5-best-practices--advanced-topics)
  - [5.1. Core Principles](#51-core-principles)
  - [5.2. Adding a Module Thumbnail](#52-adding-a-module-thumbnail)
  - [5.3. Enqueuing Module Assets (CSS/JS)](#53-enqueuing-module-assets-cssjs)
  - [5.4. Caching with Transients](#54-caching-with-transients)
  - [5.5. The Golden Rule of Hook Registration](#55-the-golden-rule-of-hook-registration)
  - [5.6. Generating Translation Files (`.pot`)](#56-generating-translation-files-pot)
- [6. [Advanced] Converting a Simple Plugin into a Module](#6-advanced-converting-a-simple-plugin-into-a-module)
  - [Step 1: Move and Rename the Plugin Folder](#step-1-move-and-rename-the-plugin-folder)
  - [Step 2: Standardize the Main File Name](#step-2-standardize-the-main-file-name)
  - [Step 3: Update the File Header](#step-3-update-the-file-header)
  - [Step 4: Refactor Asset Paths (Crucial Step)](#step-4-refactor-asset-paths-crucial-step)
  - [Step 5: Refactor Text Domain Loading](#step-5-refactor-text-domain-loading)
  - [Step 6: Deactivate the Original Plugin](#step-6-deactivate-the-original-plugin)
---

## 1. Quick Start: Building a "Custom Login Logo" Module

The fastest way to understand the framework is to build a complete, real-world module. We'll create a module that allows an administrator to change the WordPress login logo by simply entering a URL in the Console's settings.

This example is perfect for beginners because it's concise, requires only one PHP file, and clearly demonstrates the powerful Settings API.

While you can use the built-in **Module Scaffolder** (available in Developer Mode) to generate a boilerplate, this guide will walk you through the manual process for a deeper understanding.

### Step 1: Create the File Structure

All optional modules reside in the `/wp-content/modules/` directory. For this simple module, you only need one file.

```
/wp-content/
└── modules/
    └── custom-login-logo/
        └── custom-login-logo.php
```

### Step 2: Create the Main Module File (`custom-login-logo.php`)

This single file contains all the necessary logic. It defines the module's headers, registers its settings section in the Console, saves the logo URL, and applies the custom CSS to the login page.

Copy and paste the following code into `custom-login-logo.php`:

```php
<?php
/**
 * Module Name:     Custom Login Logo
 * Module URI:      https://wandtech.ir/modules/custom-login-logo/
 * Description:     Replaces the default WordPress logo on the login screen with a custom one.
 * Version:         1.1.0
 * Author:          Your Name
 * Scope:           admin
 * Text Domain:     wtc-custom-login-logo
 * Domain Path:     /languages/
 *
 * @package         Wandtech_Console_Modules
 * @author          Your Name
 * @version         1.1.0
 */

// Exit if accessed directly to prevent direct script access.
if (!defined('ABSPATH')) {
	exit;
}

// Ensure the module is loaded only once to prevent conflicts.
if (defined('WTC_CUSTOM_LOGIN_LOGO_LOADED')) {
	return;
}
define('WTC_CUSTOM_LOGIN_LOGO_LOADED', true);

/**
 * Class WANDTECH_Custom_Login_Logo_Module.
 *
 * Manages the functionality for the Custom Login Logo module.
 */
final class WANDTECH_Custom_Login_Logo_Module {

	/**
	 * The key used to store the logo URL in the settings array.
	 *
	 * @since 1.0.0
	 * @const string
	 */
	const SETTING_KEY = 'wtc_login_logo_url';

	/**
	 * Constructor. Registers all necessary hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Hook into the login page's head to add our custom CSS.
		add_action('login_head', [ $this, 'render_custom_logo_css' ]);

		// [MODIFIED] Hook into the "General" settings section to add our field.
		add_action('wandtech_console_after_general_settings', [ $this, 'render_settings_field' ]);

		// Hook into the settings save process to sanitize and store our data.
		add_filter('wandtech_console_save_settings_data', [ $this, 'save_settings_field' ], 10, 2);
	}

	/**
	 * Renders the HTML for our single setting field in the "General" section.
	 *
	 * @since  1.0.0
	 * @param  array $settings The array of all saved WandTech Console settings.
	 * @return void
	 */
	public function render_settings_field( array $settings ): void {
		// Retrieve our saved setting, or default to an empty string.
		$logo_url = $settings[self::SETTING_KEY] ?? '';
		?>
        <hr />
		<div class="setting-row">
			<div class="setting-label">
				<h4><?php esc_html_e('Custom Login Logo URL', 'wtc-custom-login-logo'); ?></h4>
				<p class="description"><?php esc_html_e('Enter the full URL to your custom logo image. This is added by a module.', 'wtc-custom-login-logo'); ?></p>
			</div>
			<div class="setting-field">
				<input type="url" class="large-text" id="<?php echo esc_attr(self::SETTING_KEY); ?>" value="<?php echo esc_attr($logo_url); ?>" placeholder="https://example.com/logo.png">
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitizes and saves our setting field when the form is submitted.
	 *
	 * @since  1.0.0
	 * @param  array $settings    The existing settings array being saved.
	 * @param  array $posted_data The raw `$_POST` data.
	 * @return array The modified settings array.
	 */
	public function save_settings_field( array $settings, array $posted_data ): array {
		$module_settings = $posted_data['settings'] ?? [];

		// Check if our setting exists in the posted data, sanitize it, and save it.
		if (isset($module_settings[self::SETTING_KEY])) {
			$settings[self::SETTING_KEY] = esc_url_raw($module_settings[self::SETTING_KEY]);
		}
		return $settings;
	}

	/**
	 * Renders the inline CSS on the login page head.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_custom_logo_css(): void {
		// Use the framework's public API to get our saved setting.
		$logo_url = apply_filters('wandtech_console_get_setting', '', self::SETTING_KEY);

		// Only output CSS if a valid URL has been saved.
		if (empty($logo_url)) {
			return;
		}
		?>
		<style type="text/css">
			#login h1 a, .login h1 a {
				background-image: url(<?php echo esc_url($logo_url); ?>);
				background-size: contain;
				background-position: center center;
				width: 100%;
				height: 80px; /* You can adjust this height */
			}
		</style>
		<?php
	}
}

// Begins execution of the module.
new WANDTECH_Custom_Login_Logo_Module();
```

### Step 3: Activate and Configure
1.  Go to **WandTech Console > Modules** and activate the "Custom Login Logo" module.
2.  Navigate to the **Settings** tab. In the **"General"** section, you will now see the "Custom Login Logo URL" field at the bottom.
3.  Paste the URL of an image into the input field and click **"Save Changes"**.
4.  Log out of your WordPress site to see your new custom logo on the login screen!

---

## 2. The Module Header: The Control Center

The comment block at the top of your module's main PHP file is critical. It controls how the framework identifies, loads, and translates your module.

| Header             | Required? | Description                                                                                                                                                             |
|--------------------|-----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Module Name`      | **Yes**   | The human-readable name displayed in the Console UI. This is automatically translated.                                                                                  |
| `Module URI`       | No        | A URL to the module's official page. If provided, a "Details" link will appear on the module card.                                                             |
| `Description`      | **Yes**   | A brief explanation of the module's function. This is also automatically translated.                                                                                    |
| `Scope`            | **Yes**   | **(Performance Critical)** Defines when the module is loaded: `admin`, `frontend`, or `all`. Use `admin` for dashboard-only tools to ensure zero impact on frontend speed. |
| `Settings Slug`    | No        | The slug of the settings section this module registers. If provided, a settings icon will appear on the module card. By convention, this should match the module slug. |
| `Text Domain`      | **Yes**   | A unique slug for this module's translations (e.g., for strings inside the code).                                                                                       |
| `Domain Path`      | **Yes**   | Should always be `/languages/`. The framework uses this to find `.mo` files.                                                                                            |
| `Requires Plugins` | No        | A comma-separated list of required plugin files (e.g., `woocommerce/woocommerce.php`). The framework will auto-deactivate the module if requirements are not met.     |
| `Version` / `Author` | No        | Standard metadata for maintenance.                                                                                                                                      |

---

## 3. Extending the Console: Actions & Filters API

WandTech Console is built to be extensible. Use the following hooks to integrate your modules deeply into the console's UI and functionality.

### 3.1. Registering a New Tab

To add a new top-level tab to the console interface, use the `wandtech_console_register_tabs` filter.
- **Hook:** `wandtech_console_register_tabs` (Filter)
- **Example:**
```php
add_filter('wandtech_console_register_tabs', function($tabs) {
    $tabs['my-custom-tab'] = [
        'title'    => __('My Tab', 'my-text-domain'),
        'callback' => 'my_tab_render_callback',
        'priority' => 50,
    ];
    return $tabs;
});
```

### 3.2. Adding Actions to the "Modules" Tab Header

To add a new button (like "Install Module") to the header area of the "Modules" tab, use the `wandtech_console_module_manager_actions` action.
- **Hook:** `wandtech_console_module_manager_actions` (Action)
- **Example:**
```php
add_action('wandtech_console_module_manager_actions', function() {
    echo '<a href="#" class="button button-secondary">My Custom Button</a>';
}, 25);
```

### 3.3. Interacting with the "Settings" Tab

The Settings tab features a scalable, multi-section interface. To add your module's options, you must register your own "section". The process involves four steps:

#### Step 1: Register Your Settings Section (PHP)

Use the `wandtech_console_register_settings_sections` filter to add your section to the sidebar navigation.

- **Hook:** `wandtech_console_register_settings_sections` (Filter)
- **Example (`my-module.php`):**
```php
add_filter('wandtech_console_register_settings_sections', function(array $sections): array {
    $sections['my-module-settings'] = [
        'title'    => __('My Module', 'my-text-domain'),
        'callback' => 'render_my_module_settings_html',
        'priority' => 40,
    ];
    return $sections;
});
```

#### Step 2: Render Your Settings UI (PHP)

The `callback` function you registered is responsible for rendering the HTML for your settings.
- **`render_my_module_settings_html(array $settings)`:**
  - `$settings` (array): Contains all currently saved WandTech Console settings.
- **Available UI Components:**
  - `.setting-row-toggle`: For boolean options with a toggle switch.
  - `.setting-row`: A classic two-column layout for text inputs, select boxes, etc.

**Example Implementation of the callback:**
```php
function render_my_module_settings_html(array $settings) {
    $is_enabled = $settings['my_module_enabled'] ?? false;
    $api_key = $settings['my_module_api_key'] ?? '';
    ?>
    <div class="setting-row-toggle">
        <div class="setting-row-header">
            <label class="switch"><input type="checkbox" id="my_module_enabled" <?php checked($is_enabled); ?>><span class="slider"></span></label>
            <h3>Enable My Feature</h3>
        </div>
        <p class="description">A short explanation of what this toggle does.</p>
    </div>
    <hr/>
    <div class="setting-row">
        <div class="setting-label">
            <h4>My API Key</h4>
            <p class="description">Enter your API key here.</p>
        </div>
        <div class="setting-field">
            <input type="text" style="width: 300px;" id="my_module_api_key" value="<?php echo esc_attr($api_key); ?>">
        </div>
    </div>
    <?php
}
```

#### Step 3: Send Your Data to the Framework (JavaScript)

Enqueue your own JavaScript file and listen for the `wandtech:collect_settings_state` event. When this event is triggered, add your setting values to the provided object.
- **Event:** `wandtech:collect_settings_state`
- **Example (`my-settings.js`):**
```javascript
jQuery(function($) {
    $('.wrap.wandtech-wrap').on('wandtech:collect_settings_state', function(event, settingsObject) {
        settingsObject.my_module_enabled = $('#my_module_enabled').is(':checked');
        settingsObject.my_module_api_key = $('#my_module_api_key').val();
    });
});
```

#### Step 4: Sanitize and Save Your Data (PHP)

Finally, use the `wandtech_console_save_settings_data` filter to intercept the data before it's saved, allowing you to sanitize it and add it to the main settings array.
- **Hook:** `wandtech_console_save_settings_data` (Filter)
- **Example:**
```php
add_filter('wandtech_console_save_settings_data', function(array $settings, array $posted_data): array {
    $module_data = $posted_data['settings'] ?? [];
    if (isset($module_data['my_module_enabled'])) {
        $settings['my_module_enabled'] = rest_sanitize_boolean($module_data['my_module_enabled']);
    }
    if (isset($module_data['my_module_api_key'])) {
        $settings['my_module_api_key'] = sanitize_text_field($module_data['my_module_api_key']);
    }
    return $settings;
}, 10, 2);
```

#### 3.3.1. Adding Settings to the "General" Section

For modules that only have one or two simple settings, creating a whole new section might be unnecessary. The framework provides a dedicated action hook to easily add your fields directly to the bottom of the "General" settings section.

This is the recommended approach for simple settings.

- **Hook:** `wandtech_console_after_general_settings` (Action)
- **Parameters:**
    - `$settings` (array): The array of all currently saved WandTech Console settings.

**Example: Adding a "License Key" field**

```php
// In your module's main class constructor:
add_action('wandtech_console_after_general_settings', [ $this, 'render_license_key_field' ]);

// The callback function:
public function render_license_key_field(array $settings): void {
    $license_key = $settings['my_module_license_key'] ?? '';
    ?>
    <hr/>
    <div class="setting-row">
        <div class="setting-label">
            <h4>My Module License Key</h4>
            <p class="description">Enter your license key for pro features.</p>
        </div>
        <div class="setting-field">
            <input type="text" class="large-text" id="my_module_license_key" value="<?php echo esc_attr($license_key); ?>">
        </div>
    </div>
    <?php
}
```
**Important:** You still need to use the `wandtech_console_save_settings_data` filter to save your setting and the `wandtech:collect_settings_state` JavaScript event to send its value, as explained in the sections above.

---

### 3.4. Accessing Settings from Your Module

To read a saved setting anywhere in your module's code, use the `wandtech_console_get_setting` filter.
- **Hook:** `wandtech_console_get_setting` (Filter)
- **Example:**
```php
$api_key = apply_filters('wandtech_console_get_setting', '' /* default value */, 'my_module_api_key');
```

---

## 4. Built-in Developer Tools

When "Developer Mode" is enabled in the Settings tab, WandTech Console provides a powerful tool to accelerate your workflow. This tool appears and disappears dynamically without requiring a page reload.

- **Module Scaffolder:** Quickly generate a new, empty module with the correct file structure and boilerplate code.

---

## 5. Best Practices & Advanced Topics

### 5.1. Core Principles
- **Encapsulation:** Keep your module self-contained. If your module requires CSS or JS, enqueue it from its own `assets` folder.
- **Performance:** Always be mindful of your module's `Scope` header. Use `admin` for dashboard-only tools to ensure zero impact on frontend speed.
- **Security:** Follow WordPress security best practices: sanitize all inputs, escape all outputs, and use nonces.
- **Class-Based:** Always wrap your module's logic inside a class with a unique, prefixed name (e.g., `WANDTECH_...`) and instantiate it at the end of the file.

### 5.2. Adding a Module Thumbnail
To give your module a professional look in the Console UI, you can add a thumbnail image.

-   **Path:** Create a standard thumbnail file inside your module's directory at:
    `your-module-slug/assets/images/thumbnail.png`
-   **Supported Formats:** The framework will automatically detect files named `thumbnail` with `.png`, `.jpg`, or `.svg` extensions.
-   **Recommended Dimensions:** For the best visual result, use an image with a **2:1 aspect ratio** (e.g., **600px wide by 300px tall**). This ensures your image displays clearly without excessive cropping.

If no thumbnail is found, a default placeholder will be used.

### 5.3. Enqueuing Module Assets (CSS/JS)
To load your module's assets correctly, you **must** use the global `WANDTECH_CONSOLE_MODULES_URL` constant provided by the framework. Do not use `plugin_dir_url()`.

**Correct Way to Enqueue Assets:**
```php
public function enqueue_frontend_assets() {
    $style_url = WANDTECH_CONSOLE_MODULES_URL . 'your-module-slug/assets/css/style.css';
    wp_enqueue_style('my-module-style', $style_url, [], '1.0.0');
}
```

### 5.4. Caching with Transients
If your module uses the WordPress Transients API for caching, **always** start your transient keys with the prefix `wtc_`. This ensures your module's cached data is automatically cleaned up when the main plugin is uninstalled (if the user has enabled that option).

```php
// Good: Follows the naming convention for auto-cleanup.
$transient_key = 'wtc_my_module_cache';
set_transient($transient_key, $data, HOUR_IN_SECONDS);
```

### 5.5. The Golden Rule of Hook Registration
To ensure your module works reliably and avoids hard-to-debug timing issues, it's critical to register your hooks correctly.

**The Golden Rule:** Register **all** of your `add_action()` and `add_filter()` calls directly in your module class's `__construct()` method.

The `__construct()` method runs the moment your module's object is created. By placing all hook registrations here, you guarantee that your module is "listening" for every WordPress event from the very beginning, ensuring you never "miss the train" for early-firing hooks like `admin_enqueue_scripts`.

**Correct and Robust Approach (✅):**
```php
class WANDTECH_My_Module {
    public function __construct() {
        // CORRECT: Register ALL hooks immediately and directly.
        add_action('plugins_loaded', [$this, 'init_logic']);
        add_action('some_other_hook', [$this, 'my_callback']);
        add_action('wp_dashboard_setup', [$this, 'add_widget']);
    }

    // This method is a CALLBACK. Its purpose is to run logic, not register more hooks.
    public function init_logic() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        // ... do something that requires WooCommerce ...
    }
    
    public function my_callback() { /* ... */ }
    public function add_widget() { /* ... */ }
}

// Instantiate the class to make it active.
new WANDTECH_My_Module();
```

### 5.6. Generating Translation Files (`.pot`)

To allow others to translate your module, you need to generate a `.pot` file. The recommended tool is **WP-CLI**.

**Step 1: Temporarily Add `Plugin Name` Header**
`wp-cli-i18n` needs a `Plugin Name:` header to correctly extract header strings. Add this line **temporarily** to your module's header block.

```php
/**
 * Plugin Name:     Dynamic Welcome Bar  // <-- Temporary line
 * Module Name:     Dynamic Welcome Bar
 * ...
 */
```

**Step 2: Run the Command**
Navigate to your module's directory (`/wp-content/modules/your-module/`) in the terminal and run the command:

```bash
wp i18n make-pot . languages/your-text-domain.pot
```

**Step 3: Remove `Plugin Name` Header**
After the `.pot` file is generated, **remember to remove the temporary `Plugin Name:` line**. This is critical to prevent your module from appearing as a duplicate plugin in the main WordPress plugins list.

---

## 6. [Advanced] Converting a Simple Plugin into a Module

While WandTech Console is designed for creating new modules from scratch, you can also convert existing simple plugins into modules. This process is ideal for small, self-contained plugins that you want to integrate into the Console's management system.

This guide provides the manual steps to perform a successful conversion.

### Step 1: Move and Rename the Plugin Folder

1.  Navigate to your `wp-content/plugins/` directory.
2.  Copy the folder of the plugin you want to convert (e.g., `my-simple-plugin`).
3.  Paste this folder into your `wp-content/modules/` directory.

### Step 2: Standardize the Main File Name

For the framework to recognize the module, the main PHP file inside the folder must have the **exact same name as the folder itself**.

-   **If:** your folder is named `my-simple-plugin`...
-   **Then:** your main PHP file must be named `my-simple-plugin.php`.

If your plugin's main file has a different name (e.g., `loader.php`), rename it. If your plugin has multiple PHP files, you may need to update the `include` or `require` statements in the main file to point to the correct paths.

### Step 3: Update the File Header

Open the main PHP file (`my-simple-plugin.php`) and modify its header block.

1.  **Change `Plugin Name` to `Module Name`:** This is mandatory.
2.  **Add `Scope`:** This is the most critical step for performance. Choose `admin`, `frontend`, or `all`.
3.  **(Optional) Add `Module URI` and `Settings Slug`:** Add these headers if you want to add a "Details" link or a settings icon to the module card.

**Before:**
```php
/**
 * Plugin Name: My Simple Plugin
 * Plugin URI:  https://example.com/
 * Description: A very simple plugin.
 * Version:     1.0.0
 * Author:      Your Name
 */
```

**After:**
```php
/**
 * Module Name:     My Simple Plugin
 * Module URI:      https://example.com/
 * Description:     A very simple plugin, now converted to a WandTech module.
 * Version:         1.0.0
 * Author:          Your Name
 * Scope:           frontend
 * Settings Slug:   my-simple-plugin
 * Text Domain:     my-simple-plugin
 * Domain Path:     /languages/
 */
```

### Step 4: Refactor Asset Paths (Crucial Step)

This is the most important technical change. Because your module no longer resides in the `wp-content/plugins/` directory, standard WordPress functions like `plugin_dir_url()` will no longer work correctly. You **must** replace them with the `WANDTECH_CONSOLE_MODULES_URL` constant.

**Find all instances of:**
-   `plugin_dir_url(__FILE__)`
-   `plugins_url('path/to/asset.css', __FILE__)`

**And replace them with the new pattern.**

**Before:**
```php
// Old, incorrect way for a module
wp_enqueue_style(
    'my-style',
    plugin_dir_url(__FILE__) . 'assets/css/style.css'
);
```

**After (Correct Way):**
```php
// The new, correct way for a WandTech module
$style_url = WANDTECH_CONSOLE_MODULES_URL . 'my-simple-plugin/assets/css/style.css';
wp_enqueue_style(
    'my-style',
    $style_url
);
```

### Step 5: Refactor Text Domain Loading

If your original plugin used `load_plugin_textdomain`, you must update it to the method used by the framework, which works for directories outside of `wp-content/plugins`.

**Before:**
```php
// Old, incorrect way for a module
load_plugin_textdomain(
    'my-simple-plugin',
    false,
    dirname(plugin_basename(__FILE__)) . '/languages/'
);
```

**After (Correct Way):**
```php
// The new, correct way for a WandTech module
if (defined('WANDTECH_CONSOLE_MODULES_PATH')) {
    $domain      = 'my-simple-plugin';
    $mofile_path = WANDTECH_CONSOLE_MODULES_PATH . $domain . '/languages/' . $domain . '-' . get_locale() . '.mo';
    load_textdomain($domain, $mofile_path);
}
```

### Step 6: Deactivate the Original Plugin

After completing the steps above:
1.  Go to the main **WordPress > Plugins** page.
2.  **Deactivate** and then **Delete** the original plugin.
3.  Navigate to **WandTech Console > Modules**. Your newly converted module should now appear in the list.
4.  Activate it and test thoroughly to ensure all functionality and styles are working as expected.