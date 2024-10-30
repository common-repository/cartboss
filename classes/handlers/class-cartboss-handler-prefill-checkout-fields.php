<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler_Prefill_Checkout_Fields')) :

    class Cartboss_Hook_Handler_Prefill_Checkout_Fields extends Cartboss_Hook_Handler {
        function add_actions() {
            add_filter('woocommerce_checkout_fields', array($this, 'handle'), 100, 1);
        }

        public function handle($fields = array()) {
            if (!Cartboss_Utils::is_actual_checkout_page()) {
                return $fields;
            }

            // (thx to WSAC plugin)
            // In case this is user's account page, do not restore data. Added since some plugins change the My account form and may trigger this function unnecessarily
            if (is_account_page()) {
                return $fields;
            }

            // restore order notes
            $order_notes_value = Cartboss_Meta_Storage_Manager::instance()->get_value(Cartboss_Constants::CB_SESSION_ORDER_METADATA, '');
            if (array_key_exists('order', $fields) && array_key_exists('order_comments', $fields['order']) && !empty($order_notes_value)) {
                $fields['order']['order_comments']['default'] = sanitize_text_field($order_notes_value);
            }

            // accepts marketing
            if (array_key_exists('billing', $fields) && key_exists(Cartboss_Constants::CB_FIELD_ACCEPTS_MARKETING, $fields['billing'])) {
                $fields['billing'][Cartboss_Constants::CB_FIELD_ACCEPTS_MARKETING]['default'] = Cartboss_Utils::is_true(Cartboss_Meta_Storage_Manager::instance()->get_value(Cartboss_Constants::CB_METADATA_ACCEPTS_MARKETING, false));
            }

            return $fields;
        }
    }

endif; // class_exists check

?>