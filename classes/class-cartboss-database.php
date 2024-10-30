<?php

abstract class Cartboss_Database extends Cartboss_Singleton {
    const TABLE_NAME = null;

    protected $wpdb;
    protected $charset_collate;

    function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    protected function table_name(): string {
        return $this->wpdb->prefix . static::TABLE_NAME;
    }

    abstract function create_table();

    public function drop_table() {
        $this->do_query("DROP TABLE IF EXISTS {$this->table_name()}");
    }

    protected function do_query($stmt) {
        $res = $this->wpdb->query($stmt);
        if ($res === false && $this->wpdb->last_error) {
            if (str_contains($this->wpdb->last_error, "Unknown column")) {
                $this->drop_table();
                $this->create_table();

            } else if (str_contains($this->wpdb->last_error, "doesn't exist")) {
                $this->create_table();
            }
        }

        return $res;
    }

//    protected function has_column($col) {
//        $row = $this->wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$this->table_name()}' AND column_name = '{$col}'");
//        return !empty($row);
//    }

//    protected function alter_table($sql) {
//        try {
//            $this->wpdb->hide_errors();
//            $this->wpdb->query($this->wpdb->prepare($sql));
//            $this->wpdb->show_errors();
//        } catch (Throwable $e) {
//        }
//    }
}