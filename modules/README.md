# WandTech Console - Module Developer Guide

Welcome to the development hub for **WandTech Console** modules! This guide provides everything you need to build powerful, performant, and portable modules for the framework.

A well-crafted module is defined by its main PHP file header. Let's start by understanding each part of it.

---

## The Module Header Block

The comment block at the top of your module's main PHP file is not just for information—it's how the WandTech Console framework identifies, registers, and loads your module. Getting this right is the most critical step.

Here is a template of a complete header block:

```php
/**
 * Module Name:     Social Links Widget
 * Description:     Adds a widget to display social media links.
 * Version:         1.0.0
 * Author:          Your Name
 *
 * Scope:           frontend
 * Text Domain:     social-links
 * Domain Path:     /languages/
 */
```

### Header Fields Explained

| Header          | Required? | Description                                                                                                                                                                                             |
|-----------------|-----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Module Name`   | **Yes**   | The human-readable name for your module. This is what users will see in the **WandTech Console > Modules** list.                                                                                            |
| `Description`   | No        | A brief, clear explanation of what the module does. This is also displayed on the Modules page.                                                                                                         |
| `Version`       | No        | The current version number of your module (e.g., `1.0.0`). Useful for asset cache-busting and maintenance.                                                                                                |
| `Author`        | No        | Your name or your team's name.                                                                                                                                                                          |
| `Scope`         | **Yes**   | **(Performance Critical)** Tells the framework *when* to load your module. This is the key to keeping the site fast. See the "Understanding Scope" section below for details.                                 |
| `Text Domain`   | **Yes**   | **(Translation Critical)** A unique, lowercase, hyphenated slug that identifies all translatable strings for this specific module. It must be unique across all modules.                                   |
| `Domain Path`   | **Yes**   | The relative path to the folder containing your module's translation files (`.po` and `.mo`). This should always be set to `/languages/`.                                                                   |

### Understanding `Scope`

The `Scope` header is the most important setting for performance. It ensures your module's code is only loaded when absolutely necessary.

-   **`admin`**: Choose this if your module only affects the WordPress dashboard.
    -   *Use Case*: Dashboard widgets, new admin menu pages, custom post type columns, user profile modifications.
    -   *Result*: The module's code will **never** be loaded on the public-facing frontend of the site, ensuring zero performance impact for your visitors.

-   **`frontend`**: Choose this if your module only affects the public-facing website.
    -   *Use Case*: Shortcodes, sidebar widgets, modifications to post content (`the_content` filter), custom script enqueuing for visitors.
    -   *Result*: The module's code will **not** be loaded on admin pages, making your dashboard faster.

-   **`all`**: Choose this **only if your module must run everywhere**.
    -   *Use Case*: A module that registers a custom post type (which needs to be available in both admin and frontend) or a module that modifies user roles.
    -   *Warning*: Use this scope sparingly. Always ask yourself if you can split the functionality into separate `admin` and `frontend` hooks.

---

## Creating a New Module: Step-by-Step

Now that you understand the header, let's build a module.

### Step 1: Create the File Structure

1.  Inside this `modules` directory, create a new folder named after your module's "slug" (e.g., `social-links`).
2.  Inside it, create a PHP file with the exact same name (e.g., `social-links.php`).
3.  Create a `languages` folder inside your module's directory to hold translation files.

```
/modules/
└── social-links/
    ├── languages/
    └── social-links.php
```

### Step 2: Write the Main Module File

Open your main PHP file and use the following boilerplate, which includes the header and the necessary class structure.

```php
<?php
/**
 * Module Name:     Social Links Widget
 * Description:     Adds a widget to display social media links.
 * Version:         1.0.0
 * Author:          Your Name
 * Scope:           frontend
 * Text Domain:     social-links
 * Domain Path:     /languages/
 */

// Prevent direct file access.
if (!defined('WPINC')) die;

// Use a constant to prevent the file from being loaded more than once.
if (defined('WANDTECH_SOCIAL_LINKS_MODULE_LOADED')) {
    return;
}
define('WANDTECH_SOCIAL_LINKS_MODULE_LOADED', true);

class Wandtech_Social_Links_Module {

    public function __construct() {
        add_action('init', array($this, 'load_module_textdomain'));
        add_action('widgets_init', array($this, 'register_widget'));
    }

    public function load_module_textdomain() {
        load_plugin_textdomain(
            'social-links',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function register_widget() {
        // Your widget registration logic goes here.
    }
}

new Wandtech_Social_Links_Module();
```

### Step 3: Activate and Test

1.  Navigate to **WandTech Console > Modules** in your WordPress dashboard.
2.  Find your new module and activate it.
3.  Verify its functionality and ensure it only loads in the `Scope` you defined.

---

You are now ready to build high-quality, performant modules for the **WandTech Console**.
```

This version places the most critical information first, ensuring that any developer, regardless of skill level, understands the importance of the header before they even start coding.