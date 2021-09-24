<?php

declare(strict_types=1);

namespace DiceRobot\Factory;

use DiceRobot\Data\Config;
use Imefisto\PsrSwoole\ResponseMerger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

/**
 * Class ResponseFactory
 *
 * The factory of HTTP response.
 *
 * @package DiceRobot\Factory
 */
class ResponseFactory
{
    /** @var string[] Return messages. */
    protected const RETURN_MESSAGES = [
        0 => "Success",

        -400 => "Bad request",
        -401 => "Unauthorized",
        -403 => "Forbidden",
        -404 => "Not found",
        -405 => "Method not allowed",
        -500 => "Internal server error",

        -900 => "Access denied",
        -901 => "Parameters invalid",
        -910 => "Composer not found",

        -1000 => "DiceRobot already paused",
        -1001 => "DiceRobot cannot be paused",
        -1010 => "DiceRobot already running",
        -1011 => "DiceRobot cannot be rerun",
        -1012 => "Rerun DiceRobot failed",
        -1020 => "Reload DiceRobot failed",
        -1030 => "DiceRobot exited non-normally",
        -1040 => "DiceRobot cannot be restarted",
        -1050 => "DiceRobot root undefined or invalid",
        -1051 => "Update DiceRobot failed",
        -1060 => "Parameters invalid",
        -1061 => "Config prohibited",
        -1070 => "Parameters invalid",
        -1071 => "Update skeleton failed",
        -1072 => "Skeleton cannot be updated",
        -1080 => "Get log failed",
        -1090 => "Get reference failed",
        -1100 => "Get deck failed",
        -1110 => "Get rule failed",
        -1120 => "Parameters invalid",
        -1121 => "Set reference failed",
        -1130 => "Parameters invalid",
        -1131 => "Set deck failed",
        -1140 => "Parameters invalid",
        -1141 => "Set rule failed",
        -1150 => "Parameters invalid",
        -1151 => "Deck already exists",
        -1152 => "Add deck failed",
        -1160 => "Parameters invalid",
        -1161 => "Rule already exists",
        -1162 => "Add rule failed",
        -1170 => "Parameters invalid",
        -1171 => "Delete deck failed",
        -1180 => "Parameters invalid",
        -1181 => "Delete rule failed",

        -2000 => "Mirai not setup as service",
        -2001 => "Start Mirai failed",
        -2010 => "Mirai not setup as service",
        -2011 => "Stop Mirai failed",
        -2020 => "Mirai not setup as service",
        -2021 => "Restart Mirai failed",
        -2030 => "Parameters invalid",
        -2031 => "Parameters prohibited",
        -2032 => "Mirai root path not found",
        -2033 => "Script not found",
        -2034 => "Script error",
        -2035 => "Update Mirai failed"
    ];

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var Psr17Factory PSR-17 HTTP factory. */
    protected Psr17Factory $psr17Factory;

    /** @var ResponseMerger PSR-7 response merger. */
    protected ResponseMerger $responseMerger;

    /** @var ResponseInterface PSR-7 empty response template. */
    protected ResponseInterface $emptyResponse;

    /** @var ResponseInterface PSR-7 preflight response template. */
    protected ResponseInterface $preflightResponse;

    /** @var ResponseInterface PSR-7 bad request response template. */
    protected ResponseInterface $badRequestResponse;

    /** @var ResponseInterface PSR-7 unauthorized response template. */
    protected ResponseInterface $unauthorizedResponse;

    /** @var ResponseInterface PSR-7 forbidden response template. */
    protected ResponseInterface $forbiddenResponse;

    /** @var ResponseInterface PSR-7 not found response template. */
    protected ResponseInterface $notFoundResponse;

    /** @var ResponseInterface PSR-7 method not allowed response template. */
    protected ResponseInterface $methodNotAllowedResponse;

    /** @var ResponseInterface PSR-7 internal service error response template. */
    protected ResponseInterface $internalServerErrorResponse;

    /** @var ResponseInterface PSR-7 response template. */
    protected ResponseInterface $response;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param Psr17Factory $psr17Factory PSR-17 HTTP factory.
     * @param ResponseMerger $responseMerger PSR-7 response merger.
     */
    public function __construct(
        Config $config,
        Psr17Factory $psr17Factory,
        ResponseMerger $responseMerger
    ) {
        $this->config = $config;
        $this->psr17Factory = $psr17Factory;
        $this->responseMerger = $responseMerger;

        $this->emptyResponse = $this->psr17Factory->createResponse(204)
            ->withHeader("Server", "DiceRobot/{$this->config->getString("dicerobot.version")}");

        $this->preflightResponse = $this->emptyResponse
            ->withStatus(200)
            ->withHeader("Access-Control-Allow-Origin", "*")
            ->withHeader("Access-Control-Allow-Methods", "GET, POST, PATCH, PUT, DELETE, OPTIONS")
            ->withHeader("Access-Control-Allow-Headers", "*");

        $this->badRequestResponse = $this->preflightResponse
            ->withStatus(400)
            ->withHeader("Content-type", "application/json; charset=utf-8")
            ->withBody($this->psr17Factory->createStream((string) json_encode([
                "code" => -400,
                "message" => self::RETURN_MESSAGES[-400]
            ])));

        $this->unauthorizedResponse = $this->preflightResponse
            ->withStatus(401)
            ->withHeader("Content-type", "application/json; charset=utf-8")
            ->withBody($this->psr17Factory->createStream((string) json_encode([
                "code" => -401,
                "message" => self::RETURN_MESSAGES[-401]
            ])));

        $this->forbiddenResponse = $this->preflightResponse
            ->withStatus(403)
            ->withHeader("Content-type", "application/json; charset=utf-8")
            ->withBody($this->psr17Factory->createStream((string) json_encode([
                "code" => -403,
                "message" => self::RETURN_MESSAGES[-403]
            ])));

        $this->notFoundResponse = $this->preflightResponse
            ->withStatus(404)
            ->withHeader("Content-type", "application/json; charset=utf-8")
            ->withBody($this->psr17Factory->createStream((string) json_encode([
                "code" => -404,
                "message" => self::RETURN_MESSAGES[-404]
            ])));

        $this->methodNotAllowedResponse = $this->preflightResponse
            ->withStatus(405)
            ->withHeader("Content-type", "application/json; charset=utf-8")
            ->withBody($this->psr17Factory->createStream((string) json_encode([
                "code" => -405,
                "message" => self::RETURN_MESSAGES[-405]
            ])));

        $this->internalServerErrorResponse = $this->preflightResponse
            ->withStatus(500)
            ->withHeader("Content-type", "application/json; charset=utf-8")
            ->withBody($this->psr17Factory->createStream((string) json_encode([
                "code" => -500,
                "message" => self::RETURN_MESSAGES[-500]
            ])));

        $this->response = $this->preflightResponse
            ->withHeader("Content-type", "application/json; charset=utf-8");
    }

