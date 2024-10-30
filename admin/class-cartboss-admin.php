<?php


class Cartboss_Admin {
    const PLUGIN_SLUG = 'cartboss';
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style('cartboss-bootstrap-grid', plugin_dir_url(__FILE__) . 'css/bootstrap-grid.min.css', array(), $this->version, 'all');
        wp_enqueue_style('cartboss-style', plugin_dir_url(__FILE__) . 'css/cartboss-admin.css', array(), $this->version, 'all');
    }


    public function enqueue_scripts() {
        wp_enqueue_script('cartboss-loading-overlay', 'https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js', $this->version, false);
        wp_enqueue_script('cartboss-js', plugin_dir_url(__FILE__) . 'js/cartboss-admin.js', array('jquery'), $this->version, false);
    }

    function cb_admin_notices() {
        $is_cb_page = self::PLUGIN_SLUG === Cartboss_Utils::get_array_value($_GET, 'page');
        $is_valid_api = Cartboss_Options::get_is_valid_api_key();

        if (!$is_valid_api && !$is_cb_page) {
            $url = admin_url('admin.php?page=' . self::PLUGIN_SLUG);

            echo '
                <div class="notice notice-error">
                     <p>🚨[CartBoss]🚨 API key is not valid. Please set API KEY <a href="' . $url . '">here</a>.</p>
                </div>
             ';
        }

        $disable_cron = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $last_cron_at = Cartboss_Options::get_cron_timestamp();

        /*if ($is_valid_api && $last_cron_at > 0 && (time() - $last_cron_at) > 60 * 60 * 24) {
            echo '
                <div class="notice notice-error">
                     <p>🚨[CartBoss]🚨 CRON-jobs are required for CartBoss to run properly. Please <a href="">refer to this site</a> for more info.</p>
                </div>
             ';
        }*/

        if (!empty(Cartboss_Options::get_latest_version()) && Cartboss_Options::get_latest_version() > CARTBOSS_VERSION) {
            echo '
                <div class="notice notice-error">
                     <p>🚨[CartBoss]🚨 New plugin version available (' . Cartboss_Options::get_latest_version() . '). Please <a href="' . admin_url('plugins.php') . '">update</a> your plugin to receive latest improvements.</p>
                </div>
             ';
        }

        if ($is_valid_api && !Cartboss_Options::get_has_balance()) {
            echo '
                <div class="notice notice-error">
                     <p>🚨[CartBoss]🚨 Low balance warning. Please add funds <a href="https://app.cartboss.io" target="_blank">to your account</a> to continue recovering abandoned carts.</p>
                </div>
             ';
        }

    }

    function cb_settings_link($links) {
        // We shouldn't encourage editing our plugin directly.
        unset($links['edit']);

        // Add our custom links to the returned array value.
        return array_merge(array(
            '<a href="' . admin_url('admin.php?page=' . self::PLUGIN_SLUG) . '">Settings</a>'
        ), $links);
    }

    public function cb_admin_menu() {
        add_menu_page('CartBoss', 'CartBoss 🔥', 'manage_options', self::PLUGIN_SLUG, array($this, 'cb_settings_init'), 'dashicons-smartphone', 1234);
    }

    function cb_settings_init() {
        cartboss_include("admin/partials/cartboss-admin-display.php");
    }

    function cb_admin_form_post() {
        // phone on top
        Cartboss_Options::set_is_phone_field_on_top(Cartboss_Utils::is_true(Cartboss_Utils::get_array_value($_POST, 'cb_phone_at_top', false)));

        // consent
        Cartboss_Options::set_is_marketing_checkbox_visible(Cartboss_Utils::is_true(Cartboss_Utils::get_array_value($_POST, 'cb_marketing_checkbox_enabled', false)));

        // consent text
        $cb_consent_label = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'cb_marketing_checkbox_label', ''));
        if (empty($cb_consent_label)) {
            $cb_consent_label = Cartboss_Options::get_default_marketing_checkbox_label();
        }
        Cartboss_Options::set_marketing_checkbox_label($cb_consent_label);

        // update if wpml enabled
        do_action('wpml_register_single_string', Cartboss_Constants::TEXT_DOMAIN, Cartboss_Constants::TEXT_KEY_CONSENT_LABEL, Cartboss_Options::get_marketing_checkbox_label());

        // ignored roles
        Cartboss_Options::set_ignored_roles(Cartboss_Utils::get_array_value($_POST, 'cb_roles', array()));

        //// API KEY
        $new_api_key = Cartboss_Utils::get_array_value($_POST, 'cb_api_key', '');
        Cartboss_Options::set_api_key($new_api_key);

        try {
            // re-init api manager
            Cartboss_Api_Manager::init(Cartboss_Config::instance()->get('api_host'), $new_api_key, $this->version);
        } catch (Throwable $e) {
            error_log("[CARTBOSS] Failed to reset api client settings");
        }

        // fetch new settings
        Cartboss_Cron_Ping::instance()->do_handle();

        wp_redirect(admin_url('admin.php?page=' . self::PLUGIN_SLUG));
    }
}
