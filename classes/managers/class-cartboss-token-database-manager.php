<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Token_Database_Manager')) :
    class Cartboss_Token_Database_Manager extends Cartboss_Database {
        const TABLE_NAME = 'cb_consumed_session_tokens';
        const EXPIRES_IN_SECONDS = 60 * 60 * 24 * 90;

        public function create_table() {
            $sql = "
                CREATE TABLE IF NOT EXISTS `{$this->table_name()}` (
                    `session_token` VARCHAR(128) NOT NULL,
                    `order_id` VARCHAR(128) NOT NULL,
                    `expires_at` BIGINT(20) UNSIGNED NOT NULL,
                    PRIMARY KEY (`session_token`),
                    INDEX(`expires_at`)
                ) {$this->charset_collate};
            ";

            $this->wpdb->query($sql);
        }

        /**
         * @param string $session_token
         */
        public function insert(string $session_token, string $order_id) {
            $sql = "INSERT IGNORE INTO {$this->table_name()} (session_token, order_id, expires_at) VALUES (%s, %s, %s)";

            $stmt = $this->wpdb->prepare(
                $sql,
                array(
                    $session_token,
                    $order_id,
                    time() + self::EXPIRES_IN_SECONDS
                )
            );

            $this->do_query($stmt);
        }

        /**
         * @return bool
         */
        public function exists(?string $session_token): bool {
            if (empty($session_token))
                return false;

            $sql = "SELECT COUNT(*) FROM {$this->table_name()} WHERE session_token = %s LIMIT 1";

            $stmt = $this->wpdb->prepare(
                $sql,
                array($session_token)
            );

            return intval($this->wpdb->get_var($stmt)) >= 1;
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

        public function purge() {
            $sql = "
                DELETE 
                FROM {$this->table_name()}
                WHERE expires_at < %s
            ";

            $stmt = $this->wpdb->prepare(
                $sql,
                array(time())
            );

            $this->do_query($stmt);
        }
    }

endif; // class_exists check

?>