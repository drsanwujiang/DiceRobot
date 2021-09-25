<?php /** @noinspection PhpUndefinedMethodInspection */

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
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $this->server = new SwooleServer($host, $port);
        } catch (Exception $e) {
            $this->logger->emergency(
                "Create server failed. Code {$e->getCode()}, message: {$e->getMessage()}."
            );

            $this->emergencyStop();

            return false;
        }

        $this->server->set([
            "http_parse_post" => false,
            "http_parse_cookie" => false
        ]);

        $this->panelHandler->initialize();

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

        // Signal SIGUSR1 (stop), stop the application
        Process::signal(SIGUSR1, [$this, "signalStop"]);

        // Signal SIGUSR2 (reload), reload the application
        Process::signal(SIGUSR2, [$this, "signalReload"]);

        // Signal SIGTSTP (restart), restart the application
        Process::signal(SIGTSTP, [$this, "signalRestart"]);
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
        // Server must be set, and application must be successfully initialized
        if (isset($this->server) && $this->app->getStatus()->equals(AppStatusEnum::HOLDING())) {
            $this->logger->notice("Server started.");

            try {
                $this->server->start();
            } catch (Exception $e) {
                // Unexpected exit
                $this->logger->emergency("Server exited unexpectedly. Code {$e->getCode()}, message: {$e->getMessage()}.");

                $this->emergencyStop();
            }
        } else {
            $this->logger->alert("Start server failed.");
        }
    }

    /**
     * Stop HTTP server and event loop.
     */
    public function stop(): void
    {
        $this->logger->info("Stopping server...");

        $this->server->shutdown();

        $this->logger->info("Server stopped.");
    }

    /**
     * Emergency stop.
     */
    protected function emergencyStop(): void
    {
        $this->logger->info("Stopping application...");

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
        $this->logger->notice("Server received Linux signal {$signal}, exit.");

        if (!$this->app->getStatus()->equals(AppStatusEnum::STOPPED())) {
            $this->logger->info("Stopping application...");

            $this->app->stop();
        }

        $this->stop();
    }

    /**
     * Stop application and server with exit code.
     *
     * @param int $signal Linux signal.
     */
    public function signalRestart(int $signal): void
    {
        $this->logger->notice("Server received Linux signal {$signal}, restart.");

        if (!$this->app->getStatus()->equals(AppStatusEnum::STOPPED())) {
            $this->logger->info("Stopping application...");

            $this->app->stop();
        }

        $this->stop();

        // Exit with code 99
        global $dicerobot_exit_code;
        $dicerobot_exit_code = 99;
    }

    /**
     * Route request.
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     */
    public function route(Request $request, Response $response): void
    {
        $header = $request->header;
        $method = $request->server["request_method"] ?? "";
        $uri = $request->server["request_uri"] ?? "";
        $queryParams = $request->get ?? [];
        $content = (string) ($request->getContent() ?? "");

        if ($uri == "/report" && $method == "POST") {
            // Handle report
            $this->report($content, $response);
        } elseif ($this->panelHandler->hasApi($uri)) {
            // Handle panel request
            $this->panelHandler->handle($header, $method, $uri, $queryParams, $content, $response);
        } else {
            $this->responseFactory->createNotFound($response);
        }

        if ($response->isWritable()) {
            $response->end();
        }
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
