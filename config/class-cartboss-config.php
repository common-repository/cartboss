<?php


class Cartboss_Config_Base {
    public function get($key, $default = null) {
        if (property_exists($this, $key)) {
            return $this->$key;
        }

        return $default;
    }
}

class Cartboss_Config_Development extends Cartboss_Config_Base {
    var $plugin_version = CARTBOSS_VERSION;
    var $debug = true;
    var $sync_delay_atc = 0;
    var $api_host = 'http://127.0.0.1:8082/3';
}

class Cartboss_Config_Staging extends Cartboss_Config_Development {
    var $api_host = 'https://api.cartboss.io/3';
}

class Cartboss_Config_Production extends Cartboss_Config_Staging {
    var $debug = false;
    var $sync_delay_atc = 60 * 3;
}

final class Cartboss_Config {
    const DEVELOPMENT = 'dev';
    const STAGING = 'stage';
    const PRODUCTION = 'prod';

    private static $instance = null;

    public static function init(string $environment): Cartboss_Config_Base {
        if ($environment == self::DEVELOPMENT) {
            self::$instance = new Cartboss_Config_Development();
        } elseif ($environment == self::STAGING) {
            self::$instance = new Cartboss_Config_Staging();
        } elseif ($environment == self::PRODUCTION) {
            self::$instance = new Cartboss_Config_Production();
        } else {
            throw new InvalidArgumentException('Unknown environment given');
        }

        return self::$instance;
    }

    public static function instance(): Cartboss_Config_Base {
        return self::$instance;
    }
}