    /**
     * Create empty response.
     *
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createEmpty(Response $response): Response
    {
        return $this->responseMerger->toSwoole(clone $this->emptyResponse, $response);
    }

    /**
     * Create preflight response.
     *
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createPreflight(Response $response): Response
    {
        return $this->responseMerger->toSwoole(clone $this->preflightResponse, $response);
    }

    /**
     * Create 400 Bad Request response.
     *
     * @param int|null $code Result code.
     * @param array|null $data Return data.
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createBadRequest(?int $code, ?array $data, Response $response): Response
    {
        $content = [
            "code" => $code ?? -400,
            "message" => self::RETURN_MESSAGES[$code] ?? self::RETURN_MESSAGES[-400]
        ];

        if ($data) {
            $content["data"] = $data;
        }

        $psrResponse = $this->badRequestResponse
            ->withBody($this->psr17Factory->createStream((string) json_encode($content)));

        return $this->responseMerger->toSwoole($psrResponse, $response);
    }

    /**
     * Create 401 Unauthorized response.
     *
     * @param int|null $code Result code.
     * @param array|null $data Return data.
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createUnauthorized(?int $code, ?array $data, Response $response): Response
    {
        $content = [
            "code" => $code ?? -401,
            "message" => self::RETURN_MESSAGES[$code] ?? self::RETURN_MESSAGES[-401]
        ];

        if ($data) {
            $content["data"] = $data;
        }

        $psrResponse = $this->unauthorizedResponse
            ->withBody($this->psr17Factory->createStream((string) json_encode($content)));

        return $this->responseMerger->toSwoole($psrResponse, $response);
    }

    /**
     * Create 403 Forbidden response.
     *
     * @param int|null $code Result code.
     * @param array|null $data Return data.
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createForbidden(?int $code, ?array $data, Response $response): Response
    {
        $content = [
            "code" => $code ?? -403,
            "message" => self::RETURN_MESSAGES[$code] ?? self::RETURN_MESSAGES[-403]
        ];

        if ($data) {
            $content["data"] = $data;
        }

        $psrResponse = $this->forbiddenResponse
            ->withBody($this->psr17Factory->createStream((string) json_encode($content)));

        return $this->responseMerger->toSwoole($psrResponse, $response);
    }

    /**
     * Create 404 Not Found response.
     *
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createNotFound(Response $response): Response
    {
        return $this->responseMerger->toSwoole(clone $this->notFoundResponse, $response);
    }

    /**
     * Create 405 Method Not Allowed response.
     *
     * @param int|null $code Result code.
     * @param array|null $data Return data.
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createMethodNotAllowed(?int $code, ?array $data, Response $response): Response
    {
        $content = [
            "code" => $code ?? -405,
            "message" => self::RETURN_MESSAGES[$code] ?? self::RETURN_MESSAGES[-405]
        ];

        if ($data) {
            $content["data"] = $data;
        }

        $psrResponse = $this->methodNotAllowedResponse
            ->withBody($this->psr17Factory->createStream((string) json_encode($content)));

        return $this->responseMerger->toSwoole($psrResponse, $response);
    }

    /**
     * Create 500 Internal Server Error response (with data).
     *
     * @param int|null $code Result code.
     * @param array|null $data Return data.
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function createInternalServerError(?int $code, ?array $data, Response $response): Response
    {
        $content = [
            "code" => $code ?? -500,
            "message" => self::RETURN_MESSAGES[$code] ?? self::RETURN_MESSAGES[-500]
        ];

        if ($data) {
            $content["data"] = $data;
        }

        $psrResponse = $this->internalServerErrorResponse
            ->withBody($this->psr17Factory->createStream((string) json_encode($content)));

        return $this->responseMerger->toSwoole($psrResponse, $response);
    }

    /**
     * Create Swoole response (with data).
     *
     * @param int $code Result code.
     * @param array|null $data Return data.
     * @param Response $response Swoole response.
     *
     * @return Response Merged Swoole response.
     */
    public function create(int $code, ?array $data, Response $response): Response
    {
        $content = [
            "code" => $code,
            "message" => self::RETURN_MESSAGES[$code] ?? "Unknown code"
        ];

        if ($data) {
            $content["data"] = $data;
        }

        $psrResponse = $this->response
            ->withBody($this->psr17Factory->createStream((string) json_encode($content)));

        if ($code != 0) {
            $psrResponse = $psrResponse->withStatus(202);
        }

        return $this->responseMerger->toSwoole($psrResponse, $response);
    }
}
