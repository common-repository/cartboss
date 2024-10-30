<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler_Order_Create')) :

    class Cartboss_Hook_Handler_Order_Create extends Cartboss_Hook_Handler {
        const ORDER_META_SESSION_TOKEN = 'cb_session_token';
        const ORDER_META_ATTRIBUTION_TOKEN = 'cb_attribution_token';
        const ORDER_META_PHONE = 'cb_phone';

        function add_actions() {
            add_action('woocommerce_new_order', array($this, 'handle'), Cartboss_Constants::CB_PRIORITY_MAX, 2);
        }

        function handle($order_id, $order) {
            if (Cartboss_Utils::is_logged_in_admin() || is_admin()) {
                return;
            }

            // this logic stores various data to order meta fields
            // session token, because of crontab calls, cookie is not accessible
            // phone, because paypal can remove this value
            try {
                $wc_order = $order;
                if (empty($wc_order))
                    $wc_order = Cartboss_Utils::get_order($order_id);

                if (empty($wc_order)) {
                    Cartboss_Api_manager::instance()->log_error("[Order Create Handler] Failed to retrieve WC_Order for order id '{$order_id}'");
                    return;
                }

                try {
                    // PHONE NUMBER
                    if (empty($wc_order->get_meta(self::ORDER_META_PHONE))) {
                        $cb_local_cart = Cartboss_Order_Database_Manager::instance()->get(Cartboss_Better_Session_Manager::instance()->get_token());
                        if (isset($cb_local_cart)) {
                            $cb_cart_payload = $cb_local_cart->payload;
                            if (!empty($cb_cart_payload)) {
                                $meta_phone = Cartboss_Utils::get_array_value(Cartboss_Utils::get_array_value(json_decode($cb_cart_payload, true), 'billing_address', array()), 'phone', null);
                                if (!empty($meta_phone)) {
                                    $wc_order->update_meta_data(self::ORDER_META_PHONE, $meta_phone);
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {
                    Cartboss_Api_manager::instance()->log_error("[Order Create Handler PHONE] failed: {$e->getMessage()}");
                }

                try {
                    // SESSION TOKEN
                    if (!Cartboss_Better_Session_Manager::is_valid_token($wc_order->get_meta(self::ORDER_META_SESSION_TOKEN))) {
                        $wc_order->update_meta_data(self::ORDER_META_SESSION_TOKEN, Cartboss_Better_Session_Manager::instance()->get_token());
                    }
                } catch (Throwable $e) {
                    Cartboss_Api_manager::instance()->log_error("[Order Create Handler SESSION] failed: {$e->getMessage()}");
                }

                try {
                    // ATTRIBUTION TOKEN
                    if (!Cartboss_Attribution_Manager::is_valid_token($wc_order->get_meta(self::ORDER_META_ATTRIBUTION_TOKEN))) {
                        $wc_order->update_meta_data(self::ORDER_META_ATTRIBUTION_TOKEN, Cartboss_Attribution_Manager::instance()->get_token());
                    }
                } catch (Throwable $e) {
                    Cartboss_Api_manager::instance()->log_error("[Order Create Handler ATTRIBUTION] failed: {$e->getMessage()}");
                }

                $wc_order->save_meta_data();
                $wc_order->save();

            } catch (Throwable $e) {
                Cartboss_Api_manager::instance()->log_error("[Order Create Handler] failed: {$e->getMessage()}");
            }
        }
    }

endif; // class_exists check

?>