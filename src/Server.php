<?php

declare(strict_types=1);

namespace DiceRobot;

use Co\Http\Server as SwooleServer;
use DiceRobot\Data\Config;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Factory\ResponseFactory;
use Psr\Log\LoggerInterface;
use Swoole\Process;
use Swoole\Coroutine\System;
use Swoole\Http\{Request, Response};

/**
 * Class Server
 *
 * DiceRobot HTTP server.
 *
 * @package DiceRobot
 */
class Server
{
    /** @var Config Config */
    protected Config $config;

    /** @var App Application */
    protected App $app;

    /** @var SwooleServer Swoole HTTP server */
    protected SwooleServer $server;

    /** @var ResponseFactory Response factory */
    protected ResponseFactory $responseFactory;

    /** @var LoggerInterface Logger */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param Config $config
     * @param App $app
     * @param ResponseFactory $responseFactory
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(
        Config $config,
        App $app,
        ResponseFactory $responseFactory,
        LoggerFactory $loggerFactory
    ) {
        $this->config = $config;
        $this->app = $app;
        $this->responseFactory = $responseFactory;
        $this->logger = $loggerFactory->create("Server");

        $this->setServer();
        $this->setSignals();
        $this->setRoutes();
    }

    /**
     * Set HTTP server.
     */
    protected function setServer(): void
    {
        $host = $this->config->getString("dicerobot.server.host");
        $port = $this->config->getInt("dicerobot.server.port");

        $this->server = new SwooleServer($host, $port);
        $this->server->set([
            "http_parse_post" => false,
            "http_parse_cookie" => false
        ]);
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
     * @param Request $request
     * @param Response $response
     */
    public function route(Request $request, Response $response): void
    {
        $method = $request->server["request_method"] ?? "";
        $uri = $request->server["request_uri"] ?? "";
        $content = (string) ($request->getContent() ?? "");

        // Mirai APIs
        if ($method == "POST") {
            if ($uri == "/report") {
                $this->report($content, $response);

                return;
            } elseif ($uri == "/heartbeat") {
                $this->heartbeat($response);

                return;
            }
        }

        // Check header
        if (!preg_match("/^DiceRobot Panel\/[1-9]\.[0-9]\.[0-9]$/", $request->header["x-dr-client"] ?? "")) {
            $this->notFound($response);

            return;
        }

        // Web APIs
        if ($method == "OPTIONS") {
            $this->preflight($response);
        } elseif ($method == "POST") {
            if ($uri == "/config") {
                $this->setConfig($content, $response);
            } else {
                $this->notFound($response);
            }
        } elseif ($method == "GET") {
            if ($uri == "/connect") {
                $this->connect($response);
            } elseif ($uri == "/profile") {
                $this->profile($response);
            } elseif ($uri == "/status") {
                $this->status($response);
            } elseif ($uri == "/statistics") {
                $this->statistics($response);
            } elseif ($uri == "/config") {
                $this->config($response);
            } elseif ($uri == "/pause") {
                $this->pause($response);
            } elseif ($uri == "/run") {
                $this->run($response);
            } elseif ($uri == "/reload") {
                $this->reload($response);
            } elseif ($uri == "/stop") {
                $this->stop($response);
            } elseif ($uri == "/restart") {
                $this->restart($response);
            } elseif ($uri == "/update") {
                $this->update($response);
            } elseif ($uri == "/mirai/status") {
                $this->miraiStatus($response);
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
    }

    /**
     * Start event loop.
     */
    public function start(): void
    {
        $this->logger->notice("Server started.");

        $this->server->start();
    }

    /******************************************************************************
     *                               Signal handlers                              *
     ******************************************************************************/

    /**
     * @param int $signal
     */
    public function signalReload(int $signal): void
    {
        $this->logger->notice("Server received Linux signal {$signal}, reload application.");

        $this->app->reload();
    }

    /**
     * @param int $signal
     */
    public function signalStop(int $signal): void
    {
        $this->logger->warning("Server received Linux signal {$signal}, stop application.");

        $this->app->stop();

        $this->logger->notice("Server exited.");

        $this->server->shutdown();
    }

    /******************************************************************************
     *                                  Web APIs                                  *
     ******************************************************************************/

    /**
     * @param Response $response
     */
    protected function preflight(Response $response): void
    {
        $this->responseFactory->createPreflight($response)->end();
    }

    /**
     * @param Response $response
     */
    protected function notFound(Response $response): void
    {
        $this->responseFactory->createNotFound($response)->end();
    }

    /**
     * @param Response $response
     */
    protected function connect(Response $response): void
    {
        $this->logger->info("Server received HTTP request, connect to application.");

        $this->responseFactory->create(0, null, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function profile(Response $response): void
    {
        $this->logger->info("Server received HTTP request, get robot profile.");

        $data = $this->app->profile();

        $this->responseFactory->create(0, $data, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function status(Response $response): void
    {
        $this->logger->info("Server received HTTP request, get application status.");

        list($appStatus) = $this->app->status();
        $code = -1;

        extract(System::exec("/bin/systemctl status dicerobot"), EXTR_OVERWRITE);

        if ($code == 4) {
            $status = -2;
        } elseif ($code == 3) {
            $status = -1;
        } elseif ($code == 0) {
            $status = 0;
        } else {
            $status = -3;
        }

        $this->responseFactory->create(0, ["app" => $appStatus, "service" => $status], $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function statistics(Response $response): void
    {
        $this->logger->info("Server received HTTP request, get statistics.");

        $data = $this->app->statistics();

        $this->responseFactory->create(0, $data, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function config(Response $response): void
    {
        $this->logger->info("Server received HTTP request, get config.");

        $data = $this->app->config();

        $this->responseFactory->create(0, $data, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function pause(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, pause application.");

        $code = $this->app->pause();

        $this->responseFactory->create($code, null, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function run(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, rerun application.");

        $code = $this->app->run();

        $this->responseFactory->create($code, null, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function reload(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, reload application.");

        $code = $this->app->reload();

        $this->responseFactory->create($code, null, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function stop(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, stop application.");

        $code = $this->app->stop();

        $this->responseFactory->create($code, null, $response)->end();

        $this->logger->notice("Server exited.");

        $this->server->shutdown();
    }

    /**
     * @param Response $response
     */
    protected function restart(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, restart application.");

        $code = -1;

        extract(System::exec("/bin/systemctl status dicerobot"), EXTR_OVERWRITE);

        if ($code != 0) {
            $this->responseFactory->create(1040, null, $response)->end();
        } else {
            $this->responseFactory->create(0, null, $response)->end();

            System::exec("/bin/systemctl restart dicerobot");
        }
    }

    /**
     * @param Response $response
     */
    protected function update(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, update DiceRobot.");

        if (!is_dir($root = $this->config->getString("root"))) {
            $this->responseFactory->create(-1050, null, $response)->end();

            return;
        }

        $code = $signal = -1;
        $output = "";

        extract(System::exec("/usr/local/bin/composer update --working-dir {$root} --no-ansi --no-interaction --quiet 2>&1"), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->responseFactory->create($code, null, $response)->end();
        } else {
            $this->logger->critical(
                "Failed to update DiceRobot. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 127) {
                $this->responseFactory->create(-1051, null, $response)->end();
            } else {
                $this->responseFactory->create(
                    -1052,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                )->end();
            }
        }
    }

    /**
     * @param string $content
     * @param Response $response
     */
    protected function setConfig(string $content, Response $response): void
    {
        $this->logger->info("Server received HTTP request, set config.");

        $code = $this->app->setConfig($content);

        $this->responseFactory->create($code, null, $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function miraiStatus(Response $response): void
    {
        $this->logger->info("Server received HTTP request, get Mirai status.");

        $code = -1;

        extract(System::exec("/bin/systemctl status mirai"), EXTR_OVERWRITE);

        if ($code == 4) {
            $status = -2;
        } elseif ($code == 3) {
            $status = -1;
        } elseif ($code == 0) {
            $status = 0;
        } else {
            $status = -3;
        }

        $this->responseFactory->create(0, ["status" => $status], $response)->end();
    }

    /**
     * @param Response $response
     */
    protected function startMirai(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, start Mirai.");

        $code = $signal = -1;
        $output = "";

        extract(System::exec("/bin/systemctl start mirai"), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->responseFactory->create($code, null, $response)->end();
        } else {
            $this->logger->critical(
                "Failed to start Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 5) {
                $this->responseFactory->create(-2000, null, $response)->end();
            } else {
                $this->responseFactory->create(
                    -2001,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                )->end();
            }
        }
    }

    /**
     * @param Response $response
     */
    protected function stopMirai(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, stop Mirai.");

        $code = $signal = -1;
        $output = "";

        extract(System::exec("/bin/systemctl stop mirai"), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->responseFactory->create($code, null, $response)->end();
        } else {
            $this->logger->critical(
                "Failed to stop Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 5) {
                $this->responseFactory->create(-2010, null, $response)->end();
            } else {
                $this->responseFactory->create(
                    -2011,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                )->end();
            }
        }
    }

    /**
     * @param Response $response
     */
    protected function restartMirai(Response $response): void
    {
        $this->logger->notice("Server received HTTP request, restart Mirai.");

        $code = $signal = -1;
        $output = "";

        extract(System::exec("/bin/systemctl restart mirai"), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->responseFactory->create($code, null, $response)->end();
        } else {
            $this->logger->critical(
                "Failed to restart Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );

            if ($code == 5) {
                $this->responseFactory->create(-2020, null, $response)->end();
            } else {
                $this->responseFactory->create(
                    -2021,
                    ["code" => $code, "signal" => $signal, "output" => $output],
                    $response
                )->end();
            }
        }
    }

    /******************************************************************************
     *                             Mirai API HTTP APIs                            *
     ******************************************************************************/

    /**
     * @param Response $response
     */
    protected function heartbeat(Response $response): void
    {
        $this->app->heartbeat();

        // Respond nothing to Mirai API HTTP
        $this->responseFactory->createEmpty($response)->end();
    }

    /**
     * @param string $content
     * @param Response $response
     */
    protected function report(string $content, Response $response): void
    {
        $this->app->report($content);

        // Respond nothing to Mirai API HTTP
        $this->responseFactory->createEmpty($response)->end();
    }
}