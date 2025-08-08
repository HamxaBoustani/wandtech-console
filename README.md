# WandTech Console

![Plugin Version](https://img.shields.io/badge/version-2.3-blue.svg)
![WordPress Tested](https://img.shields.io/badge/wp--tested-6.5-brightgreen.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)

A lightweight, modular, and high-performance framework for adding powerful features to WordPress.

WandTech Console is not just another plugin; it's a central hub for managing your site's functionality. Instead of installing dozens of separate plugins for small features, you can use the Console to enable and disable powerful tools on demand. This keeps your site fast, clean, and easy to manage.

---

## What is WandTech Console?

Think of WandTech Console as a lightweight "app store" for your website's features. The core plugin itself is incredibly lean and does almost nothing on its own. Its only job is to provide a beautiful and intuitive interface—the **Console**—where you can activate **Modules**.

Each **Module** is a self-contained feature, like a mini-plugin. This architecture gives you complete control, allowing you to enable only the functionality you truly need, ensuring your site remains blazingly fast.

## ✨ Key Features

*   **Modular Architecture:** Only load the code you need. Activate or deactivate modules with a single click without adding bloat to your site.
*   **Performance-First Design:** Modules can be loaded conditionally (e.g., `admin` only or `frontend` only), ensuring zero performance impact on your site's visitors for admin-specific tools.
*   **Developer-Friendly API:** Built for developers. Creating new modules is incredibly simple and well-documented. Each module is fully independent with its own translation files.
*   **Clean & Modern UI:** A beautiful, centralized, and intuitive interface to manage all your modules.
*   **Included Modules:** Comes with powerful modules out-of-the-box, such as:
    *   **WooCommerce Charts:** A beautiful analytics dashboard for your store.
    *   **Welcome Widget:** A personalized welcome widget for the admin dashboard.

---

## 🚀 Installation

1.  Download the latest `.zip` file of the plugin.
2.  In your WordPress dashboard, navigate to **Plugins > Add New**.
3.  Click on the **Upload Plugin** button.
4.  Choose the `.zip` file you downloaded and click **Install Now**.
5.  Once installed, click **Activate**.

---

## ⚙️ How to Use

1.  After activation, you will find a new **"WandTech"** menu item in your WordPress dashboard.
2.  Click on it to open the **WandTech Console**.
3.  Navigate to the **Modules** tab. Here you will see a list of all available modules.
4.  Use the toggle switch next to each module to activate or deactivate it. Changes are saved instantly via AJAX.

![WandTech Console Screenshot](https://i.imgur.com/your-screenshot-url.png)
*(Recommendation: Add a screenshot of your beautiful admin panel here!)*

---

## 👨‍💻 For Developers: Creating a Module

WandTech Console is built to be extended. Creating a new module is a straightforward process designed to give you maximum power and flexibility.

The core principles are:
1.  Each module lives in its own sub-directory inside `/modules`.
2.  Each module has a main PHP file with a special header that defines its properties, including its `Name`, `Scope` (admin, frontend, or all), and `Text Domain`.
3.  Each module is a self-contained, encapsulated class.

For a complete, in-depth guide on creating your own modules, please see the **[Module Developer Guide](./modules/README.md)** located in the `/modules` directory.

---

## 🤝 Contributing

Contributions are welcome! Whether you're fixing a bug, improving a feature, or creating a new module, we'd love to see your pull requests.

1.  **Fork** the repository.
2.  Create a new branch (`git checkout -b feature/my-awesome-module`).
3.  Make your changes.
4.  **Commit** your changes (`git commit -am 'Add some feature'`).
5.  **Push** to the branch (`git push origin feature/my-awesome-module`).
6.  Open a **Pull Request**.

Please open an issue for bugs, feature requests, or any other questions.

---

## 📜 License

This plugin is licensed under the **GPL v2 or later**.
See the [LICENSE](LICENSE) file for more details.