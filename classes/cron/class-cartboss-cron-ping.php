<?php


use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Cron_Ping')) :

    class Cartboss_Cron_Ping extends Cartboss_Cron {
        var $label = 'CartBoss Ping';
        var $interval = 60 * 60 * 6;

        public function do_handle() {
            try {
                if (!Cartboss_Options::get_is_valid_api_key()) {
                    return;
                }

                $response = Cartboss_Api_Manager::instance()->ping();

                $serializer = new Serializer(
                    [
                        new ObjectNormalizer(null, null, null, new class implements PropertyTypeExtractorInterface {
                            public function getTypes($class, $property, array $context = array()) {
                                if (!is_a($class, Cartboss_Site_Model::class, true)) {
                                    return null;
                                }

                                if ($property == 'balance') {
                                    return [new Type(Type::BUILTIN_TYPE_OBJECT, true, Cartboss_Money_Model::class)];
                                }

                                return null;
                            }
                        }),
                        new ArrayDenormalizer()
                    ],
                    [new JsonEncoder()]
                );

                $cb_site = $serializer->deserialize(json_encode($response), Cartboss_Site_Model::class, 'json');
                if ($cb_site) {
                    Cartboss_Options::set_is_website_active(Cartboss_Utils::is_true($cb_site->active));
                    Cartboss_Options::set_has_balance(floatval($cb_site->balance->amount) > 0);
                    Cartboss_Options::set_balance($cb_site->get_balance_view());
                    Cartboss_Options::set_latest_version($cb_site->wp_version);

                    // mark last ping
                    Cartboss_Options::set_last_ping_at(time());

                } else {
                    error_log("[CARTBOSS] Unable to parse ping response");
                }

            } catch (Cartboss_Api_Exception  $e) {
                if (intval($e->getCode()) == 401) {
                    Cartboss_Options::set_is_valid_api_key(false);
                    Cartboss_Api_manager::instance()->log_error("[CRON] Ping failed: {$e->getMessage()}");

                } else {
                    Cartboss_Api_Manager::instance()->log_error("[CRON] Ping failed: {$e->getMessage()}");
                }

            } catch (Exception $e) {
                error_log(substr("[CARTBOSS] Ping general error: " . print_r($e, true), 0, 1020));
            }
        }
    }

endif; // class_exists check

?>