<?php


class Cartboss_Public {

    private $plugin_name;

    private $version;

    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/cartboss-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        if (!Cartboss_Utils::can_run_cartboss()) {
            return;
        }
        $is_debug = Cartboss_Config::instance()->get('debug', false);
        $assets_version = $is_debug ? mt_rand(PHP_INT_MIN, PHP_INT_MAX) : $this->version;
        
        wp_enqueue_script(
            'cb-tracking-script',
            plugin_dir_url(__FILE__) . 'js/cartboss-checkout2.js',
            array('jquery'),
            $assets_version,
            true
        );

        $data = array(
            'endpoint_abandon' => WC_AJAX::get_endpoint(Cartboss_Ajax_Checkout::instance()->get_action()),
            'endpoint_populate' => WC_AJAX::get_endpoint(Cartboss_Ajax_Checkout_Fields::instance()->get_action()),
            'nonce' => wp_create_nonce('ajax-nonce'),
            'debug' => $is_debug,
            'preset_fields' => array()
        );

        wp_localize_script(
            'cb-tracking-script',
            'cb_checkout_data',
            $data
        );
    }
}
