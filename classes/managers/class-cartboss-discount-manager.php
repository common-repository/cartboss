<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Discount_Manager')) :
    class Cartboss_Discount_Manager extends Cartboss_Singleton {
        const COOKIE_NAME = "wp_cartboss_discount";
        const SESSION_NAME = "_cb_coupon";
        const QUERY_VAR = "cb__discount";

        const COUPON_EXPIRATION_DAYS = 14;
        const FIXED_AMOUNT = 'FIXED_AMOUNT';
        const PERCENTAGE = 'PERCENTAGE';
        const FREE_SHIPPING = 'FREE_SHIPPING';
        const CUSTOM = 'CUSTOM';

        const STRUCT_CODE = 'code';
        const STRUCT_TYPE = 'type';
        const STRUCT_VALUE = 'value';
        const STRUCT_SEQUENTIAL = 'sequential';
        const STRUCT_SIGNATURE = 'signature';
        private $secret;
        private $coupon_data = null;

        protected function __construct() {
            add_action("init", array($this, 'init_from_storage'));
            add_action("woocommerce_cart_calculate_fees", array($this, 'handle'), Cartboss_Constants::CB_PRIORITY_MIN, 1);
        }

        public static function init(?string $secret) {
            self::instance()->secret = $secret;
        }

        public function save_coupon(?string $token) {
            Cartboss_Utils::set_session(self::SESSION_NAME, $token);
            Cartboss_Utils::set_cookie(self::COOKIE_NAME, $token, 60 * 60 * 24 * self::COUPON_EXPIRATION_DAYS, Cartboss_Utils::is_secure());
        }

        public function init_from_storage() {
            if (!self::is_valid_coupon_data($this->coupon_data)) {

                // get from url
                $this->coupon_data = Cartboss_Utils::cb_clean(Cartboss_Utils::get_array_value($_REQUEST, self::QUERY_VAR, ''));

                // get from session
                if (!self::is_valid_coupon_data($this->coupon_data)) {
                    $this->coupon_data = Cartboss_Utils::get_session(self::SESSION_NAME);
                }

                // get from cookie
                if (!self::is_valid_coupon_data($this->coupon_data)) {
                    $this->coupon_data = Cartboss_Utils::get_cookie(self::COOKIE_NAME);
                }

                if (self::is_valid_coupon_data($this->coupon_data)) {
                    $this->save_coupon($this->coupon_data);
                }
            }
        }

        public function handle($cart) {
            if (empty($cart) && !empty(WC()->cart)) {
                $cart = WC()->cart;
            }

            if (empty($cart)) {
                return;
            }

            if (!self::is_valid_coupon_data($this->coupon_data)) {
                return;
            }

            // removing discount triggers calculate_amounts which triggers more hooks
            if (did_action("cb_discount_manager_executed") > 0) {
                return;
            }

            // enable coupons
            Cartboss_Utils::enable_coupons();

            try {
                $coupon_data = $this->decode_coupon_string($this->coupon_data);
                if (!$this->is_valid_structure($coupon_data)) {
                    return;
                }

                // extract coupon info
                $coupon_code = wc_sanitize_coupon_code(Cartboss_Utils::get_array_value($coupon_data, self::STRUCT_CODE, ''));
                $coupon_type = mb_strtoupper(trim(Cartboss_Utils::get_array_value($coupon_data, self::STRUCT_TYPE, '')));
                $coupon_value = floatval(Cartboss_Utils::get_array_value($coupon_data, self::STRUCT_VALUE, '0'));
                $coupon_sequential = boolval(Cartboss_Utils::get_array_value($coupon_data, self::STRUCT_SEQUENTIAL, false));

                if ($cart->has_discount($coupon_code)) {
                    return;
                }

                // if coupon is not yet attached to cart
                // if discount is exclusive, remove all existing cart coupons first
                try {
                    if (!$coupon_sequential && !empty($cart->get_applied_coupons())) {
                        $cart->remove_coupons();
                    }
                } catch (Exception $e) {
                    // pass
                }

                $coupon_exists_in_woo = $this->coupon_exists($cart, $coupon_code);

                if ($coupon_type == self::CUSTOM) { // if custom
                    if ($coupon_exists_in_woo) {
                        // apply coupon to order
                        $cart->apply_coupon($coupon_code);
                    } else {
                        error_log("[CARTBOSS] Custom coupon '{$coupon_code}' requested, but doesn't exist!");
                    }

                } else if (in_array($coupon_type, array(self::FIXED_AMOUNT, self::PERCENTAGE, self::FREE_SHIPPING))) { // if created by cartboss
                    // if coupon doesn't exist, create it
                    if (!$coupon_exists_in_woo) {
                        $expiration_time = time() + 60 * 60 * 24 * self::COUPON_EXPIRATION_DAYS;

                        $wc_coupon = new WC_Coupon($coupon_code);
                        $wc_coupon->set_description("Generated by CartBoss");
                        $wc_coupon->set_date_expires($expiration_time);
                        $wc_coupon->set_usage_limit(1);

                        if ($coupon_type == self::FIXED_AMOUNT) {
                            $wc_coupon->set_discount_type("fixed_cart");
                            $wc_coupon->set_amount($coupon_value);
                            $wc_coupon->set_free_shipping(false);

                        } else if ($coupon_type == self::PERCENTAGE) {
                            $wc_coupon->set_discount_type("percent");
                            $wc_coupon->set_amount($coupon_value);
                            $wc_coupon->set_free_shipping(false);

                        } else if ($coupon_type == self::FREE_SHIPPING) {
                            $wc_coupon->set_discount_type("fixed_cart");
                            $wc_coupon->set_amount(0);
                            $wc_coupon->set_free_shipping(true);
                        }

                        $wc_coupon->save();

                        // set extra meta
                        $wc_coupon->update_meta_data("cb_delete_at", $expiration_time);
                        $wc_coupon->update_meta_data("cb_source", "cartboss");
                        $wc_coupon->save_meta_data();
                    }

                    // apply coupon to order
                    $cart->apply_coupon($coupon_code);
                }

            } catch (Exception $e) {
                Cartboss_Api_manager::instance()->log_error("Coupon creation failed: {$e->getMessage()}");

            } finally {
                do_action("cb_discount_manager_executed");
                $this->reset();
            }
        }

        public function reset() {
            Cartboss_Utils::remove_session(self::SESSION_NAME);
            Cartboss_Utils::remove_cookie(self::COOKIE_NAME);
        }

        function coupon_exists($cart, $coupon_code): bool {
            try {
                $coupon = new WC_Coupon($coupon_code);
                $discounts = new WC_Discounts($cart);
                $valid_response = $discounts->is_coupon_valid($coupon);
                if (is_wp_error($valid_response)) {
                    return false;
                } else {
                    return true;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        /**
         * @param string|null $token
         *
         * @return bool
         */
        public static function is_valid_coupon_data(?string $token): bool {
            return Cartboss_Utils::is_non_empty_string($token);
        }

        private function is_valid_structure($data): bool {
            if (!is_array($data))
                return false;

            foreach (array(self::STRUCT_CODE, self::STRUCT_TYPE) as $param) {
                if (empty(Cartboss_Utils::get_array_value($data, $param, null))) {
                    return false;
                }
            }

            return true;
        }

        private function decode_coupon_string(?string $input): ?array {
            // 1st try simple b64decode (api3 signs urls instead of encrypting coupon data)
            try {
                $data = json_decode(base64_decode($input), true);
                if (Cartboss_Utils::get_array_value($data, self::STRUCT_SIGNATURE, Cartboss_Utils::get_random_string(16)) == $this->get_signature_from_coupon_structure($data)) {
                    return $data;
                }
            } catch (Exception $e) {
            }

            // 2nd try decrypt
            try {
                $data = Cartboss_Utils::aes_decode($this->secret, $input);
                if (Cartboss_Utils::get_array_value($data, self::STRUCT_CODE, null) != null) {
                    return $data;
                }
            } catch (Exception $e) {
            }

            return null;
        }

        private function get_signature_from_coupon_structure(?array $data) {
            return Cartboss_Utils::get_signature($this->secret, Cartboss_Utils::get_array_value($data, self::STRUCT_CODE, '') . "|" . Cartboss_Utils::get_array_value($data, self::STRUCT_TYPE, '') . "|" . floatval(Cartboss_Utils::get_array_value($data, self::STRUCT_VALUE, 0)));
        }
    }


endif; // class_exists check

?>