<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Ajax')) :

    abstract class Cartboss_Ajax extends Cartboss_Singleton {
        var $action = '';
        var $public = false;

        function __construct() {
            $this->initialize();
            $this->add_actions();
        }

        function initialize() {
            /* do nothing */
        }

        function add_actions() {
            add_action("wc_ajax_{$this->action}", array($this, 'request'), Cartboss_Constants::CB_PRIORITY_MAX);

//            // add action for logged-in users
//			add_action( "wp_ajax_{$this->action}", array( $this, 'request' ) );
//
//			// add action for non logged-in users
//			if ( $this->public ) {
//				add_action( "wp_ajax_nopriv_{$this->action}", array( $this, 'request' ) );
//			}
        }

        function request() {
            try {
                $this->handle_request(wp_unslash($_REQUEST));
            } catch (Throwable $e) {
                Cartboss_Api_manager::instance()->log_error("AJAX handler '{$this->action}' failed: {$e->getMessage()}");
            }
        }

        function handle_request($request) {
            return true;
        }

        function get_action() {
            return $this->action;
        }
    }

endif; // class_exists check

?>