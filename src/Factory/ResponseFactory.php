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

        -1000 => "Application is already paused",
        -1001 => "Application cannot be paused",
        -1010 => "Application is already running",
        -1011 => "Application cannot be rerun",
        -1012 => "Rerun application failed",
        -1020 => "",  // Undefined reload error
        -1030 => "",  // Undefined stop error
        -1040 => "Application cannot be restarted",

        -2000 => "Mirai is not setup as service",
        -2001 => "Start Mirai failed",
        -2010 => "Mirai is not setup as service",
        -2011 => "Stop Mirai failed",
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
