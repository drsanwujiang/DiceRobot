<?php
namespace DiceRobot\Service;

use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Service\API\Request;

/**
 * API service.
 */
abstract class APIService
{
    protected static string $prefix;
    protected static Request $service;

    protected bool $h2;
    protected ?string $auth = NULL;

    /**
     * Set API URL prefix.
     *
     * @param string $prefix Prefix
     */
    public static function setPrefix(string $prefix): void
    {
        static::$prefix = $prefix;
    }

    /**
     * Send a request via RequestService.
     *
     * @param string $url URl
     * @param string $method HTTP method
     * @param array $data Data
     *
     * @return bool|string Returned content
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    protected function request(
        string $url,
        ?string $method = NULL,
        ?array $data = NULL
    ): string {
        if (empty(self::$service))
            self::$service = Request::getInstance();

        return self::$service->request($url, $this->h2, $method, $data, $this->auth);
    }
}
