# WandTech Console - Module Developer Guide

Welcome to the official developer guide for **WandTech Console**! This document provides everything you need to build powerful, performant, and secure modules for the framework.

## 1. Quick Start: Create a Module in 5 Minutes

Let's build a simple, translatable "Copyright Footer" module.

#### Step A: Create the File Structure
Inside the `/modules/` directory, create a folder for your module, a PHP file with the same name, and a `languages` sub-folder.

```
/modules/
└── copyright-footer/
    ├── languages/
    └── copyright-footer.php
```

#### Step B: Add the Boilerplate Code
Copy the following code into `copyright-footer.php`. This boilerplate includes all best practices.

```php
<?php
/**
 * Module Name:     Copyright Footer
 * Description:     Automatically adds a copyright notice to the site footer.
 * Version:         1.0.0
 * Author:          Your Name
 * Scope:           frontend
 * Text Domain:     copyright-footer
 * Domain Path:     /languages/
 */

if (!defined('WPINC')) die;

if (defined('WANDTECH_COPYRIGHT_FOOTER_LOADED')) {
    return;
}
define('WANDTECH_COPYRIGHT_FOOTER_LOADED', true);

class Wandtech_Copyright_Footer_Module {
    
    public function __construct() {
        add_action('init', [$this, 'load_module_textdomain']);
        add_action('wp_footer', [$this, 'display_copyright_notice']);
    }

    public function load_module_textdomain() {
        load_plugin_textdomain(
            'copyright-footer',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function display_copyright_notice() {
        $start_year = '2024';
        $current_year = date('Y');
        $year_string = ($start_year === $current_year) ? $start_year : "{$start_year}–{$current_year}";
        
        $copyright_text = sprintf(
            esc_html__('Copyright &copy; %1$s %2$s. All Rights Reserved.', 'copyright-footer'),
            $year_string,
            get_bloginfo('name')
        );

        echo '<div style="text-align:center;padding:20px;font-size:12px;color:#777;">' . $copyright_text . '</div>';
    }
}

new Wandtech_Copyright_Footer_Module();
```

#### Step C: Activate and Test
1.  Go to **WandTech Console > Modules** in your WordPress dashboard.
2.  Activate the "Copyright Footer" module.
3.  Visit your site's frontend to see the result.

---

## 2. The Module Header: The Control Center

The comment block at the top of your module's file is critical. It controls how the framework identifies, loads, and translates your module.

| Header             | Required? | Description                                                                                                                                                             |
|--------------------|-----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Module Name`      | **Yes**   | The human-readable name displayed in the Console UI. This is automatically translated.                                                                                  |
| `Description`      | **Yes**   | A brief explanation of the module's function. This is also automatically translated.                                                                                    |
| `Scope`            | **Yes**   | **(Performance Critical)** Defines when the module is loaded: `admin`, `frontend`, or `all`.                                                                             |
| `Text Domain`      | **Yes**   | **(Translation Critical)** A unique slug for this module's translations.                                                                                                |
| `Domain Path`      | **Yes**   | Should always be `/languages/`.                                                                                                                                         |
| `Requires Plugins` | No        | A comma-separated list of required plugin files (e.g., `woocommerce/woocommerce.php`).                                                                                |
| `Version` / `Author` | No        | Standard metadata for maintenance.                                                                                                                                      |

---

## 3. Best Practices

### a) Class Encapsulation
Declare all new properties and helper methods as `private` by default. Only use `public` for methods hooked into WordPress actions or filters.

### b) Independent Translations
The framework automatically handles the translation of your `Module Name` and `Description`. For all other strings inside your module:
1.  **Set Headers:** Ensure `Text Domain` and `Domain Path` are correctly set.
2.  **Load Text Domain:** Use the `init` hook to call `load_plugin_textdomain()`.
3.  **Use Translation Functions:** Wrap all user-facing strings in functions like `__('My String', 'my-text-domain')`.

---

## 4. Generating Translation Files (.pot)

To allow others to translate your module, you need to generate a `.pot` file. This file will contain all translatable strings, **including the `Module Name` and `Description` from the header.**

The recommended tool is **WP-CLI**.

#### Step A: Temporarily Add `Plugin Name` Header
`wp-cli-i18n` needs a `Plugin Name:` header to correctly extract header strings. Add this line **temporarily** to your module's header block.

```php
/**
 * Plugin Name:     Copyright Footer  // <-- Temporary line
 * Module Name:     Copyright Footer
 * ...
 */
```

#### Step B: Run the Command
Navigate to your module's directory in the terminal and run the following command:

```bash
# Example for the 'copyright-footer' module
cd /path/to/wp-content/plugins/wandtech-console/modules/copyright-footer/

wp i18n make-pot . languages/copyright-footer.pot
```

#### Step C: Remove `Plugin Name` Header
After the `.pot` file is successfully generated, **remember to remove the temporary `Plugin Name:` line** from your module's header. This is a critical step to prevent your module from appearing as a duplicate plugin in the main WordPress plugins list.

---

## 5. Converting an Existing Plugin into a Module

Converting a standalone plugin into a WandTech module is a simple process.

#### Step 1: Adapt the File Structure
Move the plugin's files into a new folder inside `/modules/`. Ensure the main PHP file has the same name as its parent folder.

#### Step 2: Modify the Header
Open the main PHP file and modify its header:
1.  Change the `Plugin Name:` header to `Module Name:`.
2.  **Add the `Scope:` header.** This is the most important step. Determine if your plugin's functionality is needed on the `admin`, `frontend`, or `all` sides of the site.

**Before:**
```php
/**
 * Plugin Name: My Awesome Feature
 * Description: Does something amazing.
 * ...
 */
```

**After:**```php
/**
 * Module Name: My Awesome Feature
 * Description: Does something amazing.
 * Scope:       admin  // Or frontend, or all
 * ...
 */
```

#### Step 3: Review and Refactor
- Ensure all functionality is encapsulated within a class.
- Check if the `Text Domain` is correctly defined and used.
- Your plugin is now a fully integrated WandTech module!

---

You are now equipped to build high-quality, professional modules for the **WandTech Console**. Happy coding!