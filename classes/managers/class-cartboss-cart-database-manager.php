<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Cart_Database_Manager')) :
    class Cartboss_Cart_Database_Manager extends Cartboss_Database {
        const TABLE_NAME = 'cb_carts';
        const EXPIRES_IN_SECONDS = 60 * 60 * 24 * 31;

        public function create_table() {
            $sql = "
                CREATE TABLE IF NOT EXISTS `{$this->table_name()}` (
                    `session_token` VARCHAR(128) NOT NULL,
                    `payload` LONGTEXT NOT NULL,
                    `expires_at` BIGINT(20) UNSIGNED NOT NULL,
                    PRIMARY KEY (`session_token`),
                    INDEX(`expires_at`)
                ) {$this->charset_collate};
            ";

            $this->wpdb->query($sql);
        }

        public function insert(string $session_token, string $payload) {
            $sql = "
                INSERT INTO `{$this->table_name()}`
                    (`session_token`, `payload`, `expires_at`) VALUES (%s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    `payload` = %s,
                    `expires_at` = %s
            ";

            $expires_at = time() + self::EXPIRES_IN_SECONDS;

            $stmt = $this->wpdb->prepare(
                $sql,
                array(
                    $session_token,
                    $payload,
                    $expires_at,
                    $payload,
                    $expires_at,
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
                WHERE $session_token = %s
                LIMIT 1
            ";

            $stmt = $this->wpdb->prepare(
                $sql,
                array($session_token)
            );

            return $this->wpdb->get_row($stmt);
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