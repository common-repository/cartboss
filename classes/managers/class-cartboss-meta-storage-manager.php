<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Meta_Storage_Manager')) :
    class Cartboss_Meta_Storage_Manager extends Cartboss_Singleton {
        const COOKIE_NAME = "wp_cartboss_meta";
        private $cookie;

        protected function __construct() {
            $this->cookie = Cartboss_Utils::init_cookie(self::COOKIE_NAME, 60 * 60 * 24 * 7);
        }

        public static function init() {
            self::instance();
        }

        public function set_value(string $key,  $value) {
            try {
                $storage = $this->get_cookie_storage();
                $storage[$key] = $value;
                $this->set_cookie_storage($storage);
            } catch (\Throwable $th) {
            }
        }

        public function get_value(string $key, $default = null) {
            return Cartboss_Utils::get_array_value($this->get_cookie_storage(), $key, $default);
        }

        public function remove_value(string $key) {
            try {
                $storage = $this->get_cookie_storage();
                unset($storage[$key]);
                $this->set_cookie_storage($storage);
            } catch (\Throwable $th) {
            }
        }

        private function get_cookie_storage(): array {
            try {
                return json_decode($this->cookie->getValue() || array(), true);
            } catch (\Throwable $th) {
                return array();
            }
        }

        private function set_cookie_storage(array $data) {
            $this->cookie->setValue(json_encode($data));
            $this->cookie->saveAndSet();
        }

        public function reset() {
            $this->cookie->deleteAndUnset();
        }
    }

endif; // class_exists check

?>
