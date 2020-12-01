<?php

declare(strict_types=1);

namespace DiceRobot;

use Co\Http\Server as SwooleServer;
use DiceRobot\Data\Config;
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Factory\{LoggerFactory, ResponseFactory};
use DiceRobot\Handlers\PanelHandler;
use Exception;
use Psr\Log\LoggerInterface;
use Swoole\Http\{Request, Response};
use Swoole\Process;

/**
 * Class Server
 *
 * DiceRobot HTTP server.
 *
 * @package DiceRobot
 */
class Server
{
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var SwooleServer Swoole HTTP server. */
    protected SwooleServer $server;

    /** @var App Application. */
    protected App $app;

    /** @var PanelHandler Panel handler. */
    protected PanelHandler $panelHandler;

    /** @var ResponseFactory HTTP response factory. */
    protected ResponseFactory $responseFactory;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param App $app Application.
     * @param PanelHandler $panelHandler Panel handler.
     * @param ResponseFactory $responseFactory HTTP response factory.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        Config $config,
        App $app,
        PanelHandler $panelHandler,
        ResponseFactory $responseFactory,
        LoggerFactory $loggerFactory
    ) {
        $this->config = $config;
        $this->app = $app;
        $this->panelHandler = $panelHandler;
        $this->responseFactory = $responseFactory;

        $this->logger = $loggerFactory->create("Server");

        $this->logger->debug("Server created.");

        // Application just created or successfully initialized
        if ($this->app->getStatus()->greaterThan(AppStatusEnum::RUNNING()) && $this->setServer()) {
            $this->setSignals();
            $this->setRoutes();

            $this->panelHandler->initialize($this);
        }
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->notice("Server exited.");
    }

    /**
     * Set Swoole HTTP server.
     *
     * @return bool Success.
     */
    protected function setServer(): bool
    {
        $host = $this->config->getString("dicerobot.server.host");
        $port = $this->config->getInt("dicerobot.server.port");

        try {
            $this->server = new SwooleServer($host, $port);
        } catch (Exception $e) {
            $this->logger->emergency("Server exited unexpectedly. Code {$e->getCode()}, message: {$e->getMessage()}.");

            $this->emergencyStop();

            return false;
        }

        $this->server->set([
            "http_parse_post" => false,
            "http_parse_cookie" => false
        ]);

        return true;
    }

    /**
     * Set signal handlers.
     */
    protected function setSignals(): void
    {
        // Signal SIGINT (program interrupted), stop the application
        Process::signal(SIGINT, [$this, "signalStop"]);

        // Signal SIGHUP (session terminated), stop the application
        Process::signal(SIGHUP, [$this, "signalStop"]);

        // Signal SIGTERM (program terminated), stop the application
        Process::signal(SIGTERM, [$this, "signalStop"]);

        // Signal SIGUSR2 (gracefully reload), reload the application
        Process::signal(SIGUSR2, [$this, "signalReload"]);
    }

    /**
     * Set route handlers.
     */
    protected function setRoutes(): void
    {
        $this->server->handle('/', [$this, "route"]);
    }

    /**
     * Start HTTP server and event loop.
     */
    public function start(): void
    {
        if (isset($this->server) && $this->app->getStatus()->greaterThan(AppStatusEnum::RUNNING())) {
            $this->logger->notice("Server started.");

            try {
                $this->server->start();
            } catch (Exception $e) {
                // Unexpected exit
                $this->logger->emergency("Server exited unexpectedly. Code {$e->getCode()}, message: {$e->getMessage()}.");

                $this->emergencyStop();
            }
        }
    }

    /**
     * Stop HTTP server and event loop.
     */
    public function stop(): void
    {
        $this->logger->info("Shutdown Swoole HTTP server...");

        $this->server->shutdown();
    }

    /**
     * Emergency stop.
     */
    protected function emergencyStop(): void
    {
        $this->logger->info("Stop application...");

        $this->app->stop();
    }

    /**
     * Reload application.
     *
     * @param int $signal Linux signal.
     */
    public function signalReload(int $signal): void
    {
        $this->logger->notice("Server received Linux signal {$signal}, reload application.");

        $this->app->reload();
    }

    /**
     * Stop application and server.
     *
     * @param int $signal Linux signal.
     */
    public function signalStop(int $signal): void
    {
        $this->logger->warning("Server received Linux signal {$signal}, stop application.");

        $this->app->stop();

        $this->stop();
    }

    /**
     * Route request.
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     */
    public function route(Request $request, Response $response): void
    {
        $method = $request->server["request_method"] ?? "";
        $uri = $request->server["request_uri"] ?? "";
        $content = (string) ($request->getContent() ?? "");

        if ($method == "POST" && $uri == "/report") {
            $this->report($content, $response);
        } elseif ($method == "POST" && $uri == "/heartbeat") {
            $this->heartbeat($response);
        } else {
            /** Panel APIs */

            // Check header
            if (!preg_match("/^DiceRobot Panel\/[1-9]\.[0-9]\.[0-9]$/", $request->header["x-dr-client"] ?? "")) {
                $this->panelHandler->notFound($response);
            } else {
                // Web APIs
                if ($method == "OPTIONS") {
                    $this->panelHandler->preflight($response);
                } elseif ($method == "POST") {
                    if ($uri == "/config") {
                        $this->panelHandler->setConfig($content, $response);
                    } else {
                        $this->panelHandler->notFound($response);
                    }
                } elseif ($method == "GET") {
                    if ($uri == "/connect") {
                        $this->panelHandler->connect($response);
                    } elseif ($uri == "/profile") {
                        $this->panelHandler->getProfile($response);
                    } elseif ($uri == "/status") {
                        $this->panelHandler->getStatus($response);
                    } elseif ($uri == "/statistics") {
                        $this->panelHandler->getStatistics($response);
                    } elseif ($uri == "/config") {
                        $this->panelHandler->getConfig($response);
                    } elseif ($uri == "/pause") {
                        $this->panelHandler->pause($response);
                    } elseif ($uri == "/run") {
                        $this->panelHandler->rerun($response);
                    } elseif ($uri == "/reload") {
                        $this->panelHandler->reload($response);
                    } elseif ($uri == "/stop") {
                        $this->panelHandler->stop($response);

                        return;
                    } elseif ($uri == "/restart") {
                        $this->panelHandler->restart($response);

                        return;
                    } elseif ($uri == "/update") {
                        $this->panelHandler->update($response);
                    } elseif ($uri == "/skeleton/update") {
                        $this->panelHandler->updateSkeleton($response);
                    } elseif ($uri == "/mirai/status") {
                        $this->panelHandler->getMiraiStatus($response);
                    } elseif ($uri == "/mirai/start") {
                        $this->panelHandler->startMirai($response);
                    } elseif ($uri == "/mirai/stop") {
                        $this->panelHandler->stopMirai($response);
                    } elseif ($uri == "/mirai/restart") {
                        $this->panelHandler->restartMirai($response);
                    } else {
                        $this->panelHandler->notFound($response);
                    }
                } else {
                    $this->panelHandler->notFound($response);
                }
            }
        }

        $response->end();
    }

    /**
     * Handle heartbeat.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function heartbeat(Response $response): Response
    {
        $this->app->heartbeat();

        // Respond nothing to Mirai API HTTP
        return $this->responseFactory->createEmpty($response);
    }

    /**
     * Handle report.
     *
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function report(string $content, Response $response): Response
    {
        $this->app->report($content);

        // Respond nothing to Mirai API HTTP
        return $this->responseFactory->createEmpty($response);
    }
}
