<?php


use League\Uri\Uri;
use League\Uri\UriModifier;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler_Session_Reset')) :

    class Cartboss_Hook_Handler_Session_Reset extends Cartboss_Hook_Handler {
        const WP_URL_PATH_RESET_SESSION = 'cartboss-reset-session';

        function initialize() {
            $theme_routes = new Cartboss_Custom_Routes();
            $theme_routes->addRoute(
                '^' . self::WP_URL_PATH_RESET_SESSION . '?',
                array($this, 'handle')
            );
        }

        public static function get_session_reset_url() {
            return UriModifier::appendSegment(Uri::createFromString(Cartboss_Utils::get_home_url()), self::WP_URL_PATH_RESET_SESSION);
        }

        function handle() {
            try {
                WC()->cart->empty_cart();
            } catch (Exception $ex) {
            }

            try {
                WC()->session->destroy_session();
            } catch (Exception $ex) {
            }

            Cartboss_Better_Session_Manager::instance()->reset();

            wp_redirect(Cartboss_Utils::get_home_url());

            exit();
        }
    }

endif; // class_exists check

?>