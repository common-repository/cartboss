<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Cron_Sync')) :

    class Cartboss_Cron_Sync extends Cartboss_Cron {
        const MAX_EVENTS = 300;

        var $label = 'CartBoss Sync';
        var $interval = 60 * 1;

        public function do_handle() {
            $cb_db = Cartboss_Event_Database_Manager::instance();

            // api key not valid, don't bother
            if (!Cartboss_Options::get_is_valid_api_key()) {
                return;
            }

            foreach ($cb_db->fetch(self::MAX_EVENTS) as $record) {
                $should_delete = true;

                try {
                    $retry_count = intval($record['retry_count']);
                    $priority = intval($record['priority']);
                    $payload = $record['payload'];

//                    $json_payload = json_decode($payload, true);

                    if ($priority > 0) {
                        $max_retry = 10;
                        $retry_delay = $retry_count * 60;
                    } else {
                        $max_retry = 5;
                        $retry_delay = ($retry_count + 1) * (60 * 1);
                    }

                    if ($retry_count < $max_retry) {
                        try {
                            Cartboss_Api_Manager::instance()->track($payload);

                        } catch (Cartboss_Api_Exception $e) {
                            if ($e->getCode() == 422) { // 422 = validation error, usually phone missing
                                // do not send, just delete
                            } else {
                                $cb_db->reschedule($record['session_token'], $retry_delay);
                                $should_delete = false;
                            }

                        } catch (Exception $e) {
                            Cartboss_Api_manager::instance()->log_error("API call failed #2: {$e}");
                        }
                    }
                } catch (Exception $e) {
                    Cartboss_Api_manager::instance()->log_error("API call failed #3: {$e}");
                }

                if ($should_delete) {
                    $cb_db->delete($record['session_token']);
                }
            }

            // mark last sync
            Cartboss_Options::set_last_sync_at(time());
        }
    }

endif; // class_exists check

?>