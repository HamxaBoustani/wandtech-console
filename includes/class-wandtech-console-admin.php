<?php
/**
 * Handles the admin area "container" for the WandTech Console.
 *
 * @package    Wandtech_Console
 */
if (!defined('ABSPATH')) exit;

class Wandtech_Console_Admin {

    private $tabs = [];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    
        // --- HOOKS FOR FOOTER MODIFICATION ---
        add_filter('admin_footer_text', [$this, 'remove_admin_footer_text'], 99);
        add_filter('update_footer', [$this, 'remove_admin_footer_version'], 99);
    }

    /**
     * Helper function to get the SVG icon as a base64-encoded data URI.
     * Caches the result in a static variable for performance.
     *
     * @return string The data URI for the SVG icon.
     */
    private function get_menu_icon_svg() {
        static $svg_icon = null; // Use static variable for in-memory caching.
        if ($svg_icon !== null) {
            return $svg_icon;
        }

        $icon_path = WANDTECH_CONSOLE_PATH . 'assets/images/wandtech-logo.svg';
        if (!file_exists($icon_path)) {
            $svg_icon = 'dashicons-plugins-checked'; // Fallback to a default dashicon.
            return $svg_icon;
        }

        $svg_content = file_get_contents($icon_path);
        // We don't need to sanitize this SVG as it's a local file we control.
        $svg_icon = 'data:image/svg+xml;base64,' . base64_encode($svg_content);
        
        return $svg_icon;
    }

    public function add_plugin_page() {
        add_menu_page(
            __('WandTech Console', 'wandtech-console'),
            __('WandTech', 'wandtech-console'),
            'manage_options',
            'wandtech-console',
            [$this, 'create_admin_page'],
            $this->get_menu_icon_svg(),
            // 'data:image/svg+xml;base64,' . base64_encode(
            //     file_get_contents(WANDTECH_CONSOLE_PATH . 'assets/images/wandtech-logo.svg')
            // ),
            20
        );
    }

    /**
     * [HELPER] Checks if the current admin screen is the WandTech Console page.
     *
     * @return bool
     */
    private function is_console_page() {
        if (!function_exists('get_current_screen')) {
            return false;
        }
        $screen = get_current_screen();
        return ($screen && $screen->id === 'toplevel_page_wandtech-console');
    }

    /**
     * Conditionally removes the "Thank you for creating..." text.
     */
    public function remove_admin_footer_text($footer_text) {
        if ($this->is_console_page()) {
            return '';
        }
        return $footer_text;
    }

    /**
     * Conditionally removes the WordPress version number text.
     */
    public function remove_admin_footer_version($footer_version) {
        if ($this->is_console_page()) {
            return '';
        }
        return $footer_version;
    }

    public function enqueue_assets($hook) {
        if ('toplevel_page_wandtech-console' !== $hook) return;

        wp_enqueue_style('wandtech-console-admin-css', WANDTECH_CONSOLE_URL . 'assets/css/admin.css', [], WANDTECH_CONSOLE_VERSION);
        wp_enqueue_script('wandtech-console-admin-js', WANDTECH_CONSOLE_URL . 'assets/js/admin.js', ['jquery'], WANDTECH_CONSOLE_VERSION, true);
        
        $js_data = [
            'ajax_url'         => admin_url('admin-ajax.php'),
            'nonce_toggle'     => wp_create_nonce('wandtech_console_module_nonce'),
            'nonce_delete'     => wp_create_nonce('wandtech_console_delete_nonce'),
            'generic_error'    => __('An unexpected error occurred.', 'wandtech-console'),
            'installing_text'  => __('Installing...', 'wandtech-console'),
            'install_now_text' => __('Install Now', 'wandtech-console'),
        ];
        
        $js_data = apply_filters('wandtech_console_admin_js_data', $js_data);

        wp_localize_script('wandtech-console-admin-js', 'wandtech_console_ajax', $js_data);
    }
    
    private function setup_tabs() {
        $registered_tabs = apply_filters('wandtech_console_register_tabs', []);
        if (!is_array($registered_tabs)) $registered_tabs = [];
        uasort($registered_tabs, fn($a, $b) => ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10));
        $this->tabs = $registered_tabs;
    }

    public function create_admin_page() {
        $this->setup_tabs();
        ?>
        <div class="wrap wandtech-wrap">
            <!-- <div class="wandtech-header-logo">
                <img src="<?php echo esc_url(WANDTECH_CONSOLE_URL . 'assets/images/wandtech-logo.svg'); ?>" 
                    alt="WandTech Console Logo">
            </div> -->
            <div class="wandtech-header-logo">
                <?php
                $logo_path = WANDTECH_CONSOLE_PATH . 'assets/images/wandtech-logo.svg';
                if (file_exists($logo_path)) {
                    echo file_get_contents($logo_path);
                }
                ?>
            </div>
            <h1><?php esc_html_e('WandTech Console', 'wandtech-console'); ?></h1>
            <p class="wandtech-subtitle"><?php esc_html_e('A high-performance console for managing your site\'s modular features.', 'wandtech-console'); ?></p>

            <?php if (!empty($this->tabs)) : ?>
                <h2 class="nav-tab-wrapper">
                    <?php $i = 0; foreach ($this->tabs as $slug => $tab) : $class = 'nav-tab' . ($i === 0 ? ' nav-tab-active' : ''); echo '<a href="#'.esc_attr($slug).'" class="'.esc_attr($class).'">'.esc_html($tab['title']).'</a>'; $i++; endforeach; ?>
                </h2>
                <?php $i = 0; foreach ($this->tabs as $slug => $tab) : $class = 'tab-content' . ($i === 0 ? ' active' : ''); echo '<div id="'.esc_attr($slug).'" class="'.esc_attr($class).'">'; if (is_callable($tab['callback'])) call_user_func($tab['callback']); echo '</div>'; $i++; endforeach; ?>
            <?php else : ?>
                <div class="notice notice-warning inline"><p><strong><?php esc_html_e('No Console Modules Found', 'wandtech-console'); ?></strong><br><?php esc_html_e('The WandTech Console is active, but no core modules were found to display the UI. Please ensure the `core-modules` directory is present and not empty.', 'wandtech-console'); ?></p></div>
            <?php endif; ?>
            
            <footer class="wandtech-console-footer">
                <div class="footer-credit">
                    <?php 
                    $author_link = '<a href="https://github.com/HamxaBoustani/" target="_blank" rel="noopener noreferrer">Hamxa Boustani</a>';
                    printf(
                        wp_kses(
                            __('Made with %s by %s', 'wandtech-console'),
                            [
                                'span' => ['class' => true, 'style' => true],
                                'a'    => ['href' => true, 'target' => true, 'rel' => true]
                            ]
                        ),
                        '<span class="dashicons dashicons-heart"></span>',
                        $author_link
                    ); 
                    ?>
                </div>
                <div class="footer-version">
                    <?php 
                    printf(
                        esc_html__('Version %s', 'wandtech-console'),
                        WANDTECH_CONSOLE_VERSION
                    ); 
                    ?>
                </div>
            </footer>
        </div>
        <?php
    }
}