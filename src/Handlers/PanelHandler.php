<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\App;
use DiceRobot\Data\Config;
use DiceRobot\Data\CustomConfig;
use DiceRobot\Factory\{LoggerFactory, ResponseFactory};
use DiceRobot\Service\{LogService, ResourceService, RobotService, StatisticsService};
use DiceRobot\Util\Updater;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\System;
use Swoole\Http\Response;
use Swoole\Process;
use Throwable;

/**
 * Class PanelHandler
 *
 * Panel handler.
 *
 * @package DiceRobot\Handlers
 */
class PanelHandler
{
    /** @var ContainerInterface Container. */
    protected ContainerInterface $container;

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var App Application. */
    protected App $app;

    /** @var LogService Log service. */
    protected LogService $log;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

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
     * @param ContainerInterface $container Container.
     * @param Config $config DiceRobot config.
     * @param App $app Application.
     * @param LogService $log Log service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param StatisticsService $statistics Statistics service.
     * @param ResponseFactory $responseFactory HTTP response factory.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        App $app,
        LogService $log,
        ResourceService $resource,
        RobotService $robot,
        StatisticsService $statistics,
        ResponseFactory $responseFactory,
        LoggerFactory $loggerFactory
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->app = $app;
        $this->log = $log;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->responseFactory = $responseFactory;

        $this->logger = $loggerFactory->create("Panel");

