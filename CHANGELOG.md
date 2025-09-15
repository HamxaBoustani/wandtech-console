# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Your next amazing feature.

---

## [3.2.0] - 2025-09-10

This is a major architectural hardening and code quality release. It focuses on improving the robustness of the Developer Mode feature, enhancing the extensibility of the settings screen, ensuring full compliance with WordPress.org standards, and improving code maintainability across the entire plugin.

### Added
- **Developer Experience - Extensible General Settings:** A new action hook, `wandtech_console_after_general_settings`, has been added. This allows other modules to easily and cleanly add their own setting fields to the "General" settings tab without modifying core files, promoting a cleaner and more scalable architecture.

### Changed
- **Architectural Hardening - Developer Mode:** The logic for "Developer Mode" has been re-architected. Instead of toggling UI elements with JavaScript, it now controls the conditional loading of developer-specific system modules directly in PHP. This is a more secure, performant, and scalable approach.
- **Code Quality - Full Refactor & Documentation:** All core PHP files have been fully documented with PHPDoc and modernized with PHP 7.4+ type hinting. This improves readability, maintainability, and IDE integration.
- **Improved UX - Settings Reload:** Saving settings now consistently reloads the page to ensure that all server-side changes (like enabling Developer Mode) are applied reliably and predictably.
- **Code Quality - WPCS & Plugin Check Compliance:** The entire PHP codebase has been reviewed and updated to strictly follow WordPress Coding Standards and to resolve all errors and warnings from the official Plugin Check tool.

### Fixed
- **Critical i18n Bug - Module Header Translation:** Resolved a critical issue where module header translations (`Module Name`, `Description`) were not being applied correctly. The translation loading mechanism has been re-architected to use the standard WordPress approach for automatic header translation.
- **Architectural Bug - Developer Module Loading:** Fixed a critical bug where system modules marked for "Developer Mode" were loaded regardless of the setting's status due to a faulty logical check. The loading mechanism is now strict and reliable.
- **Security Hardening - Input Validation:** Improved sanitization and validation for all user-submitted data in AJAX handlers (`Settings`, `Scaffolder`, `Installer`) to pass strict security checks.

---

## [3.1.1] - 2025-09-10

This is an architectural hardening and stability release focused on improving the robustness and security of the Developer Mode feature.

### Changed
- **Architectural Hardening - Developer Mode:** The logic for "Developer Mode" has been re-architected. Instead of toggling UI elements with JavaScript, it now controls the conditional loading of developer-specific system modules directly in PHP. This is a more secure, performant, and scalable approach.
- **Improved UX - Settings Reload:** Saving settings now consistently reloads the page to ensure that all server-side changes (like enabling Developer Mode) are applied reliably.

### Fixed
- **Architectural Bug - Developer Module Loading:** Fixed a critical bug where system modules marked for "Developer Mode" were loaded regardless of the setting's status due to a faulty logical check. The loading mechanism is now strict and reliable.

---

## [3.1.0] - 2025-09-05

This is a major Performance and User Experience (UX) release. It introduces a powerful caching layer for scalability and a more visually rich and intuitive interface for managing modules.

### Added
- **Major Performance Feature: Module List Caching!** The list of available modules is now cached using the Transients API. This dramatically improves admin-area performance and reduces server load on sites with a large number of modules, making the console truly enterprise-ready.
- **Major UX Feature: Module Thumbnails!** The "Modules" tab now displays a thumbnail image for each module, making the interface more engaging and professional.
- **Major UX Feature: Direct Settings Access!** Modules with a settings page now display a gear icon on their card when active, providing a one-click shortcut to the relevant settings section.
- **Modern Toast Notifications:** The default WordPress admin notices have been replaced with a custom, modern "toast" notification system that is less intrusive and visually consistent.
- **New Module Headers:** Added support for two new optional headers: `Module URI` and `Settings Slug`.

