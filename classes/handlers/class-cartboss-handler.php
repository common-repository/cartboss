<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Hook_Handler')) :

    abstract class Cartboss_Hook_Handler extends Cartboss_Singleton {
        var $action = '';

        function __construct() {
            $this->initialize();
            $this->add_actions();
        }

        function initialize() {
            /* do nothing */
        }

        function add_actions() {
        }
    }

endif; // class_exists check

?>