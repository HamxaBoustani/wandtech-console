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
        // Load this module's text domain so its strings can be translated.
        add_action('init', [$this, 'load_module_textdomain']);
        
        // Add the copyright notice to the WordPress footer.
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
        
        // This string is now translatable using the 'copyright-footer' text domain.
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
| `Module Name`      | **Yes**   | The human-readable name displayed in the Console UI. **This is automatically translatable** if you provide translation files.                                           |
| `Description`      | **Yes**   | A brief explanation of the module's function. **This is also automatically translatable.**                                                                              |
| `Scope`            | **Yes**   | **(Performance Critical)** Defines when the module is loaded. See "Understanding Scope" below.                                                                        |
| `Text Domain`      | **Yes**   | **(Translation Critical)** A unique slug for this module's translations. Used for the header and all strings inside your module.                                        |
| `Domain Path`      | **Yes**   | Should always be `/languages/`.                                                                                                                                         |
| `Requires Plugins` | No        | A comma-separated list of required plugin files (e.g., `woocommerce/woocommerce.php`). The framework will prevent activation if these are not active.                  |
| `Version` / `Author` | No        | Standard metadata for maintenance.                                                                                                                                      |

### Understanding `Scope`
-   **`admin`**: Loads **only** in the WordPress dashboard.
-   **`frontend`**: Loads **only** on the public-facing site.
-   **`all`**: Loads everywhere. Use sparingly.

---

## 3. Advanced Features & Best Practices

To create truly professional modules, follow these best practices.

### a) Class Encapsulation (Important!)
Encapsulation is the practice of hiding a class's internal data to make your code more stable.
- **Rule of Thumb:** Declare all new properties and helper methods as `private` by default. Only use `public` for methods hooked into WordPress actions or filters.

### b) Independent Translations
The framework is designed so that each module manages its own translations.
1.  **Set Headers:** Ensure `Text Domain` and `Domain Path` are correctly set in your module's header.
2.  **Load Text Domain:** Use the `init` hook to call `load_plugin_textdomain()`. The boilerplate in the Quick Start provides a perfect example.
3.  **Use Translation Functions:** Wrap all user-facing strings in WordPress translation functions, like `__('My String', 'my-text-domain')`.

The framework will **automatically handle the translation of your `Module Name` and `Description`** based on the text domain you provide. You do not need to write any extra code for this.

### c) Adding New Console Tabs
Your module can add its own settings page as a new tab in the Console.

```php
// Inside your module's main class...
public function __construct() {
    add_filter('wandtech_console_register_tabs', [$this, 'add_my_custom_tab']);
}

public function add_my_custom_tab(array $tabs): array {
    $tabs['my-custom-tab'] = [
        'title'    => __('My Settings', 'my-module-domain'),
        'callback' => [$this, 'render_my_settings_page'],
        'priority' => 50
    ];
    return $tabs;
}

public function render_my_settings_page() {
    // Your settings page HTML goes here.
    echo '<h2>' . esc_html__('My Settings Page', 'my-module-domain') . '</h2>';
}
```

### d) Module Directories Explained
-   `/modules/`: This is where you place all your **Optional Modules**.
-   `/core-modules/`: Reserved for the framework's essential UI components. **Do not add files here.**

---

You are now equipped to build high-quality, professional modules for the **WandTech Console**. Happy coding!