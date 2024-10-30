<?php


class Cartboss {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('CARTBOSS_VERSION')) {
            $this->version = CARTBOSS_VERSION;
        } else {
            $this->version = 'CBOI';
        }
        $this->plugin_name = 'cartboss';

        define('CARTBOSS_PATH', plugin_dir_path(dirname(__FILE__)));

        include_once(CARTBOSS_PATH . 'cartboss-utility-functions.php');

        // vendor
        cartboss_include('vendor/autoload.php');

        // config
        cartboss_include('config/class-cartboss-constants.php');
        cartboss_include('config/class-cartboss-config.php');
        cartboss_include('config/class-cartboss-options.php');

        // various
        cartboss_include('classes/class-cartboss-utils.php');
        cartboss_include('classes/class-cartboss-singleton.php');
        cartboss_include('classes/class-cartboss-database.php');
        cartboss_include('classes/class-cartboss-custom-routes.php');
        cartboss_include('classes/class-cartboss-exceptions.php');
        cartboss_include('classes/class-cartboss-api-client.php');
        cartboss_include('classes/libs/class-cartboss-cookie-helper.php');
        cartboss_include('classes/libs/class-cartboss-session-helper.php');

        // ajax
        cartboss_include('classes/ajax/class-cartboss-ajax.php');
        cartboss_include('classes/ajax/class-cartboss-ajax-checkout.php');
        cartboss_include('classes/ajax/class-cartboss-ajax-checkout-fields.php');

        // cron
        cartboss_include('classes/cron/class-cartboss-cron.php');
//        cartboss_include('classes/cron/class-cartboss-cron-reschedule.php');
        cartboss_include('classes/cron/class-cartboss-cron-ping.php');
//        cartboss_include('classes/cron/class-cartboss-cron-sync.php');
        cartboss_include('classes/cron/class-cartboss-cron-clean.php');

        // managers
//        cartboss_include('classes/managers/class-cartboss-event-database-manager.php');
        cartboss_include('classes/managers/class-cartboss-token-database-manager.php');
        cartboss_include('classes/managers/class-cartboss-order-database-manager.php');
        cartboss_include('classes/managers/class-cartboss-better-session-manager.php');
        cartboss_include('classes/managers/class-cartboss-attribution-manager.php');
        cartboss_include('classes/managers/class-cartboss-api-manager.php');
        cartboss_include('classes/managers/class-cartboss-discount-manager.php');
        cartboss_include('classes/managers/class-cartboss-contact-manager.php');
        //cartboss_include('classes/managers/class-cartboss-event-sender.php');

        // models
        cartboss_include('classes/models/class-cartboss-base-model.php');
        cartboss_include('classes/models/class-cartboss-money-model.php');
        cartboss_include('classes/models/class-cartboss-contact-model.php');
        cartboss_include('classes/models/class-cartboss-event-model.php');
        cartboss_include('classes/models/class-cartboss-order-model.php');
        cartboss_include('classes/models/class-cartboss-site-model.php');

        // hook handlers
        cartboss_include('classes/handlers/class-cartboss-handler.php');
        cartboss_include('classes/handlers/class-cartboss-handler-order-duplicate.php');
        cartboss_include('classes/handlers/class-cartboss-handler-order-purchase.php');
        cartboss_include('classes/handlers/class-cartboss-handler-order-create.php');
        cartboss_include('classes/handlers/class-cartboss-handler-order-restore.php');
        cartboss_include('classes/handlers/class-cartboss-handler-session-reset.php');
        cartboss_include('classes/handlers/class-cartboss-handler-customize-checkout-fields.php');
//        cartboss_include('classes/handlers/class-cartboss-handler-prefill-checkout-fields.php');

        // load appropriate config
        Cartboss_Config::init(Cartboss_Config::PRODUCTION);
        if (Cartboss_Utils::is_staging()) {
            Cartboss_Config::init(Cartboss_Config::STAGING);
        } elseif (Cartboss_Utils::is_development()) {
            Cartboss_Config::init(Cartboss_Config::DEVELOPMENT);
        }

        // enable debug
        if (Cartboss_Config::instance()->get('debug')) {
            define('WP_DEBUG', true);
            define('WP_DEBUG_DISPLAY', false);
            define('WP_DEBUG_LOG', true);
            error_reporting(E_ALL);
        }

        // init managers
        Cartboss_Api_Manager::init(Cartboss_Config::instance()->get('api_host'), Cartboss_Options::get_api_key(), $this->version);
        Cartboss_Discount_Manager::init(Cartboss_Options::get_api_key());
//        Cartboss_Event_Database_Manager::instance();
        Cartboss_Token_Database_Manager::instance();
        Cartboss_Order_Database_Manager::instance();
        Cartboss_Attribution_Manager::init();
        Cartboss_Better_Session_Manager::init();
        //Cartboss_Event_Sender::init();

        // init crons
        Cartboss_Cron_Clean::instance();
        Cartboss_Cron_Ping::instance();
//        Cartboss_Cron_Sync::instance();

        // init hook handlers
        // Cartboss_Hook_Handler_Order_Duplicate::instance(); not using this any more, because it could cause issues with some WPS
        Cartboss_Hook_Handler_Order_Purchase::instance();
        Cartboss_Hook_Handler_Order_Create::instance();
        Cartboss_Hook_Handler_Order_Restore::instance();
        Cartboss_Hook_Handler_Session_Reset::instance();
        Cartboss_Hook_Handler_Customize_Checkout_Fields::instance();

        // init ajax
        Cartboss_Ajax_Checkout::instance();
        Cartboss_Ajax_Checkout_Fields::instance();

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    public function cb_init() {

    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cartboss-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cartboss-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-cartboss-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-cartboss-public.php';
        $this->loader = new Cartboss_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Cartboss_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new Cartboss_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        $this->loader->add_action('admin_notices', $plugin_admin, 'cb_admin_notices');
        $this->loader->add_action('network_admin_notices', $plugin_admin, 'cb_admin_notices');

        $this->loader->add_action('admin_menu', $plugin_admin, 'cb_admin_menu');
        $this->loader->add_action('admin_post_cartboss_form_save', $plugin_admin, 'cb_admin_form_post');

        $this->loader->add_filter('plugin_action_links_' . CARTBOSS_PLUGIN_NAME, $plugin_admin, 'cb_settings_link');

    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    private function define_public_hooks() {
        $plugin_public = new Cartboss_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles', Cartboss_Constants::CB_PRIORITY_MAX);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_loader() {
        return $this->loader;
    }
}
