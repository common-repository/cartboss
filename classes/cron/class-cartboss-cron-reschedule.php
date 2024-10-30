<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Cron_Reschedule')) :

    class Cartboss_Cron_Reschedule extends Cartboss_Cron
    {
        var $label = 'CartBoss Cron Rescheduler';
        var $interval = 60 * 60;

        public function do_handle()
        {
//            Cartboss_Cron_Ping::instance()->schedule();
//            Cartboss_Cron_Sync::instance()->schedule();
//            Cartboss_Cron_Clean::instance()->schedule();
        }
    }

endif; // class_exists check

?>