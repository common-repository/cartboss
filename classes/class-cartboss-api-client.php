<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Api_Manager')) :

    class Cartboss_Api_Client {
        /**
         * HTTP status code for an empty ok response.
         */
        const HTTP_NO_CONTENT = 204;

        /**
         * HTTP Methods
         */
        const HTTP_GET = "GET";
        const HTTP_POST = "POST";

        /**
         * Default response timeout (in seconds).
         */
        const DEFAULT_TIMEOUT = 15;

        /**
         * @var string
         */
        private $api_host;
        /**
         * @var string
         */
        private $api_key;
        /**
         * @var string
         */
        private $plugin_version;
        /**
         * @var array
         */
        protected $version_strings = [];
        /**
         * @var int
         */
        private $timeout;


        public function __construct(string $api_host, ?string $api_key, string $plugin_version, int $timeout = null) {
            $this->api_host = $api_host;
            $this->api_key = $api_key;
            $this->plugin_version = $plugin_version;
            $this->timeout = $timeout ?? self::DEFAULT_TIMEOUT;

            $this->addVersionString("WORDPRESS/" . $this->plugin_version);
            $this->addVersionString("PHP/" . phpversion());
        }

        public function addVersionString($versionString): Cartboss_Api_Client {
            $this->version_strings[] = str_replace([" ", "\t", "\n", "\r"], '-', $versionString);
            return $this;
        }

        /**
         * @throws Cartboss_Api_Exception
         */
        public function performHttpCall($httpMethod, $apiMethod, $httpBody = null): ?stdClass {
            if (empty($this->api_key)) {
                throw new Cartboss_Api_Exception("You have not set an API key.");
            }

            $url = $this->api_host . "/" . $apiMethod;

            $userAgent = implode(' ', $this->version_strings);

            $headers = [
                'Accept' => "application/json",
                'Authorization' => "Bearer {$this->api_key}",
                'User-Agent' => $userAgent,
                'X-Cartboss-User-Agent' => $userAgent,
            ];

            if ($httpBody !== null) {
                $headers['Content-Type'] = "application/json";
            }

            if (function_exists("php_uname")) {
                $headers['X-Cartboss-Client-Info'] = php_uname();
            }

            return $this->send($httpMethod, $url, $headers, $httpBody);
        }

        /**
         * @throws Cartboss_Api_Exception
         */
        public function send($httpMethod, $url, $headers, $httpBody): ?stdClass {
            $params = array(
                'timeout' => $this->timeout,
                'headers' => $headers
            );

            if (isset($httpBody)) {
                $params['body'] = $httpBody;
            }

            if ($httpMethod == self::HTTP_GET) {
                $response = wp_remote_get($url, $params);

            } elseif ($httpMethod == self::HTTP_POST) {
                $response = wp_remote_post($url, $params);

            } else {
                throw new Cartboss_Api_Exception("Method '{$httpMethod}' not implemented", 0);
            }

            if (is_wp_error($response)) {
                throw Cartboss_Api_Exception::createFromError($response);
            }

            return $this->parse_response_body($response['http_response']);
        }

        /**
         * @param array $body
         * @return null|string
         * @throws Cartboss_Api_Exception
         */
        public function parse_request_body(array $body): ?string {
            if (empty($body)) {
                return null;
            }

            try {
                $encoded = @json_encode($body);
            } catch (InvalidArgumentException $e) {
                throw new Cartboss_Api_Exception("Error encoding parameters into JSON: '" . $e->getMessage() . "'.");
            }

            return $encoded;
        }

        /**
         * @param WP_HTTP_Requests_Response $response
         * @return stdClass|null
         * @throws Cartboss_Api_Exception
         */
        private function parse_response_body(WP_HTTP_Requests_Response $response): ?stdClass {
            $body = $response->get_data();
            if (empty($body)) {
                if ($response->get_status() === self::HTTP_NO_CONTENT) {
                    return null;
                }

                throw new Cartboss_Api_Exception("No response body found.");
            }

            $object = @json_decode($body);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Cartboss_Api_Exception("Unable to decode CartBoss response: '{$body}'.");
            }

            if ($response->get_status() >= 400) {
                throw Cartboss_Api_Exception::createFromResponse($response, null);
            }

            return $object;
        }
    }

endif; // class_exists check

?>