        $this->logger->debug("Panel handler created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Panel handler destructed.");
    }

    /**
     * Handle panel request.
     *
     * @param string $method Request method.
     * @param string $uri Request URL.
     * @param string[] $queryParams Query parameters.
     * @param string $content Request content.
     * @param string $contentType Request content type.
     * @param Response $response Swoole response.
     *
     * @return Response Swoole response.
     */
    public function handle(
        string $method,
        string $uri,
        array $queryParams,
        string $content,
        string $contentType,
        Response $response
    ): Response {
        try {
            $this->route($method, $uri, $queryParams, $content, $contentType, $response);
        } catch (Throwable $t) {
            $details = sprintf(
                "Type: %s\nCode: %s\nMessage: %s\nFile: %s\nLine: %s\nTrace: %s",
                get_class($t),
                $t->getCode(),
                $t->getMessage(),
                $t->getFile(),
                $t->getLine(),
                $t->getTraceAsString()
            );

            $this->logger->error("Panel request failed, unexpected exception occurred:\n{$details}.");

            $this->error($response);
        }

        return $response;
    }

    /**
     * Route request.
     *
     * @param string $method Request method.
     * @param string $uri Request URL.
     * @param string[] $queryParams Query parameters.
     * @param string $content Request content.
     * @param string $contentType Request content type.
     * @param Response $response Swoole response.
     *
     * @return Response Swoole response.
     */
    protected function route(
        string $method,
        string $uri,
        array $queryParams,
        string $content,
        string $contentType,
        Response $response
    ): Response {
        if ($method == "OPTIONS") {
            $this->preflight($response);
        } elseif ($method == "POST" && $contentType == "application/json") {
            if ($uri == "/config") {
                $this->setConfig($content, $response);
            } elseif ($uri == "/mirai/update") {
                $this->updateMirai($content, $response);
            } else {
                $this->notFound($response);
            }
        } elseif ($method == "GET") {
            if ($uri == "/connect") {
                $this->connect($response);
            } elseif ($uri == "/pause") {
                $this->pause($response);
            } elseif ($uri == "/run") {
                $this->rerun($response);
            } elseif ($uri == "/reload") {
                $this->reload($response);
            } elseif ($uri == "/stop") {
                $this->stop($response);
            } elseif ($uri == "/restart") {
                $this->restart($response);
            } elseif ($uri == "/update") {
                $this->update($response);
            } elseif ($uri == "/profile") {
                $this->getProfile($response);
            } elseif ($uri == "/status") {
                $this->getStatus($response);
            } elseif ($uri == "/statistics") {
                $this->getStatistics($response);
            } elseif ($uri == "/config") {
                $this->getConfig($response);
            } elseif ($uri == "/logs") {
                $this->getLogList($response);
            } elseif ($uri == "/log") {
                $this->getLog($queryParams, $response);
            } elseif ($uri == "/skeleton/update") {
                $this->updateSkeleton($response);
            } elseif ($uri == "/mirai/status") {
                $this->getMiraiStatus($response);
            } elseif ($uri == "/mirai/start") {
                $this->startMirai($response);
            } elseif ($uri == "/mirai/stop") {
                $this->stopMirai($response);
            } elseif ($uri == "/mirai/restart") {
                $this->restartMirai($response);
            } else {
                $this->notFound($response);
            }
        } else {
            $this->notFound($response);
        }

        return $response;
    }

    /**
     * CORS preflight.
     *
     * @param Response $response  HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function preflight(Response $response): Response
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
    protected function notFound(Response $response): Response
    {
        return $this->responseFactory->createNotFound($response);
    }

    /**
     * 500 Internal Server Error.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function error(Response $response): Response
    {
        return $this->responseFactory->createError($response);
    }

    /**
     * Connect.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function connect(Response $response): Response
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
    protected function pause(Response $response): Response
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
    protected function rerun(Response $response): Response
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
    protected function reload(Response $response): Response
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
    protected function stop(Response $response): void
    {
        $this->logger->notice("HTTP request received, stop application.");

        $code = $this->app->stop();

        if ($code == -1) {
            $code = -1030;
        }

        $this->responseFactory->create($code, null, $response)->end();

        Process::kill(getmypid(), 10);  // Send SIGUSR1
    }

    /**
     * Restart application and server.
     *
     * @param Response $response HTTP response.
     */
    protected function restart(Response $response): void
    {
        $this->logger->notice("HTTP request received, restart application.");

        $code = -1;

        extract(System::exec(
            "/bin/systemctl status {$this->config->getString("dicerobot.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->responseFactory->create(0, null, $response)->end();

            System::exec("/bin/systemctl restart {$this->config->getString("dicerobot.service.name")}");
        } else {
            $this->responseFactory->create(1040, null, $response)->end();
        }
    }

    /**
     * Update DiceRobot.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function update(Response $response): Response
    {
        $this->logger->notice("HTTP request received, update DiceRobot.");

        if (!is_dir($root = $this->config->getString("root"))) {
            return $this->responseFactory->create(-1050, null, $response);
        }

        $code = $signal = -1;
        $output = "";

        extract(System::exec(
            "/usr/local/bin/composer --no-interaction --no-ansi --quiet update --working-dir {$root} --no-dev 2>&1"
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
     * Get profile.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getProfile(Response $response): Response
    {
        $this->logger->info("HTTP request received, get profile.");

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
    protected function getStatus(Response $response): Response
    {
        $this->logger->info("HTTP request received, get application status.");

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
    protected function getStatistics(Response $response): Response
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
    protected function getConfig(Response $response): Response
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
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function setConfig(string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, set config.");

        if (!is_array($data = json_decode($content, true))) {
            return $this->responseFactory->create(-1060, null, $response);
        }

        if (!$this->resource->getConfig()->setConfig($data)) {
            return $this->responseFactory->create(-1061, null, $response);
        }

        $this->config->load($this->container->make(CustomConfig::class), $this->resource->getConfig());

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Get DiceRobot log file list.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getLogList(Response $response): Response
    {
        $this->logger->info("HTTP request received, get log list.");

        $logs = $this->log->getLogList();

        return $this->responseFactory->create(0, ["logs" => $logs], $response);
    }

    /**
     * Get parsed DiceRobot log file content.
     *
     * @param array $params Query parameters.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getLog(array $params, Response $response): Response
    {
        $this->logger->info("HTTP request received, get log.");

        if (false === $log = $this->log->getLog($params["name"] ?? "")) {
            return $this->responseFactory->create(-1080, null, $response);
        }

        return $this->responseFactory->create(0, ["log" => $log], $response);
    }

    /**
     * Update DiceRobot skeleton.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function updateSkeleton(Response $response): Response
    {
        $this->logger->notice("HTTP request received, update skeleton.");

        /** @var Updater $updater */
        $updater = $this->container->make(Updater::class);

        $code = $updater->update();

        if ($code == -1) {
            $code = -1070;
        } elseif ($code == -2) {
            $code = -1071;
        }

        return $this->responseFactory->create($code, null, $response);
    }

    /**
     * Get Mirai status.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getMiraiStatus(Response $response): Response
    {
        $this->logger->info("HTTP request received, get Mirai status.");

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
    protected function startMirai(Response $response): Response
    {
        $this->logger->notice("HTTP request received, start Mirai.");

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
    protected function stopMirai(Response $response): Response
    {
        $this->logger->notice("HTTP request received, stop Mirai.");

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
    protected function restartMirai(Response $response): Response
    {
        $this->logger->notice("HTTP request received, restart Mirai.");

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

    /**
     * Update Mirai.
     *
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function updateMirai(string $content, Response $response): Response
    {
        $this->logger->notice("HTTP request received, update Mirai.");

        if (!is_array($params = json_decode($content, true))) {
            return $this->responseFactory->create(-2030, null, $response);
        }

        foreach ($params as $param) {
            if (!preg_match("/^[1-9]\.[0-9]\.[0-9]$/", $param)) {
                return $this->responseFactory->create(-2031, null, $response);
            }
        }

        if (!is_dir($root = $this->config->getString("mirai.path"))) {
            return $this->responseFactory->create(-2032, null, $response);
        }

        $code = $signal = -1;
        $output = "";

        $params = join(" ", $params);

        extract(System::exec(
            "/bin/bash {$root}/update-mirai.sh {$params}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            return $this->responseFactory->create($code, null, $response);
        } else {
            $this->logger->critical(
                "Failed to update Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 127) {
                return $this->responseFactory->create(-2033, null, $response);
            } elseif ($code == 126) {
                return $this->responseFactory->create(-2034, null, $response);
            } else {
                return $this->responseFactory->create(
                    -2035,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                );
            }
        }
    }
}
