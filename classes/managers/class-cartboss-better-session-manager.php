<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Better_Session_Manager')) :
    class Cartboss_Better_Session_Manager extends Cartboss_Singleton {
        const COOKIE_NAME = "wp_cartboss_session";
        const SESSION_NAME = "_cb_session";
        const QUERY_VAR = "wpcb_session";
        private $session_id = null;

        protected function __construct() {
            add_action("init", array($this, 'init_from_storage'));
        }

        public static function init() {
            self::instance();
        }

        public function save_session_id(?string $token) {
            Cartboss_Utils::set_session(self::SESSION_NAME, $token);
            Cartboss_Utils::set_cookie(self::COOKIE_NAME, $token, 60 * 60 * 24 * 365, Cartboss_Utils::is_secure());
        }

        public function init_from_storage() {
            // get from url
            $this->session_id = Cartboss_Utils::cb_clean(Cartboss_Utils::get_array_value($_REQUEST, self::QUERY_VAR, null));

            // get from session
            if (!self::is_valid_token($this->session_id)) {
                $this->session_id = Cartboss_Utils::get_session(self::SESSION_NAME);
            }

            // get from cookie
            if (!self::is_valid_token($this->session_id)) {
                $this->session_id = Cartboss_Utils::get_cookie(self::COOKIE_NAME);
            }

            // if valid, save it in case one of storages doesn't contain it
            if (self::is_valid_token($this->session_id)) {
                $this->save_session_id($this->session_id);
            }
        }

        public function get_token(): ?string {
            if (!self::is_valid_token($this->session_id)) {
                $this->session_id = "Cx" . Cartboss_Utils::get_random_string(52);

                $this->save_session_id($this->session_id);
            }
            return $this->session_id;
        }

        public function reset() {
            Cartboss_Utils::remove_session(self::SESSION_NAME);
            Cartboss_Utils::remove_cookie(self::COOKIE_NAME);

            // also remove attribution and discount
            Cartboss_Discount_Manager::instance()->reset();
            Cartboss_Attribution_Manager::instance()->reset();
        }

        public static function is_valid_token(?string $token): bool {
            return Cartboss_Utils::is_non_empty_string($token);
        }
    }

endif; // class_exists check

?>
