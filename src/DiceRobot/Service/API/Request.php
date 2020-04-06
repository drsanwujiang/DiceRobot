<?php
namespace DiceRobot\Service\API;

use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;

/**
 * Request service. Send HTTP request via cURL.
 */
class Request
{
    private $ch = NULL;
    private static ?Request $instance = NULL;

    private function __construct()
    {
        $this->ch = curl_init();
    }

    public function __destruct()
    {
        if (is_resource($this->ch))
        {
            curl_close($this->ch);
            $this->ch = null;
        }
    }

    public static function getInstance(): Request
    {
        if (!self::$instance instanceof self)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Build cURL request.
     *
     * @param string $url URL
     * @param bool $http2 HTTP2 flag
     * @param string|null $method HTTP method
     * @param array|null $data Data
     * @param string|null $auth Authorization
     *
     * @return string Content
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function request( string $url, bool $http2, ?string $method, ?array $data, ?string $auth): string
    {
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "DiceRobot/" . DICEROBOT_VERSION
        ];
        $headers = [
            "Content-Type: application/json",
            "Timestamp: " . time()
        ];

        if ($http2)
            $options[CURLOPT_HTTP_VERSION] =  CURL_HTTP_VERSION_2TLS;

        if (!is_null($method))
            $options[CURLOPT_CUSTOMREQUEST] = $method;

        if (!is_null($data))
            $options[CURLOPT_POSTFIELDS] = json_encode($data);

        if (!is_null($auth))
            $headers[] = "Authorization: Bearer " . $auth;

        $options[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($this->ch, $options);

        return $this->execute();
    }

    /**
     * Execute cURL request.
     *
     * @return string Content
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    private function execute(): string
    {
        $result = curl_exec($this->ch);
        $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        curl_reset($this->ch);

        if ($result === false)
            throw new NetworkErrorException();

        if ($code >= 400)
            throw new InternalErrorException();

        return $result;
    }
}
