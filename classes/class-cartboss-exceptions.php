<?php


class Cartboss_Api_Exception extends Exception
{

    protected $request;


    protected $response;

    /**
     * ISO8601 representation of the moment this exception was thrown
     *
     * @var DateTimeImmutable
     */
    protected $raisedAt;


    public function __construct(
        $message = "",
        $code = 0,
        $request = null,
        $response = null,
        $previous = null
    )
    {
        $this->raisedAt = new DateTimeImmutable();

        $formattedRaisedAt = $this->raisedAt->format(DateTimeInterface::ISO8601);
        $message = "[{$formattedRaisedAt}] " . $message;

        if (!empty($response)) {
            $this->response = $response;
        }

        $this->request = $request;
        if ($request) {
            $requestBody = $request->getBody()->__toString();

            if ($requestBody) {
                $message .= ". Request body: {$requestBody}";
            }
        }

        parent::__construct($message, $code, $previous);
    }


    public static function createFromError(WP_Error $error): Cartboss_Api_Exception{
        return new self(
            "Error executing API call ({$error->get_error_code()}: {$error->get_error_message($error->get_error_code())})",
            0,
            null,
            $error,
            null
        );
    }

    /**
     * @throws Cartboss_Api_Exception
     */
    public static function createFromResponse(WP_HTTP_Requests_Response $response, $request = null, $previous = null): Cartboss_Api_Exception
    {
        $object = static::parseResponseBody($response);

        $detail = print_r($object->detail, true);

        return new self(
            "Error executing API call ({$object->status}: {$object->title}): {$detail}",
            $response->get_status(),
            $request,
            $response,
            $previous
        );
    }

    /**
     * @throws Cartboss_Api_Exception
     */
    protected static function parseResponseBody(WP_HTTP_Requests_Response $response): stdClass
    {
        $body = $response->get_data();

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new self("Unable to decode CartBoss response: '{$body}'.");
        }

        return $object;
    }

    public function getResponse(): ?WP_HTTP_Requests_Response
    {
        return $this->response;
    }

    /**
     * @return bool
     */
    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * Get the ISO8601 representation of the moment this exception was thrown
     *
     * @return DateTimeImmutable
     */
    public function getRaisedAt(): DateTimeImmutable
    {
        return $this->raisedAt;
    }
}

class UnrecognizedClientException extends Cartboss_Api_Exception
{
}