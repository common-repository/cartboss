<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Api_Manager')) :

    class Cartboss_Api_Manager extends Cartboss_Singleton {
        private $api_client;

        public static function init(string $api_host, ?string $api_key, string $plugin_version): void {
            self::instance()->api_client = new Cartboss_Api_Client($api_host, $api_key, $plugin_version,15);
        }

        public function ping() {
            return self::instance()->api_client->performHttpCall(Cartboss_Api_Client::HTTP_POST, 'ping');
        }

        /**
         * @throws Cartboss_Api_Exception
         */
        public function track(string $payload) {
            self::instance()->api_client->performHttpCall(Cartboss_Api_Client::HTTP_POST, 'track', $payload);
        }

        public function get_order(string $order_id) {
            return self::instance()->api_client->performHttpCall(Cartboss_Api_Client::HTTP_GET, 'orders/' . $order_id);
        }

        public function log_error(string $message) {
            $this->log('ERROR', $message);
        }

        public function log_info(string $message) {
            $this->log('INFO', $message);
        }

        private function log(string $level, string $message) {
            error_log(substr("[CARTBOSS][{$level}] {$message}", 0, 1020));

            try {
                global $wp_version;

                if (!function_exists('get_plugins'))
                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                $plugin_folder = get_plugins('/woocommerce');
                $wc_version = $plugin_folder['woocommerce.php']['Version'];

                $payload = array(
                    'level' => $level,
                    'platform' => 'WORDPRESS',
                    'version' => CARTBOSS_VERSION,
                    'php' => phpversion(),
                    'wp' => $wp_version,
                    'wc' => $wc_version,
                    'message' => $message,
                );

                self::instance()->api_client->performHttpCall(Cartboss_Api_Client::HTTP_POST, 'log', self::instance()->api_client->parse_request_body($payload));
            } catch (Exception $e) {
                // pass
            }
        }
    }

endif; // class_exists check

?>