### Changed
- **Improved UX - Smart Page Reload:** Activating or deactivating a module now only triggers a page reload if that module has a settings page, ensuring a smoother, no-reload experience for simpler modules.
- **Improved UX - Animated Settings Icon:** The new settings icon on module cards gently spins on hover, providing a delightful micro-interaction.

### Fixed
- **Critical Bug - Settings Not Saving:** Fixed a critical bug where settings for optional modules were not being saved correctly.
- **UI Bug - Dynamic Settings Sections:** Fixed a major UI bug where a module's settings section would not appear or disappear dynamically after its parent module was toggled.
- **UI Bug - Admin Notice "Jump":** Fixed a visual glitch where the new toast notifications would briefly appear in the wrong position before centering themselves.

---

## [3.0.0] - 2025-09-02

This is a major architectural and developer experience release. It decouples optional modules from the core plugin, simplifies the framework by removing deprecated tools, and significantly improves the robustness of asset and translation loading for all modules.

### Changed
- **Major Architectural Change - External Module Directory:** Optional modules are no longer stored inside the plugin's directory. They now reside in a dedicated `wp-content/modules/` folder, which the framework automatically creates. This decouples user modules from the core plugin, making updates safer and the architecture more robust.
- **Developer Experience - Asset Loading:** The official method for enqueuing module assets (CSS/JS) has been standardized to use the new `WANDTECH_CONSOLE_MODULES_URL` constant. This resolves all path-related issues for modules located outside the standard plugins directory.
- **Developer Experience - Translation Loading:** The framework's core and the module scaffolder have been updated to use a more robust `load_textdomain` method, ensuring translations for external modules work correctly and reliably.
- **Developer Experience - New Constants:** Added `WANDTECH_CONSOLE_MODULES_PATH`, `WANDTECH_CONSOLE_MODULES_URL`, and `WANDTECH_CONSOLE_BASENAME` constants to provide a stable and clean API for developers.
- **UX - Quicker Access:** A "Console" link has been added to the plugin's action links on the main WordPress Plugins page for faster navigation.

### Fixed
- **Critical Bug - Module Deletion:** Fixed a critical bug where deleting a module would fail with an "Invalid module path detected" error due to an unreliable security check. The validation logic has been replaced with a more robust and secure method.

---

## [2.6.0] - 2025-08-24

This is a major architectural release focused on future-proofing the settings management system, making it fully scalable and significantly improving the user experience for administrators.

### Added
- **Major Feature: Scalable Settings UI!** The "Settings" tab has been completely re-architected with a modern, multi-section interface.
  - **New Vertical Navigation:** A new sidebar navigation menu allows for clean and logical grouping of settings.
  - **Live Search for Settings:** A search bar allows users to instantly filter sections and find the exact setting they need.
  - **Extensible Sections API:** A new filter, `wandtech_console_register_settings_sections`, allows modules to register their own dedicated sections in the settings UI.
- **New Core Setting: Uninstall Behavior.** A new "Data Cleanup on Uninstall" option has been added to the "General" settings, giving users control over whether plugin data is removed upon uninstallation.

### Changed
- **Improved UX - Settings Layout:** Boolean (true/false) options in the Settings tab now use a modern, single-column layout.
- **Improved UX - Settings State:** The active settings section is now remembered using `localStorage`.
- **Hardened - Uninstallation:** The `uninstall.php` script now reads the user's chosen "Uninstall Behavior" setting before proceeding with data cleanup.

### Fixed
- **Architectural Bug - Module Hook Registration:** Resolved a complex bug related to hook timing that prevented some modules' settings from saving correctly.
- **UI Bug - Active Settings Menu Item:** Fixed a minor CSS bug in the new settings navigation menu.
- **UI Bug - Redundant CSS:** Removed old, unused CSS rules for the previous settings layout.

---

## [2.5.0] - 2025-08-21

This is a major user experience and stability release that transforms the console into a modern, single-page-like application. It eliminates page reloads for all module operations and introduces significant performance, security, and UI enhancements.

