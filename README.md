<div align="center">
  <img src="https://raw.githubusercontent.com/HamxaBoustani/wandtech-console/main/assets/images/wandtech-logo.svg" alt="WandTech Console Logo" width="128">
  <h1>WandTech Console</h1>
  <p><strong>A high-performance, developer-centric framework for adding modular functionality to WordPress.</strong></p>

  <p>
    <a href="https://github.com/HamxaBoustani/wandtech-console/releases" target="_blank">
      <img src="https://img.shields.io/badge/version-2.1.1-blue.svg?style=for-the-badge" alt="Plugin Version">
    </a>
    <img src="https://img.shields.io/badge/wp_tested-6.5+-brightgreen.svg?style=for-the-badge" alt="WordPress Tested">
    <img src="https://img.shields.io/badge/php-7.4+-blueviolet.svg?style=for-the-badge" alt="PHP Requires">
    <a href="https://github.com/HamxaBoustani/wandtech-console/blob/main/LICENSE" target="_blank">
      <img src="https://img.shields.io/badge/license-GPL--2.0+-informational.svg?style=for-the-badge" alt="License">
    </a>
  </p>

  <!-- === THE MODIFIED PLAYGROUND SECTION === -->
  <br>
  <a href="https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/HamxaBoustani/wandtech-console/main/blueprint.json" target="_blank" rel="noopener">
    <img src="https://pub-4517acecab6543f0bc62af2fea95f2b6.r2.dev/playground-icon.svg" alt="Open in WordPress Playground">
  </a>
  <!-- === END OF SECTION === -->

</div>

---

## 💡 The Philosophy: Power and Performance Through Modularity

> *Stop installing dozens of single-feature plugins. Start using one elegant, blazing-fast central hub to enable powerful tools on demand.*

**WandTech Console** is not just another plugin; it's a lightweight "app store" for your website's features. The core plugin itself is incredibly lean. Its only job is to provide a robust framework and a beautiful interface—the **Console**—where you can activate **Modules**.

This architecture gives you absolute control, ensuring your site remains fast, clean, and easy to maintain by loading **only the code you need, when you need it.**

---

## ✨ Key Features & Technical Advantages

| Feature                     | Technical Breakdown & Benefit                                                                                                                                     |
|-----------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 🔌 **True Modular Architecture** | **Dual-Tier System (`core-modules` & `modules`):** Guarantees core UI stability while providing maximum flexibility for optional, user-activated features. |
| ⚡ **Performance-First Design** | **Conditional, Context-Aware Loading:** Modules load based on a `Scope` header (`admin`, `frontend`), ensuring admin tools have **zero performance impact** on the frontend. |
| 🛡️ **Robust Dependency Engine** | **Automatic Dependency Checks:** The console validates if required plugins are active *before* loading a module, preventing fatal errors and showing clear admin notices. |
| 🔐 **Secure by Default**       | **Hardened Against Vulnerabilities:** Proactively secured against CSRF (Nonces), Path Traversal (`realpath`), Whitelist Validation, and XSS (output escaping). |
| 🛠️ **Extensible & Developer-Friendly** | **Hook & Filter-Driven:** The Console's UI and functionality are extensible via standard WordPress hooks, allowing any module to integrate deeply without touching core code. |
| 🌍 **Fully Translatable**       | **Isolated Text Domains:** Each module manages its own translation files, making them completely portable and easy to manage for multilingual sites. |
| 🎨 **Clean & Modern UI**       | An intuitive, responsive, and accessible interface with AJAX-powered actions and `localStorage` persistence for a seamless experience. |

---

## 🚀 Installation

1.  Download the latest `.zip` from the **[Releases](https://github.com/HamxaBoustani/wandtech-console/releases)** page.
2.  In your WordPress admin dashboard, navigate to **Plugins > Add New Plugin**.
3.  Click **Upload Plugin**, select the downloaded `.zip` file, and click **Install Now**.
4.  After installation, **Activate** the plugin.

---

## ⚙️ How to Use

1.  Upon activation, a new menu item, **"WandTech,"** will appear in your WordPress dashboard.
2.  Click it to open the **WandTech Console**.
3.  Navigate to the **Modules** tab to view, filter, search, activate, and deactivate all available modules.
4.  Click the "Install Module" button to upload and install new modules directly from a `.zip` file.

<div align="center">
  <img src="https://raw.githubusercontent.com/HamxaBoustani/wandtech-console/main/assets/images/screenshot.png" alt="WandTech Console Screenshot">
</div>

---

## 👨‍💻 For Developers: Building on the Framework

WandTech Console is designed to be the foundation for your custom features. For a complete, in-depth guide on creating your own modules—including details on the `Scope` header, dependency management, and translation—please see the **[Module Developer Guide](./modules/README.md)**.

---

## 🤝 Contributing

Contributions are what make the open-source community an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1.  Fork the Project.
2.  Create your Feature Branch (`git checkout -b feature/AmazingModule`).
3.  Commit your Changes (`git commit -m 'Add some AmazingModule'`).
4.  Push to the Branch (`git push origin feature/AmazingModule`).
5.  Open a Pull Request.

---

## 🚀 Scalability & Enterprise Readiness

WandTech Console is engineered for high performance, even on large-scale websites.

#### Caching Strategy
The framework and its modules make intelligent use of the WordPress **Transients API**. For the best possible performance, we highly recommend running your site on a hosting environment that supports a persistent object cache, such as **Redis** or **Memcached**. The Console will automatically use it to store cached data in memory (RAM), resulting in a dramatically faster experience.

#### Security & Rate Limiting
All AJAX and REST API endpoints include robust security checks. For high-traffic websites, we recommend implementing rate limiting at the infrastructure level (e.g., via **Cloudflare**, **Wordfence**, or your web server) for protection against DDoS attacks. This is more performant than handling it within the application.

---

## 📝 Changelog

A detailed history of all changes is available in the **[CHANGELOG.md](./CHANGELOG.md)** file.

---

## 📜 License

Distributed under the **GPLv2 or later License**. See the `LICENSE` file for more information.

---

<div align="center">
  <em>Made with ❤️ by Hamxa Boustani</em>
</div>