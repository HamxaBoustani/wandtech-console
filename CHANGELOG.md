# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Your next amazing feature.

---

## [2.1.1] - 2025-08-17

This is a polishing and stability release that focuses on improving the user interface, user experience, and overall robustness of the framework.

### Changed
- **Improved UI Branding:** Replaced the default Dashicon with a custom SVG logo in the admin menu for a stronger brand identity.
- **Improved UI Branding:** Added the main WandTech logo to the header of the Console page.
- **Improved UX - Consistent Action Colors:** Unified the color for all positive actions (activating a module, active state border) to use the primary brand color for better visual consistency.
- **Improved UX - RTL Footer Layout:** The custom console footer now correctly adjusts its layout for right-to-left (RTL) languages.
- **Improved UX - Integrated UI:** The default WordPress footer ("Thank you for creating...") and version number are now hidden on the Console page for a cleaner, more integrated application-like feel.

### Fixed
- **Critical Bug - Admin Color Schemes:** Resolved a complex CSS specificity issue that prevented the Console's UI from correctly adopting the user's chosen Admin Color Scheme. The UI is now fully compatible with all color schemes.
- **Stability - Duplicate Plugin Bug:** Fixed an issue where the plugin could appear duplicated in the plugins list if a file header was accidentally copied into another file.
- **Stability - Gravatar Loading:** Hardened the "About" tab to correctly display the author's Gravatar on all WordPress installations, regardless of whether the user exists on that specific site.
- **UI Bug - Admin Menu Icon:** Corrected a potential syntax error in the `add_menu_page` function call.
- **UI Bug - Close Button Style:** Fixed a styling issue with the close button on the Live Sales Notification module.
- **UI Bug - Active Tab Focus:** Removed the default browser focus ring from an active tab to improve the visual clarity of the UI.
- **UI Bug - Missing Dashicon:** Replaced a non-existent `dashicons-bug` with the correct `dashicons-hammer` in the "About" tab.

---

## [2.1.0] - 2025-08-13

This is a major feature and stability update, introducing a full suite of module management tools and significant architectural improvements.

### Added
- **Module Installer:** A new core module to install modules from a `.zip` file.
- **Module Deletion:** Ability to securely delete inactive modules from the UI.
- **Live Module Search:** A debounced search bar to instantly filter modules.
- **Status Filters & Live Counts:** "All," "Active," and "Inactive" filters with real-time counters.
- **Console Dashboard:** A new core module providing an at-a-glance system status.
- **Professional Documentation:** Added `CHANGELOG.md` and a comprehensive `README.md`.

### Changed
- **Centralized Header Translation:** Refactored the header translation system to be automatic and handled by the core, simplifying module development.
- **Improved UX - AJAX Notices:** Replaced `alert()` with contextual WordPress admin notices.
- **Improved UX - RTL Layout:** The "Modules" tab header is now fully RTL-aware.

### Fixed
- **Critical Error on Missing Modules:** Implemented a self-healing mechanism to automatically clean up orphaned module entries from the database, preventing fatal errors.

### Security
- **Whitelist Validation for AJAX Actions.**
- **Hardened Module Deletion:** Added extra security checks for the delete functionality.

---

## [2.0.0] - 2025-08-10

The initial release of the completely re-architected "WandTech Console". This version focused on building a powerful, performant, and secure foundation.

### Added
- **Core vs. Optional Module Architecture:** Separated the framework into a stable core (`/core-modules`) and user-configurable optional modules (`/modules`).
- **Advanced Dependency Management:** Introduced the `Requires Plugins` header to prevent module activation if dependencies are not met.
- **Extensible, Filter-Based UI:** The Console's tabbed interface is now built dynamically via the `wandtech_console_register_tabs` filter.
- **Persistent Active Tab:** The Console now remembers the user's last active tab using `localStorage`.
- **Conditional, Context-Aware Loading:** The core performance feature. Modules are loaded based on their `Scope` (`admin`, `frontend`, or `all`).
- **Path Traversal Protection:** Hardened the module loading mechanism using `realpath()`.

---