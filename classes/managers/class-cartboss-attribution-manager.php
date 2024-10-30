<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Attribution_Manager')) :

    class Cartboss_Attribution_Manager extends Cartboss_Singleton {
        const COOKIE_NAME = "wp_cartboss_attribution";
        const SESSION_NAME = "_cb_attribution";
        const QUERY_VAR = "cb__att";

        private $attribution_id = null;

        protected function __construct() {
            add_action("init", array($this, 'init_from_storage'));
        }

        public static function init() {
            self::instance();
        }

        public function save_attribution(?string $token) {
            Cartboss_Utils::set_session(self::SESSION_NAME, $token);
            Cartboss_Utils::set_cookie(self::COOKIE_NAME, $token, 60 * 60 * 24 * 365, Cartboss_Utils::is_secure());
        }

        public function init_from_storage() {
            if (!self::is_valid_token($this->attribution_id)) {

                // get from url
                $this->attribution_id = Cartboss_Utils::cb_clean(Cartboss_Utils::get_array_value($_REQUEST, self::QUERY_VAR, ''));

                // get from session
                if (!self::is_valid_token($this->attribution_id)) {
                    $this->attribution_id = Cartboss_Utils::get_session(self::SESSION_NAME);
                }

                // get from cookie
                if (!self::is_valid_token($this->attribution_id)) {
                    $this->attribution_id = Cartboss_Utils::get_cookie(self::COOKIE_NAME);
                }

                if (self::is_valid_token($this->attribution_id)) {
                    $this->save_attribution($this->attribution_id);
                }
            }
        }

        public function get_token(): ?string {
            return $this->attribution_id;
        }

        public function reset() {
            Cartboss_Utils::remove_session(self::SESSION_NAME);
            Cartboss_Utils::remove_cookie(self::COOKIE_NAME);
        }

        public static function is_valid_token(?string $token): bool {
            return Cartboss_Utils::is_non_empty_string($token, 5);
        }
    }

endif; // class_exists check

?>