### Added
-   **Dynamic No-Reload UX:** The entire module management experience no longer requires page reloads. Installing, scaffolding, and deleting modules now happens instantly.
-   **Smart Empty State:** When no optional modules are installed, the Modules tab now displays a helpful guide.
-   **Settings "Dirty State" Detection:** The "Save Changes" button in the Settings tab now intelligently detects unsaved changes.
-   **Dynamic Developer Features:** The "Create Module" tool (Scaffolder) now appears and disappears instantly when Developer Mode is toggled, without requiring a page reload.
-   **In-Context Error Feedback:** If activating a module fails, a clear error message now appears directly on the module card.
-   **Full RTL Support:** Dashboard information tables now render correctly for right-to-left (RTL) languages.

### Changed
-   **JavaScript Architecture:** The main `admin.js` file has been significantly refactored for improved efficiency and maintainability.
-   **Code Standardization:** Added `Requires at least` and `Requires PHP` headers to the main plugin file. Moved all inline CSS to the central `admin.css` file.

### Fixed
-   **Reliable Admin Notices:** Fixed a bug where auto-deactivation notices would not reliably appear.
-   **UI Visibility:** Fixed bugs where the search box or "Dirty State" would not appear or reset correctly.
-   **AJAX Error Handling:** Error messages for duplicate modules are now correctly displayed to the user.

---

## [2.4.0] - 2025-08-19

This is a major developer-focused release that transforms the console into a comprehensive diagnostics and debugging powerhouse.

### Added
- **Major Feature: Advanced System Info Dashboard!** The "Dashboard" tab has been completely redesigned with new panels for Server Environment, WordPress Constants, and File System Permissions.

### Changed
- **Improved UI - Dashboard Layout:** The dashboard now uses a responsive, two-column grid layout.
- **Improved UI - Consistent Info Tables:** All new information panels use a standardized table format.

### Fixed
- **Critical Bug - Modal Event Handling:** Resolved a major regression bug where modal buttons were not functioning correctly.
- **UI Bug - Settings Save:** Fixed a bug where the "Save Changes" button was not saving all settings correctly.

---

## [2.3.0] - 2025-08-19

This is a major feature release that introduces powerful new developer tools and significant user experience enhancements.

### Added
- **Major Feature: Module Scaffolder!** A new "Create Module" tool, available in Developer Mode, allows users to generate a new, fully-structured module.
- **New API Documentation:** Added a comprehensive `DEVELOPER-GUIDE.md` file.
- **Improved UX - Interactive Notices:** Admin notices now auto-scroll into view and success notices automatically fade out.

### Changed
- **Improved UX - Custom Delete Confirmation:** Replaced the browser default `confirm()` dialog with a custom modal for deleting modules.
- **Improved UX - Button Order:** The developer tool buttons are now in a more logical order: Install, Create.

### Fixed
- **Critical UI Bug - Dynamic Developer Tools:** Fixed a bug where developer-only buttons would not appear after enabling Developer Mode without a page refresh.
- **UI Bug - Modal Styling:** Fixed a CSS scope issue where variables were not available to modals.

---

## [2.2.1] - 2025-08-19

This is a user experience (UX) and stability release.

### Changed
- **Improved UX - Custom Delete Confirmation:** Replaced the default `confirm()` dialog with a custom modal.
- **Improved UX - Auto-Scrolling Notices:** The page now automatically scrolls to notices.
- **Improved UX - Self-Dismissing Success Notices:** "Success" notices now automatically fade out.
- **Hardened - Auto-Deactivation of Unmet Dependencies:** The framework now automatically deactivates modules whose dependencies are no longer met.
- **Improved UX - Clearer Deactivation Notices:** Admin notices for auto-deactivated modules have been rephrased.

### Fixed
- **UI Bug - Modal Styling:** Fixed a CSS scope issue with variables in modals.
- **UI Bug - Inconsistent State:** Fixed an issue where the UI did not reflect a module's actual deactivated status.

---

