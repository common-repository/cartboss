<?php


use League\Uri\Uri;
use League\Uri\UriModifier;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Ajax_Checkout')) :

    class Cartboss_Ajax_Checkout extends Cartboss_Ajax {
        var $action = "cartboss_save_abandoned_cart_data";
        var $public = true;

        function handle_request($request) {
            try {

                

                if (Cartboss_Utils::is_logged_in_admin()) {
                    wp_send_json_error(array('message' => "You are administrator."));
                    return;
                }

                $nonce = Cartboss_Utils::get_array_value($_POST, 'nonce');
                if (!wp_verify_nonce($nonce, 'ajax-nonce')) {
                    wp_send_json_error(array('message' => "Nonce value cannot be verified."));

                    return;
                }

                if (empty(WC()->cart)) {
                    Cartboss_Api_manager::instance()->log_error("[Checkout AJAX Handler]: WC()->cart not initialized");

                    wp_send_json_error(array('message' => "Cart inaccessible"));

                    return;
                }

                if (!Cartboss_Utils::is_cart_full()) {
                    wp_send_json_error(array('message' => "Cart empty"));

                    return;
                }

                $session_token = Cartboss_Better_Session_Manager::instance()->get_token();
                if (!Cartboss_Better_Session_Manager::is_valid_token($session_token)) {
                    wp_send_json_error(array('message' => "Cartboss session token is not valid"));

                    return;
                }

                // super important!
                // prevent ATC from happening again, if user has already placed an order in some other browser
                if (Cartboss_Token_Database_Manager::instance()->exists($session_token)) {
                    wp_send_json_error(array('message' => "Session token already consumed"));
                    return;
                }

                // event
                $cb_event = new Cartboss_AddToCart_Event_Model(Cartboss_Config::instance()->get('plugin_version'), Cartboss_Config::instance()->get('debug', false));

                // contact
                $cb_contact = new Cartboss_Contact_Model();
                $cb_contact->ip_address = Cartboss_Utils::get_visitor_ip();
                $cb_contact->user_agent = sanitize_text_field(Cartboss_Utils::get_array_value($_SERVER, 'HTTP_USER_AGENT'));
                $cb_contact->phone = Cartboss_Utils::get_array_value($_POST, 'billing_phone', Cartboss_Utils::get_array_value($_POST, 'shipping_phone'));
                $cb_contact->email = sanitize_email(Cartboss_Utils::get_array_value($_POST, 'billing_email', Cartboss_Utils::get_array_value($_POST, 'shipping_email')));
                $cb_contact->country = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_country', Cartboss_Utils::get_array_value($_POST, 'shipping_country', WC_Geolocation::geolocate_ip($cb_contact->ip_address))));
                $cb_contact->first_name = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_first_name', Cartboss_Utils::get_array_value($_POST, 'shipping_first_name')));
                $cb_contact->last_name = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_last_name', Cartboss_Utils::get_array_value($_POST, 'shipping_last_name')));
                $cb_contact->accepts_marketing = Cartboss_Utils::is_true(Cartboss_Utils::get_array_value($_POST, 'cartboss_accepts_marketing', false));
                $cb_event->contact = $cb_contact;

                if (empty($cb_contact->phone) || strlen($cb_contact->phone) < 6) {
                    wp_send_json_error(array('message' => "Phone number invalid"));

                    return;
                }

                // order
                $cb_order = new Cartboss_Order_Model();
                $cb_order->id = $session_token;
                $cb_order->value = WC()->cart->get_cart_contents_total();
                $cb_order->currency = sanitize_text_field(get_woocommerce_currency());

                // billing
                $cb_address = new Cartboss_Order_Address_Model();
                $cb_address->phone = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_phone'));
                $cb_address->email = sanitize_email(Cartboss_Utils::get_array_value($_POST, 'billing_email'));
                $cb_address->first_name = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_first_name'));
                $cb_address->last_name = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_last_name'));
                $cb_address->company = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_company'));
                $cb_address->address_1 = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_address_1'));
                $cb_address->address_2 = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_address_2'));
                $cb_address->city = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_city'));
                $cb_address->state = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_state'));
                $cb_address->postal_code = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_postcode'));
                $cb_address->country = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'billing_country'));
                $cb_order->billing_address = $cb_address;

                // shipping
                $cb_address = new Cartboss_Order_Address_Model();
                $cb_address->phone = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_phone'));
                $cb_address->email = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_email'));
                $cb_address->first_name = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_first_name'));
                $cb_address->last_name = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_last_name'));
                $cb_address->company = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_company'));
                $cb_address->address_1 = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_address_1'));
                $cb_address->address_2 = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_address_2'));
                $cb_address->city = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_city'));
                $cb_address->state = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_state'));
                $cb_address->postal_code = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_postcode'));
                $cb_address->country = sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'shipping_country'));
                $cb_order->shipping_address = $cb_address;

                $js_checkout_redirect_url = Cartboss_Utils::get_array_value($_POST, 'checkout_redirect_url', wp_get_referer());
                $js_checkout_host = Uri::createFromString($js_checkout_redirect_url)->getHost();

                // checkout url
                $checkout_url = Cartboss_Hook_Handler_Order_Restore::get_order_restore_url();
                if (!str_contains($checkout_url, $js_checkout_host)) {
                    $checkout_url = Uri::createFromString($checkout_url);
                    $checkout_url = $checkout_url->withHost($js_checkout_host);
                }

                $checkout_url = UriModifier::appendQuery($checkout_url, Cartboss_Better_Session_Manager::QUERY_VAR . '=' . $session_token);
                $cb_order->checkout_url = $checkout_url->jsonSerialize();

                // metadata
                $metadata = array(
                   Cartboss_Constants::CB_METADATA_CHECKOUT_REDIRECT_URL => $js_checkout_redirect_url,
//                    Cartboss_Constants::CB_METADATA_ORDER_COMMENTS => sanitize_text_field(Cartboss_Utils::get_array_value($_POST, 'order_comments', '')),
                   Cartboss_Constants::CB_METADATA_ACCEPTS_MARKETING => Cartboss_Utils::is_true(Cartboss_Utils::get_array_value($_POST, 'cartboss_accepts_marketing', false)),
//                    Cartboss_Constants::CB_METADATA_EXTRA_FIELDS => serialize(stripslashes(Cartboss_Utils::get_array_value($_POST, 'extra_fields'))),
                );

                try {
                    if (isset(WC()->session)) {
                        $metadata[Cartboss_Constants::CB_METADATA_LOCAL_SESSION_ID] = WC()->session->get_customer_id();
                    }
                } catch (\Throwable $th) {
                }

                $cb_order->metadata = $metadata;

                // cart
                $wc_cart = WC()->cart->get_cart();
                foreach ($wc_cart as $wc_cart_item) {
                    try {
                        $cb_cart_item = new Cartboss_Order_Cart_Item_Model();
                        $cb_cart_item->id = sanitize_text_field(Cartboss_Utils::get_array_value($wc_cart_item, 'product_id'));
                        $cb_cart_item->variation_id = Cartboss_Utils::is_valid_variation_id(Cartboss_Utils::get_array_value($wc_cart_item, 'variation_id')) ? sanitize_text_field(Cartboss_Utils::get_array_value($wc_cart_item, 'variation_id')) : null;
                        $cb_cart_item->quantity = intval(Cartboss_Utils::get_array_value($wc_cart_item, 'quantity', 1));
                        $cb_cart_item->price = floatval(Cartboss_Utils::get_array_value($wc_cart_item, 'line_total', 0));

                        /* @var WC_Product_Simple $wc_product */
                        $wc_product = Cartboss_Utils::get_array_value($wc_cart_item, 'data');
                        if (!empty($wc_product)) {
                            $cb_cart_item->name = sanitize_text_field($wc_product->get_name());
                            $cb_cart_item->image_url = Cartboss_Utils::get_product_image_url($wc_product->get_image_id());
                        }

                        $cb_order->addCartItem($cb_cart_item);
                    } catch (Exception $e) {
                        //pass
                    }
                }
                $cb_event->order = $cb_order;

                // store order for later restoration reference
                Cartboss_Order_Database_Manager::instance()->insert($session_token, $cb_order->serialize());

                // send ATC event
                Cartboss_Utils::send_event($cb_event->serialize());
                //do_action("cb_send_event", $cb_event->serialize());

                // check if can be inserted as event
//                $previous = Cartboss_Event_Database_Manager::instance()->get($session_token);
//                if (!$previous || $previous->priority != Cartboss_Event_Database_Manager::PRIORITY_HIGH) {
//                    Cartboss_Event_Database_Manager::instance()->insert($session_token, $cb_event->serialize(), Cartboss_Config::instance()->get('sync_delay_atc', 0), Cartboss_Event_Database_Manager::PRIORITY_NORMAL);
//                }

            } catch (Throwable $e) {
                Cartboss_Api_manager::instance()->log_error("[Checkout AJAX Handler]: Saving ATC event to DB failed: {$e->getMessage()}");
            }

            wp_send_json_success();
        }
    }

endif; // class_exists check

?>