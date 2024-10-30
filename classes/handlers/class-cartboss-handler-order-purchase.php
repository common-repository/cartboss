<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler_Order_Purchase')) :

    class Cartboss_Hook_Handler_Order_Purchase extends Cartboss_Hook_Handler {
        const ORDER_META_IS_PURCHASE_REPORTED = 'cb_purchase_reported';

        function add_actions() {
            add_action('woocommerce_order_status_changed', array($this, 'handle'), 0, 3);
        }

        function handle($order_id, $old_status, $new_status) {
            try {
                $wc_order = Cartboss_Utils::get_order($order_id);
                if (empty($wc_order)) {
                    Cartboss_Api_manager::instance()->log_error("[Order Purchase Handler] Failed to retrieve WC_Order for order id '{$order_id}'");
                    return;
                }

                // purchase event has already been reported
                if (Cartboss_Utils::is_true($wc_order->get_meta(self::ORDER_META_IS_PURCHASE_REPORTED))) {
                    return;
                }

                // must be paid, or on-hold, check both statuses as some implementations can do weird stuff
                if (!in_array($new_status, Cartboss_Constants::VALID_PURCHASE_STATUSES) && !in_array($wc_order->get_status(), Cartboss_Constants::VALID_PURCHASE_STATUSES)) {
                    return;
                }

                // get token from order meta
                $session_token = $wc_order->get_meta(Cartboss_Hook_Handler_Order_Create::ORDER_META_SESSION_TOKEN, true);
                
                // add this token to consumed ones
                Cartboss_Token_Database_Manager::instance()->insert($session_token, $order_id);

                // check if token is valid
                if (!Cartboss_Better_Session_Manager::is_valid_token($session_token)) {
                    return;
                }

                // attribution token
                $attribution_token = $wc_order->get_meta(Cartboss_Hook_Handler_Order_Create::ORDER_META_ATTRIBUTION_TOKEN);

                // event
                $cb_event = new Cartboss_Purchase_Event_Model(Cartboss_Config::instance()->get('plugin_version'), Cartboss_Config::instance()->get('debug', false));
                $cb_event->attribution = $attribution_token;

                // contact

                // phone from billing address
                $phone = $wc_order->get_billing_phone();

                // try from shipping address (WC 5.6+)
                if (empty($phone) && method_exists($wc_order, 'get_shipping_phone')) {
                    $phone = $wc_order->get_shipping_phone();
                }

                // try from order meta data
                if (empty($phone)) {
                    $phone = $wc_order->get_meta(Cartboss_Hook_Handler_Order_Create::ORDER_META_PHONE);
                }

                // try from wc_customer
                if (empty($phone)) {
                    try {
                        $wc_customer = new WC_Customer($wc_order->get_customer_id());

                        $phone = $wc_customer->get_billing_phone();
                        if (empty($phone) && method_exists($wc_customer, 'get_shipping_phone')) {
                            $phone = $wc_customer->get_shipping_phone();
                        }
                    } catch (Exception $e) {
                    }
                }

                $cb_contact = new Cartboss_Contact_Model();
                $cb_contact->phone = $phone;
                $cb_contact->email = sanitize_email($wc_order->get_billing_email());
                $cb_contact->country = sanitize_text_field(Cartboss_Utils::get_first_non_empty_value($wc_order->get_billing_country(), $wc_order->get_shipping_country(), WC_Geolocation::geolocate_ip($cb_contact->ip_address)));
                $cb_contact->first_name = sanitize_text_field(Cartboss_Utils::get_first_non_empty_value($wc_order->get_billing_first_name(), $wc_order->get_shipping_first_name()));
                $cb_contact->last_name = sanitize_text_field(Cartboss_Utils::get_first_non_empty_value($wc_order->get_billing_last_name(), $wc_order->get_shipping_last_name()));
                $cb_contact->ip_address = Cartboss_Utils::get_visitor_ip();
                $cb_contact->user_agent = sanitize_text_field(Cartboss_Utils::get_array_value($_SERVER, 'HTTP_USER_AGENT'));
                $cb_event->contact = $cb_contact;

                // order
                $cb_order = new Cartboss_Order_Model();
                $cb_order->id = $session_token;
                $cb_order->number = strval($wc_order->get_id());
                $cb_order->value = floatval($wc_order->get_total());
                $cb_order->currency = sanitize_text_field($wc_order->get_currency());
                $cb_order->is_cod = $wc_order->get_payment_method() === "cod";

                // billing
                $cb_address = new Cartboss_Order_Address_Model();
                $cb_address->phone = $phone;
                $cb_address->email = sanitize_text_field($wc_order->get_billing_email());
                $cb_address->first_name = sanitize_text_field($wc_order->get_billing_first_name());
                $cb_address->last_name = sanitize_text_field($wc_order->get_billing_last_name());
                $cb_address->company = sanitize_text_field($wc_order->get_billing_company());
                $cb_address->address_1 = sanitize_text_field($wc_order->get_billing_address_1());
                $cb_address->address_2 = sanitize_text_field($wc_order->get_billing_address_2());
                $cb_address->city = sanitize_text_field($wc_order->get_billing_city());
                $cb_address->state = sanitize_text_field($wc_order->get_billing_state());
                $cb_address->postal_code = sanitize_text_field($wc_order->get_billing_postcode());
                $cb_address->country = sanitize_text_field($wc_order->get_billing_country());
                $cb_order->billing_address = $cb_address;

                // shipping
                $cb_address = new Cartboss_Order_Address_Model();
                $cb_address->first_name = sanitize_text_field($wc_order->get_shipping_first_name());
                $cb_address->last_name = sanitize_text_field($wc_order->get_shipping_last_name());
                $cb_address->company = sanitize_text_field($wc_order->get_shipping_company());
                $cb_address->address_1 = sanitize_text_field($wc_order->get_shipping_address_1());
                $cb_address->address_2 = sanitize_text_field($wc_order->get_shipping_address_2());
                $cb_address->city = sanitize_text_field($wc_order->get_shipping_city());
                $cb_address->state = sanitize_text_field($wc_order->get_shipping_state());
                $cb_address->postal_code = sanitize_text_field($wc_order->get_shipping_postcode());
                $cb_address->country = sanitize_text_field($wc_order->get_shipping_country());
                $cb_order->shipping_address = $cb_address;

                /* @var $wc_order- >get_items() WC_Order_Item[] */
                foreach ($wc_order->get_items() as $wc_cart_item) {
                    try {
                        $cb_cart_item = new Cartboss_Order_Cart_Item_Model();
                        $cb_cart_item->id = sanitize_text_field($wc_cart_item->get_product_id());
                        $cb_cart_item->variation_id = Cartboss_Utils::is_valid_variation_id($wc_cart_item->get_variation_id()) ? sanitize_text_field($wc_cart_item->get_variation_id()) : null;
                        $cb_cart_item->quantity = $wc_cart_item->get_quantity();
                        $cb_cart_item->price = floatval($wc_cart_item->get_total());

                        if ($cb_cart_item->id) {
                            $wc_product = wc_get_product($cb_cart_item->id);
                            if (!empty($wc_product)) {
                                $cb_cart_item->name = sanitize_text_field($wc_product->get_name());
                                $cb_cart_item->image_url = Cartboss_Utils::get_product_image_url($wc_product->get_image_id());
                            }
                        }

                        $cb_order->addCartItem($cb_cart_item);
                    } catch (Exception $e) {
                        Cartboss_Api_manager::instance()->log_error("[Order Purchase Handler] Getting cart item info failed: {$e->getMessage()}");
                    }
                }

                $cb_event->order = $cb_order;

                // 1st mark purchase event reported
                $wc_order->update_meta_data(self::ORDER_META_IS_PURCHASE_REPORTED, true);
                $wc_order->save_meta_data();
                $wc_order->save();

                // 2nd this will only work if hook gets called when browser is still present, and not when called from backend
                Cartboss_Better_Session_Manager::instance()->reset();

                // 3rd send PURCHASE event
                Cartboss_Utils::send_event($cb_event->serialize());

                // basic validation
//                Cartboss_Event_Database_Manager::instance()->insert($session_token, $cb_event->serialize(), 0, Cartboss_Event_Database_Manager::PRIORITY_HIGH);

            } catch (Throwable $e) {
                Cartboss_Api_manager::instance()->log_error("[Order Purchase Handler] Saving PURCHASE event to DB failed: {$e->getMessage()}");
            }
        }
    }

endif; // class_exists check

?>