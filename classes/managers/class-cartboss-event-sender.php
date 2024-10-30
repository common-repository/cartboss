<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Event_Sender')) :

    class Cartboss_Event_Sender extends Cartboss_Singleton {
        protected function __construct() {
            add_action("cb_send_event", array($this, 'send_event'));
        }

        public static function init() {
            self::instance();
        }

        public function send_event($payload) {
            if (!Cartboss_Options::get_is_valid_api_key()){
                error_log("[CARTBOSS] Please provide valid API key to continue using CartBoss");
                return;
            }


            try {
                // actual sending
                Cartboss_Api_Manager::instance()->track($payload);

                // mark that cb server has been just contacted
                Cartboss_Options::set_last_sync_at(time());

            } catch (Cartboss_Api_Exception $e) {
                if ($e->getCode() == 422) {
                    // 422 = validation error, usually phone missing
                    // do not send
                } else {
                    error_log("[CARTBOSS][API] Failed sending event: {$e}");

                    Cartboss_Api_manager::instance()->log_error("API call failed #1: {$e}");
                }

            } catch (Exception $e) {
                Cartboss_Api_manager::instance()->log_error("API call failed #2: {$e}");
            }
        }
    }

endif; // class_exists check

?>