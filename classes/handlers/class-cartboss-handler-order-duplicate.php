<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler_Order_Duplicate')) :

    class Cartboss_Hook_Handler_Order_Duplicate extends Cartboss_Hook_Handler {
        function add_actions() {
            if (!is_admin()) {
                add_action("woocommerce_before_checkout_process", array($this, 'handle'), Cartboss_Constants::CB_PRIORITY_MIN);
            }
        }

        function handle() {
            try {
                $cookie_session_token = Cartboss_Better_Session_Manager::instance()->get_token();
                if (Cartboss_Token_Database_Manager::instance()->exists($cookie_session_token)) {
                    wp_redirect(Cartboss_Hook_Handler_Session_Reset::get_session_reset_url(), 303);
                    exit();
                }
            } catch (Throwable $e) {
                Cartboss_Api_manager::instance()->log_error("[Order Duplicate Handler] failed: {$e->getMessage()}");
            }
        }
    }

endif; // class_exists check

?>