<?php


class Cartboss_Options {
//    const DEFAULT_ROLES = array('administrator', 'shop_manager', 'editor', 'author');
    const DEFAULT_ROLES = array();

    const API_KEY = 'cb_api_key';
    const PHONE_ON_TOP = 'cb_phone_on_top';
    const MARKETING_CHECKBOX_ENABLED = 'cb_marketing_checkbox_enabled';
    const MARKETING_CHECKBOX_LABEL = 'cb_marketing_checkbox_label';
    const IS_WEBSITE_ACTIVE = 'cb_is_website_active';
    const BALANCE = 'cb_balance';
    const HAS_BALANCE = 'cb_has_balance';
    const LAST_PING_AT = 'cb_last_ping_at';
    const LAST_SYNC_AT = 'cb_last_sync_at';
    const IS_VALID_API_KEY = 'cb_is_valid_api_key';
    const IGNORED_ROLES = 'cb_ignored_roles';
    const LATEST_VERSION = 'cb_latest_version';
    const CRON_TIMESTAMP = 'cb_cron_timestamp';

    public static function get_api_key(): ?string {
        return get_option(self::API_KEY, null);
    }

    public static function set_api_key(string $value) {
        update_option(self::API_KEY, trim($value));

        if (mb_strlen($value) > 0) {
            self::set_is_valid_api_key(true);
        } else {
            self::set_is_valid_api_key(false);
        }
    }

    public static function is_enabled(): bool {
//        return self::get_is_valid_api_key() && self::get_has_balance() && self::get_is_website_active();
        return self::get_is_valid_api_key();
    }

    public static function get_is_valid_api_key(): bool {
        return get_option(self::IS_VALID_API_KEY, false);
    }

    public static function set_is_valid_api_key(bool $value) {
        update_option(self::IS_VALID_API_KEY, $value);
        if (!$value) {
            self::set_is_website_active(false);
            self::set_balance(null);
            self::set_last_ping_at(null);
            self::set_last_sync_at(null);
        }
    }

    public static function get_has_balance(): bool {
        return get_option(self::HAS_BALANCE, false);
    }

    public static function set_has_balance(bool $value) {
        update_option(self::HAS_BALANCE, $value);
    }

    public static function get_is_website_active(): bool {
        return get_option(self::IS_WEBSITE_ACTIVE, false);
    }

    public static function set_is_website_active(bool $value) {
        update_option(self::IS_WEBSITE_ACTIVE, $value);
    }

    public static function set_is_phone_field_on_top(bool $value) {
        update_option(self::PHONE_ON_TOP, $value);
    }

    public static function get_is_phone_field_on_top(): bool {
        return get_option(self::PHONE_ON_TOP, false);
    }

    public static function set_is_marketing_checkbox_visible(bool $value) {
        update_option(self::MARKETING_CHECKBOX_ENABLED, $value);
    }

    public static function get_is_marketing_checkbox_visible(): bool {
        return get_option(self::MARKETING_CHECKBOX_ENABLED, false);
    }

    public static function get_marketing_checkbox_label() {
        return get_option(self::MARKETING_CHECKBOX_LABEL, self::get_default_marketing_checkbox_label());
    }

    public static function get_default_marketing_checkbox_label() {
        return 'Sign up for exclusive offers and news via text messages';
    }

    public static function set_marketing_checkbox_label($value) {
        update_option(self::MARKETING_CHECKBOX_LABEL, trim($value));
    }

    public static function get_balance(): string {
        return get_option(self::BALANCE, '');
    }

    public static function set_balance(?string $value) {
        update_option(self::BALANCE, $value);
    }

    public static function get_latest_version(): string {
        return get_option(self::LATEST_VERSION, '');
    }

    public static function set_latest_version(?string $value) {
        update_option(self::LATEST_VERSION, $value);
    }

    public static function get_last_ping_at(): int {
        return get_option(self::LAST_PING_AT, 0);
    }

    public static function set_last_ping_at($value) {
        update_option(self::LAST_PING_AT, intval($value));
    }

    public static function get_last_sync_at(): int {
        return get_option(self::LAST_SYNC_AT, 0);
    }

    public static function set_last_sync_at($value) {
        update_option(self::LAST_SYNC_AT, intval($value));
    }

    public static function set_ignored_roles(array $value) {
        unset($value['customer']); // never ignore customers
        update_option(self::IGNORED_ROLES, $value);
    }

    public static function get_ignored_roles() {
        return get_option(self::IGNORED_ROLES, self::DEFAULT_ROLES);
    }

    public static function is_ignored_role($role) {
        return in_array($role, self::get_ignored_roles());
    }

    public static function set_cron_timestamp() {
        update_option(self::CRON_TIMESTAMP, time());
    }

    public static function get_cron_timestamp() {
        return get_option(self::CRON_TIMESTAMP, 0);
    }

    public static function init() {
        add_option(self::API_KEY, null);
        add_option(self::IS_VALID_API_KEY, false);
        add_option(self::HAS_BALANCE, false);
        add_option(self::IS_WEBSITE_ACTIVE, false);
        add_option(self::PHONE_ON_TOP, false);
        add_option(self::MARKETING_CHECKBOX_ENABLED, false);
        add_option(self::MARKETING_CHECKBOX_LABEL, self::get_default_marketing_checkbox_label());
        add_option(self::BALANCE, '');
        add_option(self::LAST_PING_AT, 0);
        add_option(self::LAST_SYNC_AT, 0);
        add_option(self::IGNORED_ROLES, self::DEFAULT_ROLES);
        add_option(self::LATEST_VERSION, '');
        add_option(self::CRON_TIMESTAMP, 0);
    }

    public static function reset() {
        delete_option(self::PHONE_ON_TOP);
        delete_option(self::MARKETING_CHECKBOX_ENABLED);
        delete_option(self::MARKETING_CHECKBOX_LABEL);
        delete_option(self::IS_WEBSITE_ACTIVE);
        delete_option(self::BALANCE);
        delete_option(self::HAS_BALANCE);
        delete_option(self::LAST_PING_AT);
        delete_option(self::LAST_SYNC_AT);
        delete_option(self::IS_VALID_API_KEY);
        delete_option(self::API_KEY);
        delete_option(self::IGNORED_ROLES);
        delete_option(self::LATEST_VERSION);
        delete_option(self::CRON_TIMESTAMP);
    }
}
