<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\App;
use DiceRobot\Data\Config;
use DiceRobot\Factory\{LoggerFactory, ResponseFactory};
use DiceRobot\Server;
use DiceRobot\Service\{RobotService, StatisticsService};
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\System;
use Swoole\Http\Response;

/**
 * Class PanelHandler
 *
 * Panel handler.
 *
 * @package DiceRobot\Handlers
 */
class PanelHandler
{
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var App Application. */
    protected App $app;

    /** @var Server DiceRobot HTTP server. */
    protected Server $server;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var StatisticsService Statistics service. */
    protected StatisticsService $statistics;

    /** @var ResponseFactory HTTP response factory. */
    protected ResponseFactory $responseFactory;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param App $app Application.
     * @param RobotService $robot Robot service.
     * @param StatisticsService $statistics Statistics service.
     * @param ResponseFactory $responseFactory HTTP response factory.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        Config $config,
        App $app,
        RobotService $robot,
        StatisticsService $statistics,
        ResponseFactory $responseFactory,
        LoggerFactory $loggerFactory
    ) {
        $this->config = $config;
        $this->app = $app;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->responseFactory = $responseFactory;

        $this->logger = $loggerFactory->create("Handler");
    }

    /**
     * Initialize panel handler.
     *
     * @param Server $server DiceRobot HTTP server.
     */
    public function initialize(Server $server): void
    {
        $this->server = $server;

        $this->logger->info("Panel handler initialized.");
    }

    /**
     * CORS preflight.
     *
     * @param Response $response  HTTP response.
     *
     * @return Response HTTP response.
     */
    public function preflight(Response $response): Response
    {
        return $this->responseFactory->createPreflight($response);
    }

    /**
     * 404 Not Found.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function notFound(Response $response): Response
    {
        return $this->responseFactory->createNotFound($response);
    }

    /**
     * Get profile.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function getProfile(Response $response): Response
    {
        $this->logger->info("Server received HTTP request, get robot profile.");

        $data = [
            "id" => $this->robot->getId(),
            "nickname" => $this->robot->getNickname(),
            "friends" => $this->robot->getFriendCount(),
            "groups" => $this->robot->getGroupCount(),
            "startup" => DICEROBOT_STARTUP,
            "version" => [
                "dicerobot" => DICEROBOT_VERSION,
                "mirai" => $this->robot->getVersion()
            ]
        ];

        return $this->responseFactory->create(0, $data, $response);
    }

    /**
     * Get status.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function getStatus(Response $response): Response
    {
        $this->logger->info("Server received HTTP request, get application status.");

        $appStatus = $this->app->getStatus()->getValue();
        $code = -1;

        extract(System::exec(
            "/bin/systemctl status {$this->config->getString("dicerobot.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 4) {
            $serviceStatus = -2;
        } elseif ($code == 3) {
            $serviceStatus = -1;
        } elseif ($code == 0) {
            $serviceStatus = 0;
        } else {
            $serviceStatus = -3;
        }

        return $this->responseFactory->create(0, ["app" => $appStatus, "service" => $serviceStatus], $response);
    }

    /**
     * Get statistics.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function getStatistics(Response $response): Response
    {
        $this->logger->info("HTTP request received, get statistics.");

        $data = $this->statistics->getSortedData();

        return $this->responseFactory->create(0, $data, $response);
    }

    /**
     * Get DiceRobot config.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function getConfig(Response $response): Response
    {
        $this->logger->info("HTTP request received, get config.");

        $data = [
            "strategy" => $this->config->getArray("strategy"),
            "order" => $this->config->getArray("order"),
            "reply" => $this->config->getArray("reply"),
            "errMsg" => $this->config->getArray("errMsg"),
        ];

        return $this->responseFactory->create(0, $data, $response);
    }

    /**
     * Set panel config.
     *
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function setConfig(string $content, Response $response): Response
    {
        $this->logger->info("Server received HTTP request, set config.");

        $code = $this->app->setConfig($content);

        if ($code == -1) {
            $code = -1060;
        } elseif ($code == -2) {
            $code = -1061;
        }

        return $this->responseFactory->create($code, null, $response);
    }

    /**
     * Connect.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function connect(Response $response): Response
    {
        $this->logger->info("HTTP request received, connect to application.");

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Pause application.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function pause(Response $response): Response
    {
        $this->logger->notice("HTTP request received, pause application.");

        $code = $this->app->pause();

        if ($code == -1) {
            $code = -1000;
        } elseif ($code == -2) {
            $code = -1001;
        }

        return $this->responseFactory->create($code, null, $response);
    }

    /**
     * Rerun application.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function rerun(Response $response): Response
    {
        $this->logger->notice("HTTP request received, rerun application.");

        $code = $this->app->run();

        if ($code == -1) {
            $code = -1010;
        } elseif ($code == -2) {
            $code = -1011;
        } elseif ($code == -3) {
            $code = -1012;
        }

        return $this->responseFactory->create($code, null, $response);
    }

    /**
     * Reload application.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function reload(Response $response): Response
    {
        $this->logger->notice("HTTP request received, reload application.");

        $code = $this->app->reload();

        if ($code == -1) {
            $code = -1020;
        }

        return $this->responseFactory->create($code, null, $response);
    }

    /**
     * Stop application and server.
     *
     * @param Response $response HTTP response.
     */
    public function stop(Response $response): void
    {
        $this->logger->notice("HTTP request received, stop application.");

        $code = $this->app->stop();

        if ($code == -1) {
            $code = -1030;
        }

        $this->responseFactory->create($code, null, $response)->end();

        $this->server->stop();
    }

    /**
     * Restart application and server.
     *
     * @param Response $response HTTP response.
     */
    public function restart(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, restart application.");

        $code = -1;

        extract(System::exec(
            "/bin/systemctl status {$this->config->getString("dicerobot.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code != 0) {
            $this->responseFactory->create(1040, null, $response)->end();
        } else {
            $this->responseFactory->create(0, null, $response)->end();

            System::exec("/bin/systemctl restart {$this->config->getString("dicerobot.service.name")}");
        }
    }

    /**
     * Update DiceRobot.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function update(Response $response): Response
    {
        $this->logger->notice("Server received HTTP request, update DiceRobot.");

        if (!is_dir($root = $this->config->getString("root"))) {
            return $this->responseFactory->create(-1050, null, $response);
        }

        $code = $signal = -1;
        $output = "";

        extract(System::exec(
            "/usr/local/bin/composer update --working-dir {$root} --no-ansi --no-interaction --quiet 2>&1"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            return $this->responseFactory->create($code, null, $response);
        } else {
            $this->logger->critical(
                "Failed to update DiceRobot. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 127) {
                return $this->responseFactory->create(-1051, null, $response);
            } else {
                return $this->responseFactory->create(
                    -1052,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                );
            }
        }
    }

    /**
     * Get Mirai status.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function getMiraiStatus(Response $response): Response
    {
        $this->logger->info("Server received HTTP request, get Mirai status.");

        $code = -1;

        extract(System::exec(
            "/bin/systemctl status {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 4) {
            $status = -2;
        } elseif ($code == 3) {
            $status = -1;
        } elseif ($code == 0) {
            $status = 0;
        } else {
            $status = -3;
        }

        return $this->responseFactory->create(0, ["status" => $status], $response);
    }

    /**
     * Start Mirai.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function startMirai(Response $response): Response
    {
        $this->logger->notice("Server received HTTP request, start Mirai.");

        $code = $signal = -1;
        $output = "";

        extract(System::exec(
            "/bin/systemctl start {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            return $this->responseFactory->create($code, null, $response);
        } else {
            $this->logger->critical(
                "Failed to start Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 5) {
                return $this->responseFactory->create(-2000, null, $response);
            } else {
                return $this->responseFactory->create(
                    -2001,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                );
            }
        }
    }

    /**
     * Stop Mirai.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function stopMirai(Response $response): Response
    {
        $this->logger->notice("Server received HTTP request, stop Mirai.");

        $code = $signal = -1;
        $output = "";

        extract(System::exec(
            "/bin/systemctl stop {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            return $this->responseFactory->create($code, null, $response);
        } else {
            $this->logger->critical(
                "Failed to stop Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 5) {
                return $this->responseFactory->create(-2010, null, $response);
            } else {
                return $this->responseFactory->create(
                    -2011,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                );
            }
        }
    }

    /**
     * Restart Mirai.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    public function restartMirai(Response $response): Response
    {
        $this->logger->notice("Server received HTTP request, restart Mirai.");

        $code = $signal = -1;
        $output = "";

        extract(System::exec(
            "/bin/systemctl restart {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            return $this->responseFactory->create($code, null, $response);
        } else {
            $this->logger->critical(
                "Failed to restart Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 5) {
                return $this->responseFactory->create(-2020, null, $response);
            } else {
                return $this->responseFactory->create(
                    -2021,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                );
            }
        }
    }
}