## [2.2.0] - 2025-08-19

This is a major feature release that enhances the user experience and provides a foundation for future advanced configurations.

### Added
- **Major Feature: Settings Tab & Developer Mode.** A new "Settings" tab has been added with a "Developer Mode" toggle to securely enable or disable advanced features.
- **Live Dashboard Updates:** The statistics on the Dashboard tab now update instantly via JavaScript.

### Changed
- **Hardened - Robust Scope Validation:** The framework now performs strict validation of the `Scope` header at every stage.
- **Improved UX - Menu Position:** The main "WandTech" admin menu item has been moved to a more logical position.
- **Improved UX - Notice Placement:** AJAX admin notices now appear in a dedicated container.
- **Improved UX - Settings UI:** The "Save Changes" button in the Settings tab is now placed in a distinct footer section.

### Fixed
- **UI Bug - Dynamic Feature Toggling:** Fixed a bug where developer tools would not appear after enabling Developer Mode without a page refresh.

---

## [2.1.1] - 2025-08-17

This is a polishing and stability release.

### Changed
- **Improved UI Branding:** Replaced the default Dashicon with a custom SVG logo and added the logo to the Console header.
- **Improved UX - Consistent Action Colors:** Unified the color for all positive actions.
- **Improved UX - RTL Footer Layout:** The custom console footer now correctly adjusts for RTL languages.
- **Improved UX - Integrated UI:** The default WordPress footer is now hidden on the Console page.

### Fixed
- **Critical Bug - Admin Color Schemes:** Resolved a CSS specificity issue that broke UI compatibility with Admin Color Schemes.
- **Stability - Duplicate Plugin Bug:** Fixed an issue where the plugin could appear duplicated.
- **Stability - Gravatar Loading:** Hardened the "About" tab to correctly display the author's Gravatar.

---

## [2.1.0] - 2025-08-13

This is a major feature and stability update, introducing a full suite of module management tools.

### Added
- **Module Installer:** A new core module to install modules from a `.zip` file.
- **Module Deletion:** Ability to securely delete inactive modules from the UI.
- **Live Module Search & Filters:** A search bar and status filters with real-time counters.
- **Console Dashboard:** A new core module providing an at-a-glance system status.

### Changed
- **Centralized Header Translation:** Refactored the header translation system to be automatic.
- **Improved UX - AJAX Notices:** Replaced `alert()` with contextual WordPress admin notices.

### Fixed
- **Critical Error on Missing Modules:** Implemented a self-healing mechanism to clean up orphaned module entries from the database.

### Security
- **Hardened Module Deletion:** Added extra security checks for the delete functionality.

---

## [2.0.0] - 2025-08-10

This release marks the complete re-architecture of the framework into the "WandTech Console". It establishes the foundational, high-performance, and secure core upon which all future features are built.

### Added
- **Core vs. Optional Module Architecture:** Separated the framework into a stable core (`/system`) and user-configurable optional modules (`/modules`).
- **Advanced Dependency Management:** Introduced the `Requires Plugins` header to prevent module activation if dependencies are not met.
- **Extensible, Filter-Based UI:** The Console's tabbed interface is now built dynamically via the `wandtech_console_register_tabs` filter.
- **Persistent Active Tab:** The Console now remembers the user's last active tab using `localStorage`.
- **Conditional, Context-Aware Loading:** The core performance feature. Modules are loaded based on their `Scope` (`admin`, `frontend`, or `all`).
- **Path Traversal Protection:** Hardened the module loading mechanism.

---

## [1.0.0] - 2025-08-01

The initial public release of the modular framework concept. This version focused on proving the core architectural pattern of loading features on demand.

### Added
- **Proof of Concept: Modular System.** The foundational logic for scanning a `modules` directory and selectively loading PHP files was implemented.
- **Basic `Scope` Control:** The first implementation of the `Scope` header to conditionally load code on either the admin or frontend.
- **Simple Activation/Deactivation:** A basic mechanism to toggle modules on or off.