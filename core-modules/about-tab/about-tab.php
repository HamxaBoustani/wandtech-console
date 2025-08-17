<?php
/**
 * Core Module Name: About Tab
 * Description:      Provides the "About" tab for the WandTech Console, including author info and project links.
 * Version:          1.2.0
 * Author:           Hamxa
 * Scope:            admin
 */

if (!defined('ABSPATH')) exit;

if (defined('WANDTECH_ABOUT_TAB_LOADED')) {
    return;
}
define('WANDTECH_ABOUT_TAB_LOADED', true);

class Wandtech_About_Tab {

    /**
     * Constructor. Hooks into the framework's tab registration system.
     */
    public function __construct() {
        add_filter('wandtech_console_register_tabs', [$this, 'register_tab']);
    }

    /**
     * Registers the "About" tab with the console's UI.
     *
     * @param array $tabs The existing array of tabs.
     * @return array The modified array of tabs.
     */
    public function register_tab(array $tabs): array {
        $tabs['about'] = [
            'title'    => __('About', 'wandtech-console'),
            'callback' => [$this, 'render_content'],
            'priority' => 100, // A high priority to ensure it's one of the last tabs.
        ];
        return $tabs;
    }

    /**
     * Renders the HTML content for the "About" tab.
     */
    public function render_content() {
        // --- Robust Gravatar URL Generation ---
        // 1. Set your email here.
        $author_email = 'Hamxa.Boustani@gmail.com';

        // 2. Create the MD5 hash required by Gravatar.
        $hash = md5(strtolower(trim($author_email)));

        // 3. Construct the URL directly. This method is independent of WordPress users.
        $gravatar_url = sprintf('https://secure.gravatar.com/avatar/%s?s=128&d=mm&r=g', $hash);
        ?>

        <style>
            /* Local styles for the About tab to keep it self-contained */
            .wandtech-about-container { max-width: 800px; margin: 20px auto 0; }
            .wandtech-about-header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; margin-bottom: 30px; }
            .wandtech-about-header h3 { font-size: 1.8em; margin: 0; color: var(--text-color); }
            .wandtech-about-header p { font-size: 1.2em; color: var(--subtle-text-color); margin-top: 5px; }
            .wandtech-about-section { margin-bottom: 40px; display: flex; gap: 30px; align-items: flex-start; }
            .wandtech-about-section.creator-section img { border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .wandtech-about-section .content { flex-grow: 1; }
            .wandtech-about-section h4 { font-size: 1.3em; color: var(--text-color); margin-top: 0; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; }
            .wandtech-action-buttons { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
            .wandtech-action-buttons .button { font-size: 14px; padding: 8px 15px !important; height: auto !important; line-height: 1.5 !important; }
            .wandtech-action-buttons .button .dashicons { margin-right: 5px; vertical-align: text-bottom; }
        </style>

        <div class="wandtech-about-container">

            <div class="wandtech-about-header">
                <h3><?php esc_html_e('More Power, More Performance.', 'wandtech-console'); ?></h3>
                <p><?php esc_html_e('Thank you for choosing WandTech Console!', 'wandtech-console'); ?></p>
            </div>

            <div class="wandtech-about-section creator-section">
                <img src="<?php echo esc_url($gravatar_url); ?>" alt="Hamxa Boustani" width="128" height="128">
                <div class="content">
                    <h4><?php esc_html_e('About the Author', 'wandtech-console'); ?></h4>
                    <p><?php
                        printf(
                            wp_kses(
                                __('This plugin was crafted with ❤️ by <strong>%s</strong>, a passionate developer dedicated to creating high-quality, performant, and user-friendly solutions for WordPress.', 'wandtech-console'),
                                [
                                    'strong' => [],
                                    'a' => ['href' => true, 'target' => true, 'rel' => true]
                                ]
                            ),
                            '<a href="https://github.com/HamxaBoustani/" target="_blank" rel="noopener noreferrer">Hamxa Boustani</a>'
                        );
                    ?></p>
                </div>
            </div>
            
            <div class="wandtech-about-section">
                <div class="content">
                    <h4><?php esc_html_e('Get Involved & Stay Connected', 'wandtech-console'); ?></h4>
                    <p><?php esc_html_e('Your support helps this open-source project grow. Please consider starring the repository on GitHub, reporting any issues you find, or connecting on social media.', 'wandtech-console'); ?></p>
                    <div class="wandtech-action-buttons">
                        <!-- <a href="#" class="button button-primary" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-admin-site-alt3"></span>
                            <?php esc_html_e('Visit WandTech.com', 'wandtech-console'); ?>
                        </a> -->
                        <a href="https://github.com/HamxaBoustani/wandtech-console/" class="button button-secondary" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php esc_html_e('Star on GitHub', 'wandtech-console'); ?>
                        </a>
                        <a href="https://github.com/HamxaBoustani/wandtech-console/issues" class="button button-secondary" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e('Report an Issue', 'wandtech-console'); ?>
                        </a>
                        <a href="https://x.com/HamxaBoustani" class="button button-secondary" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-twitter"></span>
                            <?php esc_html_e('Follow on X', 'wandtech-console'); ?>
                        </a>
                        <!-- <a href="#" class="button button-secondary" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-linkedin"></span>
                            <?php esc_html_e('Connect on LinkedIn', 'wandtech-console'); ?>
                        </a> -->
                    </div>
                </div>
            </div>

        </div>

        <?php
    }
}

new Wandtech_About_Tab();