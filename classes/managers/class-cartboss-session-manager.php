<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Session_Manager')) :
    class Cartboss_Session_Manager extends Cartboss_Singleton {
        const COOKIE_NAME = "wp_cartboss_session";
        const QUERY_VAR = "wpcb_session";
        private $has_executed = false;
        private $cookie;

        protected function __construct() {
            $this->cookie = Cartboss_Utils::init_cookie(self::COOKIE_NAME, 60 * 60 * 24 * 365);

            add_action("init", array($this, 'parse_url_args'), Cartboss_Constants::CB_PRIORITY_MAX);
        }

        public static function init() {
            self::instance();
        }

        public function parse_url_args() {
            if ($this->has_executed) {
                return;
            }

            if (!Cartboss_Utils::can_run_cartboss()) {
                return;
            }

            // get from url
            $token = Cartboss_Utils::get_array_value($_GET, self::QUERY_VAR, null);

            // get from cookie if not in url
            if (!self::is_valid_token($token)) {
                $token = $this->get_token();
            }

            // create random one in none above present
            if (!self::is_valid_token($token)) {
                $token = Cartboss_Utils::get_random_string(52);
            }

            // store it to cookie and later to order db entity
            if (self::is_valid_token($token)) {
                $this->cookie->setValue($token);
                $this->cookie->saveAndSet();

                $this->has_executed = true;
            }
        }

        public function get_token(): ?string {
            return $this->cookie->get(self::COOKIE_NAME, null);
        }

        public function reset() {
            $this->cookie->deleteAndUnset();

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
