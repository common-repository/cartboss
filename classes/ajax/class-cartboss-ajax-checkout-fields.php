<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Ajax_Checkout_Fields')) :

    class Cartboss_Ajax_Checkout_Fields extends Cartboss_Ajax {
        var $action = "cartboss_get_checkout_fields";
        var $public = true;

        function handle_request($request) {
            try {
                if (Cartboss_Utils::is_logged_in_admin()) {
                    wp_send_json_error(array('message' => "You are logged-in administrator."));
                    return;
                }

                $nonce = Cartboss_Utils::get_array_value($_POST, 'nonce');
                if (!wp_verify_nonce($nonce, 'ajax-nonce')) {
                    wp_send_json_error(array('message' => "Nonce value cannot be verified."));
                    return;
                }

                $data = array();

                $cb_local_cart = Cartboss_Order_Database_Manager::instance()->get(Cartboss_Better_Session_Manager::instance()->get_token());
                if (isset($cb_local_cart)) {
                    $cb_cart_payload = $cb_local_cart->payload;
                    if (!empty($cb_cart_payload)) {
                        $cb_cart_payload = json_decode($cb_cart_payload, true);

                        foreach ($cb_cart_payload['billing_address'] as $key => $value) {
                            if (!empty($value)) {
                                $data['billing_' . $key] = $value;
                            }
                        }

                        foreach ($cb_cart_payload['shipping_address'] as $key => $value) {
                            if (!empty($value)) {
                                $data['shipping_' . $key] = $value;
                            }
                        }
                        try {
                            $metadata = Cartboss_Utils::get_array_value($cb_cart_payload, 'metadata');
                            if ($metadata) {
                                // $data['extra_fields'] = unserialize(Cartboss_Utils::get_array_value($metadata, Cartboss_Constants::CB_METADATA_EXTRA_FIELDS, array()));

                                $data[Cartboss_Constants::CB_FIELD_ACCEPTS_MARKETING] = Cartboss_Utils::is_true(Cartboss_Utils::get_array_value($metadata, Cartboss_Constants::CB_METADATA_ACCEPTS_MARKETING, false));
                            }
                        } catch (Throwable $e) {
                        }
                    }
                }

                wp_send_json_success($data);
            } catch (Throwable $ex) {
                wp_send_json_error(array('message' => $ex));
            }
        }
    }

endif; // class_exists check

?>