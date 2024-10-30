<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler_Customize_Checkout_Fields')) :

    class Cartboss_Hook_Handler_Customize_Checkout_Fields extends Cartboss_Hook_Handler {
        function add_actions() {
            add_filter('woocommerce_checkout_fields', array($this, 'handle'), Cartboss_Constants::CB_PRIORITY_MIN, 1);
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

            // FORCE PHONE AT TOP?
            $phone_priority = false;
            if (Cartboss_Options::get_is_phone_field_on_top()) {
                $phone_priority = 1;
            } else {
                foreach ($fields['billing'] as $bk => $bv) {
                    if (str_contains($bk, 'phone') && key_exists('priority', $bv)) {
                        $phone_priority = intval($bv['priority']);
                        break;
                    }
                }
            }

            if ($phone_priority === false) { // no phone available? skip!
                error_log("[CARTBOSS] Hey admin, phone field is not enabled? CartBoss doesn't work without it!");

            } else {
                // re+set phone priority like a boss :)
                $fields['billing']['billing_phone']['priority'] = $phone_priority;

                // marketing consent field
                $_class = array('form-row-wide', 'cartboss-checkout-field', 'cartboss-checkout-field--no-top-margin');
                if (!Cartboss_Options::get_is_marketing_checkbox_visible()) {
                    array_push($_class, 'cartboss-checkout-field--hidden');
                }

                $fields['billing'][Cartboss_Constants::CB_FIELD_ACCEPTS_MARKETING] = array(
                    'priority' => intval($fields['billing']['billing_phone']['priority']) + 1,
                    'type' => 'checkbox',
                    'label' => apply_filters('wpml_translate_single_string', Cartboss_Options::get_marketing_checkbox_label(), Cartboss_Constants::TEXT_DOMAIN, Cartboss_Constants::TEXT_KEY_CONSENT_LABEL),
                    'required' => false,
                    'default' => false,
                    'class' => $_class,
                    'clear' => true,
                    'input_class' => array('woocommerce-form__input', 'woocommerce-form__input-checkbox', 'cartboss-checkbox')
                );
            }

            return $fields;
        }
    }

endif; // class_exists check

?>