<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Event_Database_Manager')) :
    class Cartboss_Event_Database_Manager extends Cartboss_Database {
        const TABLE_NAME = 'cb_tracking_events';
        const EXPIRES_IN_SECONDS = 60 * 60 * 24 * 3;
        const PRIORITY_NORMAL = 0;
        const PRIORITY_HIGH = 10;

        public function create_table() {
            $sql = "
                CREATE TABLE IF NOT EXISTS `{$this->table_name()}`(
                    `session_token` VARCHAR(128) NOT NULL,
                    `payload` LONGTEXT NOT NULL,
                    `process_at` BIGINT(20) UNSIGNED NOT NULL,
                    `priority` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                    `retry_count` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY(`session_token`),
                    INDEX(`process_at`),
                    INDEX(`priority`)
                ) {$this->charset_collate};
            ";
            $this->wpdb->query($sql);
        }

        /**
         * @param string $session_token
         * @param string $payload
         * @param int $delay
         * @param int $priority
         * @return void
         */
        public function insert(string $session_token, string $payload, int $delay = 0, int $priority = self::PRIORITY_NORMAL) {
            $sql = "
                INSERT INTO `{$this->table_name()}`
                    (`session_token`, `payload`, `process_at`, `priority`) VALUES (%s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    `payload` = %s,
                    `process_at` = %s,
                    `priority` = %s,
                    `retry_count` = 0
            ";

            $process_at = time() + $delay;

            $stmt = $this->wpdb->prepare(
                $sql,
                array(
                    $session_token,
                    $payload,
                    $process_at,
                    $priority,
                    $payload,
                    $process_at,
                    $priority
                )
            );

            $this->do_query($stmt);
        }

        /**
         * @param string $session_token
         */
        public function get(string $session_token) {
            $sql = "
                SELECT *
                FROM {$this->table_name()}
                WHERE session_token = %s
                LIMIT 1
            ";

            $stmt = $this->wpdb->prepare(
                $sql,
                array($session_token)
            );

            return $this->wpdb->get_row($stmt);
        }

        /**
         * @return array
         */
        public function fetch(int $limit = 100): array {
            // fetch ones with higher priority first
            $sql = "
                SELECT *
                FROM {$this->table_name()}
                WHERE process_at < %s
                ORDER BY priority DESC, process_at ASC 
                LIMIT %d;
            ";

            $stmt = $this->wpdb->prepare(
                $sql,
                array(time(), $limit)
            );

            return $this->wpdb->get_results($stmt, ARRAY_A);
        }

        /**
         * @param string $session_token
         */
        public function delete(string $session_token) {
            $sql = "
                DELETE 
                FROM {$this->table_name()}
                WHERE session_token = %s
                LIMIT 1
            ";

            $stmt = $this->wpdb->prepare(
                $sql,
                array($session_token)
            );

            $this->do_query($stmt);
        }

        public function purge() {
            $sql = "
                DELETE 
                FROM {$this->table_name()}
                WHERE process_at < %s
            ";

            $stmt = $this->wpdb->prepare(
                $sql,
                array(time() - self::EXPIRES_IN_SECONDS)
            );

            $this->do_query($stmt);
        }

        /**
         * @param string $session_token
         * @param int $delay
         */
        public function reschedule(string $session_token, int $delay = 60) {
            $sql = "
                UPDATE {$this->table_name()}
                SET retry_count = retry_count + 1, process_at = %s
                WHERE session_token = %s
                LIMIT 1;
            ";

            $stmt = $this->wpdb->prepare(
                $sql,
                array(time() + $delay, $session_token)
            );

            $this->do_query($stmt);
        }
    }

endif; // class_exists check

?>