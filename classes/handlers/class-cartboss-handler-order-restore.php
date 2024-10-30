<?php


use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler_Order_Restore')) :

    class Cartboss_Hook_Handler_Order_Restore extends Cartboss_Hook_Handler {
        const WP_URL_PATH_RESTORE_CART = 'cartboss-restore-session';

        function initialize() {
            $theme_routes = new Cartboss_Custom_Routes();
            $theme_routes->addRoute(
                '^' . self::WP_URL_PATH_RESTORE_CART . '?',
                array($this, 'handle')
            );
        }

        public static function get_order_restore_url() {
            return UriModifier::appendSegment(Uri::createFromString(Cartboss_Utils::get_home_url()), self::WP_URL_PATH_RESTORE_CART);
        }

        function handle() {
            // get it from URL, because this script runs before Cartboss_Better_Session_Manager
            $session_token = Cartboss_Utils::get_array_value($_GET, Cartboss_Better_Session_Manager::QUERY_VAR, null);

            // check if session is valid format
            if (!Cartboss_Better_Session_Manager::is_valid_token($session_token)) {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Invalid session token received '{$session_token}'");

                $this->redirect_home('cb-err-inv-sess');
                exit();
            }

            // check if provided session token is already consumed, if it is, we should not restore this session
            try {
                if (Cartboss_Token_Database_Manager::instance()->exists($session_token)) {
                    wp_redirect(Cartboss_Hook_Handler_Session_Reset::get_session_reset_url(), 303);
                    exit();
                }
            } catch (Throwable $e) {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler CONSUMED check] failed: {$e->getMessage()}");
            }

            // fetch order info from local db
            $order_data = null;
            try {
                $response_record = Cartboss_Order_Database_Manager::instance()->get($session_token);
                $order_data = json_decode($response_record->payload);
            } catch (Exception $e) {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Fetching order '{$session_token}' from DB failed with error {$e->getMessage()}");
            }

            // fetch order info from remote
            if (empty($order_data)) {
                try {
                    $order_data = Cartboss_Api_Manager::instance()->get_order($session_token);
                } catch (Exception $e) {
                    Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Fetching order '{$session_token}' from REMOTE failed with error {$e->getMessage()}");
                }
            }

            if (empty($order_data)) {
                $this->redirect_home('cb-err-no-order');
                exit();
            }

            try {
                $serializer = new Serializer(
                    [
                        new ObjectNormalizer(null, null, null, new class implements PropertyTypeExtractorInterface {
                            public function getTypes($class, $property, array $context = array()) {
                                if (!is_a($class, Cartboss_Order_Model::class, true)) {
                                    return null;
                                }

                                if (in_array($property, array('billing_address', 'shipping_address'))) {
                                    return [new Type(Type::BUILTIN_TYPE_OBJECT, true, Cartboss_Order_Address_Model::class)];
                                }

                                if ('items' == $property) {
                                    return [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, Cartboss_Order_Cart_Item_Model::class))];
                                }

                                return null;
                            }
                        }),
                        new ArrayDenormalizer()
                    ],
                    [new JsonEncoder()]
                );

                $cb_order = $serializer->deserialize(json_encode($order_data), Cartboss_OrderExtended_Model::class, 'json');

            } catch (Throwable $e) {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Deserialize failed: {$e->getMessage()}");
                $this->redirect_home('cb-err-des');
                exit();
            }

            if (empty($cb_order)) {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Order not fetched and/or deserialized");
                $this->redirect_home('cb-err-no-order-des');
                exit();
            }

            // remove previous session bcoz of possible order duplicates
            try {
                $previous_session_id = Cartboss_Utils::get_array_value($cb_order->metadata, Cartboss_Constants::CB_METADATA_LOCAL_SESSION_ID);
                if ($previous_session_id) {
                    $sh = new WC_Session_Handler();
                    if ($sh) {
                        $sh->delete_session($previous_session_id);
                    }
                }
            } catch (Exception $e) {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Removing old session failed: {$e->getMessage()}");
            }

            // remove possible current user session, before creating a new one from scratch
            try {
                WC()->session->destroy_session();
            } catch (Exception $ex) {
            }

            // restore cart
            if (!empty(WC()->cart)) {
                WC()->cart->empty_cart();

                if (!empty($cb_order->items)) {
                    foreach ($cb_order->items as $cb_cart_item) {
                        try {
                            $wc_product = wc_get_product(intval($cb_cart_item->id)); //Checking if the product exists
                            if ($wc_product) {
                                if ($cb_cart_item->variation_id) {
                                    $single_variation = new WC_Product_Variation(intval($cb_cart_item->variation_id));
                                    $variation_attributes = $single_variation->get_variation_attributes();
                                } else {
                                    $variation_attributes = '';
                                }

                                try {
                                    WC()->cart->add_to_cart($wc_product->get_id(), intval($cb_cart_item->quantity), intval($cb_cart_item->variation_id), $variation_attributes);

                                } catch (Exception $e) {
                                    Cartboss_Api_manager::instance()->log_error($e->getMessage());
                                }
                            }
                        } catch (Exception $e) {
                            Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Restoring product id { $cb_cart_item->id} failed: {$e->getMessage()}");
                        }
                    }

                    try {
                        WC()->cart->calculate_totals();
                    } catch (Exception $e) {
                        Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Saving WC cart failed step 1: {$e->getMessage()}");
                    }

                    try {
                        WC()->cart->set_session();
                    } catch (Exception $e) {
                        Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Saving WC cart failed step 2: {$e->getMessage()}");
                    }

                    try {
                        WC()->cart->maybe_set_cart_cookies();
                    } catch (Exception $e) {
                        Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Saving WC cart failed step 3: {$e->getMessage()}");
                    }
                }
            } else {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] WC()->cart not initialized");
            }

            if (WC()->customer) {
                try {
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'email', $cb_order->billing_address->email);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'phone', $cb_order->billing_address->phone);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'first_name', $cb_order->billing_address->first_name);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'last_name', $cb_order->billing_address->last_name);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'address_1', $cb_order->billing_address->address_1);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'address_2', $cb_order->billing_address->address_2);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'city', $cb_order->billing_address->city);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'state', $cb_order->billing_address->state);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'postcode', $cb_order->billing_address->postal_code);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'company', $cb_order->billing_address->company);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'billing', 'country', $cb_order->billing_address->country);

                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'first_name', $cb_order->shipping_address->first_name);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'last_name', $cb_order->shipping_address->last_name);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'address_1', $cb_order->shipping_address->address_1);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'address_2', $cb_order->shipping_address->address_2);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'city', $cb_order->shipping_address->city);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'state', $cb_order->shipping_address->state);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'postcode', $cb_order->shipping_address->postal_code);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'company', $cb_order->shipping_address->company);
                    Cartboss_Utils::set_wc_customer_field(WC()->customer, 'shipping', 'country', $cb_order->shipping_address->country);

                    WC()->customer->save();

                } catch (Exception $ee) {
                    Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] Saving WC customer failed: {$ee->getMessage()}");
                }
            } else {
                Cartboss_Api_manager::instance()->log_error("[Order Restore Handler] WC()->customer not initialized");
            }

            // extract query params and remove CB ones
            $current_uri = Uri::createFromServer($_SERVER);
            $current_uri = UriModifier::removeParams($current_uri, Cartboss_Better_Session_Manager::QUERY_VAR, Cartboss_Attribution_Manager::QUERY_VAR, Cartboss_Discount_Manager::QUERY_VAR, "cb__sig");
            $current_query = Query::createFromUri($current_uri);

            // determine checkout url; 1st try provided via ATC event, if not cool, try default
            $checkout_url = Cartboss_Utils::get_array_value($cb_order->metadata, Cartboss_Constants::CB_METADATA_CHECKOUT_REDIRECT_URL);
            if (filter_var($checkout_url, FILTER_VALIDATE_URL) === false) {
                $checkout_url = wc_get_checkout_url();
            }

            try {
                // add query params; eg. utm_* to the checkout url
                $redirect_uri = Uri::createFromString($checkout_url);
                $redirect_uri = UriModifier::appendQuery($redirect_uri, $current_query);

            } catch (Exception $e) {
                $redirect_uri = $checkout_url;
            }

            wp_redirect($redirect_uri, 303);
            exit();
        }

        function redirect_home($reason = null) {
            $redirect_uri = Uri::createFromString(Cartboss_Utils::get_home_url());
            if (isset($reason))
                $redirect_uri = $redirect_uri->withFragment($reason);

            wp_redirect($redirect_uri, 303);
            exit();
        }
    }

endif; // class_exists check

?>