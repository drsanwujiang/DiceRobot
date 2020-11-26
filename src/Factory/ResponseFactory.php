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
    /** @var string[] Return message */
    protected const RETURN_MESSAGE = [
        0 => "Success",

        -404 => "Not found",

        -1000 => "DiceRobot already paused",
        -1001 => "DiceRobot cannot be paused",
        -1010 => "DiceRobot already running",
        -1011 => "DiceRobot cannot be rerun",
        -1012 => "Rerun DiceRobot failed",
        -1020 => "Reload DiceRobot failed",
        -1030 => "DiceRobot exited non-normally",
        -1040 => "DiceRobot cannot be restarted",
        -1050 => "DiceRobot root undefined or invalid",
        -1051 => "Composer not found",
        -1052 => "Update DiceRobot failed",
        -1060 => "Config invalid",
        -1061 => "Config prohibited",

        -2000 => "Mirai not setup as service",
        -2001 => "Start Mirai failed",
        -2010 => "Mirai not setup as service",
        -2011 => "Stop Mirai failed",
        -2020 => "Mirai not setup as service",
        -2021 => "Restart Mirai failed",
    ];

    /** @var Config Config */
    protected Config $config;

    /** @var Psr17Factory PSR-17 HTTP factory */
    protected Psr17Factory $psr17Factory;

    /** @var ResponseMerger PSR-7 response merger */
    protected ResponseMerger $responseMerger;

    /** @var ResponseInterface PSR-7 empty response template */
    protected ResponseInterface $emptyResponse;

    /** @var ResponseInterface PSR-7 preflight response template */
    protected ResponseInterface $preflightResponse;

    /** @var ResponseInterface PSR-7 response template */
    protected ResponseInterface $response;

    /**
     * The constructor.
     *
     * @param Config $config
     * @param Psr17Factory $psr17Factory
     * @param ResponseMerger $responseMerger
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
            ->withHeader("Access-Control-Allow-Methods", "POST, GET, OPTIONS")
            ->withHeader("Access-Control-Allow-Headers", "*");

        $this->response = $this->preflightResponse
            ->withHeader("Content-type", "application/json; charset=utf-8");
    }

    /**
     * @param Response $response Swoole response
     *
     * @return Response Merged response
     */
    public function createEmpty(Response $response): Response
    {
        return $this->responseMerger->toSwoole(clone $this->emptyResponse, $response);
    }

    /**
     * @param Response $response Swoole response
     *
     * @return Response Merged response
     */
    public function createNotFound(Response $response): Response
    {
        $content = [
            "code" => -404,
            "message" => self::RETURN_MESSAGE[-404]
        ];

        return $this->responseMerger->toSwoole(
            $this->response->withStatus(404)
                ->withBody($this->psr17Factory->createStream((string) json_encode($content))),
            $response
        );
    }

    /**
     * @param Response $response Swoole response
     *
     * @return Response Merged response
     */
    public function createPreflight(Response $response): Response
    {
        return $this->responseMerger->toSwoole(clone $this->preflightResponse, $response);
    }

    /**
     * @param int $code Result code
     * @param array|null $data Return data
     * @param Response $response Swoole response
     *
     * @return Response Merged response
     */
    public function create(int $code, ?array $data, Response $response): Response
    {
        $content = [
            "code" => $code,
            "message" => self::RETURN_MESSAGE[$code] ?? "Unexpected code"
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
