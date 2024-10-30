<?php


use Delight\Cookie\Cookie;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class Cartboss_Utils {
    const CIPHER = 'aes-256-cbc';

    public static function is_staging(): bool {
        return str_contains(self::get_array_value($_SERVER, 'HTTP_HOST', ''), 'cloudwaysapps.com');
    }

    public static function is_development(): bool {
        return str_contains(self::get_array_value($_SERVER, 'HTTP_HOST', ''), 'localhost');
    }

    public static function get_current_url(): string {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    public static function get_array_value($arr, $key, $default = null) {
        if (!is_array($arr)) {
            return $default;
        }
        if (!array_key_exists($key, $arr)) {
            return $default;
        }
        if (empty($arr[$key])) {
            return $default;
        }

        return $arr[$key];
    }

    public static function get_visitor_ip(): ?string {
        foreach (
            array(
                'HTTP_CF_CONNECTING_IP',
                'TRUE_CLIENT_IP',
                'HTTP_CLIENT_IP',
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ) as $key
        ) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return null;
    }

    public static function is_bot(): bool {
        try {
            $cd = new CrawlerDetect();
            return $cd->isCrawler(Cartboss_Utils::get_array_value($_SERVER, 'HTTP_USER_AGENT'));
        } catch (Exception $e) {
            return false;
        }
    }

    public static function get_first_non_empty_value() {
        foreach (func_get_args() as $arg) {
            if (isset($arg) && !empty($arg)) {
                return $arg;
            }
        }

        return null;
    }

    public static function get_i18n() {
        global $sitepress, $q_config, $polylang;

        if (!empty($sitepress) && is_object($sitepress) && method_exists($sitepress, 'get_active_languages')) {
            // WPML.
            return 'wpml';
        }

        if (!empty($polylang) && function_exists('pll_languages_list')) {
            $languages = pll_languages_list();

            if (empty($languages)) {
                return false;
            }

            // Polylang, Polylang Pro.
            return 'polylang';
        }

        if (!empty($q_config) && is_array($q_config)) {
            if (function_exists('qtranxf_convertURL')) {
                // qTranslate-x.
                return 'qtranslate-x';
            }

            if (function_exists('qtrans_convertURL')) {
                // qTranslate.
                return 'qtranslate';
            }
        }

        return false;
    }

    public static function get_home_url($lang = '') {
        $i18n_plugin = self::get_i18n();

        if (!$i18n_plugin) {
            return home_url();
        }

        switch ($i18n_plugin) {
            // WPML.
            case 'wpml':
                return $GLOBALS['sitepress']->language_url($lang);
            // qTranslate.
            case 'qtranslate':
                return qtrans_convertURL(home_url(), $lang, true);
            // qTranslate-x.
            case 'qtranslate-x':
                return qtranxf_convertURL(home_url(), $lang, true);
            // Polylang, Polylang Pro.
            case 'polylang':
                $pll = function_exists('PLL') ? PLL() : $GLOBALS['polylang'];

                if (!empty($pll->options['force_lang']) && isset($pll->links)) {
                    return pll_home_url($lang);
                }
        }

        return home_url();
    }

    public static function is_cart_full() {
        try {
            if (!WC()->cart->is_empty()) {
                return true;
            }
            if (WC()->cart->get_cart_contents_count() > 0) {
                return true;
            }
        } catch (Throwable $e) {
        }
        return false;
    }

    public static function get_product_image_url($product_id) {
        try {
            $url = wp_get_attachment_image_url(intval($product_id), array(800, 800));

            return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function is_valid_variation_id($val): bool {
        return isset($val) && !empty($val) && intval($val) > 0;
    }

    public static function is_true($val): bool {
        return wp_validate_boolean($val);
//        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function is_non_empty_string($val, $min_length = 0): bool {
        if (empty($val) || !is_string($val)) {
            return false;
        }

        $val = trim(strval($val));
        if (strlen($val) < $min_length) {
            return false;
        }

        return true;
    }

    public static function aes_decode($secret, $input) {
        if (!$input)
            return null;

        try {
            $secret = mb_substr($secret, 0, 32);
            $input = base64_decode($input);
            $json = json_decode($input);

            if ($json) {
                $iv = base64_decode($json->iv);
                $decrypted = openssl_decrypt($json->ciphertext, self::CIPHER, $secret, 0, $iv);
                if ($decrypted) {
                    return json_decode($decrypted, true);
                }
            }
        } catch (Exception $e) {
            Cartboss_Api_manager::instance()->log_error("Dectryption failed: {$e->getMessage()}");
        }

        return null;
    }

    public static function enable_coupons() {
        try {
            $coupons_enabled = esc_html(get_option('woocommerce_enable_coupons'));
            if ($coupons_enabled === "no" || !wc_coupons_enabled()) {
                update_option('woocommerce_enable_coupons', 'yes', true);
            }
        } catch (Exception $e) {
        }
    }

    public static function set_wc_customer_field($customer, $category, $field, $value) {
        if (isset($customer, $category, $field, $value)) {
            $method_name = "set_{$category}_{$field}";
            if ($customer && is_callable(array($customer, $method_name))) {
                call_user_func_array(array($customer, $method_name), array($value));
            }
        }
    }

    public static function timeago($timestamp) {
        if ($timestamp <= 0) {
            return "not yet";
        }

        $strTime = array("sec", "min", "hr", "day", "month", "year");
        $length = array("60", "60", "24", "30", "12", "10");

        $currentTime = time();
        if ($currentTime >= $timestamp) {
            $diff = time() - $timestamp;
            for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
                $diff = $diff / $length[$i];
            }

            $diff = round($diff);
            return $diff . " " . $strTime[$i] . "(s) ago ";
        }
        return "/";
    }

    public static function get_random_string($length): ?string {
        try {
            $out = bin2hex(random_bytes($length / 2));
        } catch (Throwable $e) {
            try {
                $out = bin2hex(openssl_random_pseudo_bytes($length / 2));
            } catch (Throwable $e) {
                $out = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRSTUVZX', ceil($length / strlen($x)))), 1, $length);
            }
        }
        return $out;
    }

    public static function is_logged_in_admin() {
        try {
            if (!is_user_logged_in()) {
                return false;
            }

            $ignored_roles = Cartboss_Options::get_ignored_roles();
            $user = wp_get_current_user();

            if (!empty($user->roles) && is_array($user->roles)) {
                foreach ($user->roles as $role) {
                    if (in_array($role, $ignored_roles)) {
                        return true;
                    }
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function get_editable_roles() {
        global $wp_roles;

        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles);
        unset($editable_roles['customer']);
        return $editable_roles;
    }

    public static function is_actual_checkout_page() {
        return is_checkout() && !is_wc_endpoint_url();
    }

    public static function is_visitor_requested_page() {
        return
            !is_admin() &&
            !wp_doing_ajax() &&
            !wp_doing_cron() &&
            !wp_is_json_request() &&
            !is_user_admin() &&
            !is_network_admin() &&
            self::get_array_value($_SERVER, 'REQUEST_METHOD', 'GET') === 'GET' &&
            strpos($_SERVER['REQUEST_URI'], ".map") === false;
    }

    public static function can_run_cartboss() {
        return self::is_logged_in_admin() === false;
    }


    public static function get_signature(string $secret, string $payload) {
        try {
            return hash_hmac('sha256', $payload, $secret);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function get_order($order_id) {
        try {
            $wc_order = wc_get_order($order_id);
        } catch (Exception $e) {
            $wc_order = null;
        }

        if (!$wc_order) {
            try {
                $wc_order = new WC_Order($order_id);
            } catch (Exception $e) {
                $wc_order = null;
            }
        }

        if ($wc_order)
            return $wc_order;

        return null;
    }

    public static function init_cookie($name, $max_age = 60 * 60 * 24 * 365) {
        $cookie = new Cookie($name);
        $cookie->setMaxAge($max_age);
        $cookie->setSecureOnly(true);
        $cookie->setSameSiteRestriction('None');
        $cookie->setPath(COOKIEPATH ? COOKIEPATH : '/');
        $cookie->setDomain(COOKIE_DOMAIN);
        return $cookie;
    }

    public static function set_cookie($name, $value, $expire = 0, $secure = false, $httponly = false) {
        if (self::is_cli() || self::is_cron() || self::is_rest()) {
            return;
        }
        if (headers_sent()) {
            return;
        }

        if (empty($value) || self::get_cookie($name) !== $value) {
            setcookie($name, $value, $expire == 0 ? 0 : time() + $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters('woocommerce_cookie_httponly', $httponly, $name, $value, $expire, $secure));
        }
    }

    public static function get_cookie($key, $default = null) {
        return isset($_COOKIE[$key]) ? self::cb_clean($_COOKIE[$key]) : $default;
    }

    public static function remove_cookie($key) {
        if (isset($_COOKIE[$key])) {
            unset($_COOKIE[$key]);
        }
        self::set_cookie($key, '', -1 * 60 * 60 * 24, true);
    }

    public static function set_session(string $key, $value = null) {
        if (function_exists('WC') && !is_null(WC()->session) && WC()->session->has_session()) {
            WC()->session->set($key, $value);
        }
    }

    public static function get_session(string $key, $default = '') {
        if (function_exists('WC') && !is_null(WC()->session) && WC()->session->has_session()) {
            return WC()->session->get($key, $default);
        }
        return $default;
    }

    public static function remove_session(string $key) {
        self::set_session($key, '');
    }

    public static function is_secure() {
        try {
            return apply_filters('wc_session_use_secure_cookie', is_ssl());
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Checks whether the current request is a WP cron request
     * @return bool
     */
    public static function is_cron() {
        if (defined('DOING_CRON') && true === DOING_CRON) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether the current request is a WP rest request
     * @return bool
     */
    public static function is_rest() {
        if (defined('REST_REQUEST') && true === REST_REQUEST) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether the current request is a WP CLI request
     * @return bool
     */
    public static function is_cli() {
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        return false;
    }

    public static function cb_clean($var) {
        if (is_array($var)) {
            return array_map('cb_clean', $var);
        } else {
            return is_scalar($var) ? sanitize_text_field($var) : $var;
        }
    }

    public static function send_event($payload) {
        if (!Cartboss_Options::get_is_valid_api_key()) {
            error_log("[CARTBOSS] Please provide valid API key to continue using CartBoss");
            return;
        }

        try {
            // actual sending
            Cartboss_Api_Manager::instance()->track($payload);

            // mark that cb server has been just contacted
            Cartboss_Options::set_last_sync_at(time());

        } catch (Cartboss_Api_Exception $e) {
            if ($e->getCode() == 422) {
                // 422 = validation error, usually phone missing
                // do not send
            } else {
                error_log("[CARTBOSS][API] Failed sending event: {$e}");

                Cartboss_Api_manager::instance()->log_error("API call failed #1: {$e}");
            }

        } catch (Exception $e) {
            Cartboss_Api_manager::instance()->log_error("API call failed #2: {$e}");
        }
    }
}