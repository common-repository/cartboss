<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Cron')) :

    abstract class Cartboss_Cron extends Cartboss_Singleton {
        const LOCK_TIMEOUT = 60 * 10;

        var $label = '';
        var $interval = 60;

        protected $cron_hook;
        protected $cron_lock;
        private $schedule_id;
        private $slug;

        function __construct() {
            $this->slug = str_replace('-', '_', sanitize_title($this->label));
            $this->cron_hook = "cb_hook_{$this->slug}";
            $this->cron_lock = "cb_lock_{$this->slug}";
            $this->schedule_id = "cb_interval_$this->slug";

            if (!has_action($this->cron_hook)) {
                add_action($this->cron_hook, array($this, 'handle'));
            }

            add_filter('http_request_timeout', array($this, 'set_timout'));
            add_action("cron_schedules", array($this, 'set_cron_schedules'));
            add_action("init", array($this, 'schedule_this_cron'));
        }

        function set_timout($time) {
            return 30;
        }

        public function set_cron_schedules(array $schedules = array()) {
            if (!isset($schedules[$this->schedule_id])) {
                $schedules[$this->schedule_id] = array(
                    'interval' => $this->interval,
                    'display' => $this->label,
                );
            }
            return $schedules;
        }

        public function schedule_this_cron() {
            if (!wp_next_scheduled($this->cron_hook)) {
                wp_schedule_event(time(), $this->schedule_id, $this->cron_hook);
            }
        }

        public function deactivate() {
            $timestamp = wp_next_scheduled($this->cron_hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $this->cron_hook);
            }
            wp_clear_scheduled_hook($this->cron_hook);
        }

        // https://wordpress.stackexchange.com/questions/95677/is-it-safe-to-run-wp-cron-php-twice-if-the-first-instance-takes-too-long
        // https://github.com/techcrunch/wp-async-task
        function handle() {
            $last_run_time = get_transient($this->cron_lock);
            if (!$last_run_time || $last_run_time + $this->interval <= time()) {
                set_transient($this->cron_lock, time(), $this->interval - 5);

                // extend processing limit
                try {
                    if (!ini_get('safe_mode')) {
                        set_time_limit(self::LOCK_TIMEOUT + 60);
                    }
                } catch (Exception $e) {
                    error_log("[CARTBOSS][CRON] Failed to extend execution time");
                }

                try {
                    $this->do_handle();

                    Cartboss_Options::set_cron_timestamp();

                } catch (Exception $e) {
                    Cartboss_Api_manager::instance()->log_error("[CRON][{$this->label}] failed: {$e->getMessage()}");

                } finally {
                    delete_transient($this->cron_lock);

                    // somehow happened, multiple cron duplicates were created and fired at the same time, so we need to remove them all
                    try {
                        $counter = 0;
                        foreach (_get_cron_array() as $timestamp => $events) {
                            foreach ($events as $event_hook => $event_args) {
                                if ($event_hook == $this->cron_hook) {
                                    $counter++;
                                }
                            }
                        }
                        if ($counter > 1) {
                            wp_clear_scheduled_hook($this->cron_hook);
                        }
                    } catch (Exception $e) {
                        // pass
                    }
                }

                // restore processing limit
                try {
                    if (!ini_get('safe_mode')) {
                        if ($l = ini_get('max_execution_time')) {
                            set_time_limit($l);
                        }
                    }
                } catch (Exception $e) {
                    error_log("[CARTBOSS][CRON] Failed to restore execution time");
                }
            }
        }

        abstract function do_handle();
    }

endif; // class_exists check

?>