<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\App;
use DiceRobot\Data\{Config, CustomConfig};
use DiceRobot\Factory\{LoggerFactory, ResponseFactory};
use DiceRobot\Service\{LogService, ResourceService, RobotService, StatisticsService};
use DiceRobot\Util\{Environment, Jwt, Updater};
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
    /** @var string[] Panel APIs. */
    private const PANEL_APIS = [
        "GET" => [
            "/pause", "/run", "/reload", "/stop", "/restart", "/update",
            "/profile", "/status", "/statistics",
            "/config", "/logs", "/log", "/references", "/reference", "/decks", "/deck", "/rules", "/rule",
            "/mirai/status", "/mirai/start", "/mirai/stop", "/mirai/restart"
        ],
        "POST" => [
            "/connect",
            "/skeleton/update",
            "/mirai/update"
        ],
        "PATCH" => [
            "/config", "/reference", "/deck", "/rule"
        ],
        "PUT" => [
            "/deck", "/rule"
        ],
        "DELETE" => [
            "/deck", "/rule"
        ]
    ];

    /** @var string[] Panel APIs. */
    private static array $panelApis;

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

    /** @var Jwt JSON Web Token util. */
    protected Jwt $jwt;

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
     * @param Jwt $jwt JSON Web Token util.
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
        Jwt $jwt,
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
        $this->jwt = $jwt;

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
     * Initialize handler.
     */
    public function initialize(): void
    {
        self::$panelApis = array_unique(array_merge(
            self::PANEL_APIS["GET"], self::PANEL_APIS["POST"], self::PANEL_APIS["PATCH"],
            self::PANEL_APIS["PUT"], self::PANEL_APIS["DELETE"]
        ));



        $this->logger->info("Panel handler initialized.");
    }

    /**
     * Test whether the API exists.
     *
     * @param string $uri The API URI.
     *
     * @return bool Existence.
     */
    public function hasApi(string $uri): bool
    {
        return in_array($uri, self::$panelApis);
    }

    /**
     * Handle panel request.
     *
     * @param array $headers Request headers.
     * @param string $method Request method.
     * @param string $uri Request URL.
     * @param string[] $queryParams Query parameters.
     * @param string $content Request content.
     * @param Response $response Swoole response.
     *
     * @return Response Swoole response.
     */
    public function handle(
        array $headers,
        string $method,
        string $uri,
        array $queryParams,
        string $content,
        Response $response
    ): Response {
        try {
            $this->route($headers, $method, $uri, $queryParams, $content, $response);
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

            $this->responseFactory->createInternalServerError(null, null, $response);
        }

        return $response;
    }

    /**
     * Route request.
     *
     * @param array $headers Request headers.
     * @param string $method Request method.
     * @param string $uri Request URL.
     * @param string[] $queryParams Query parameters.
     * @param string $content Request content.
     * @param Response $response Swoole response.
     *
     * @return Response Swoole response.
     */
    protected function route(
        array $headers,
        string $method,
        string $uri,
        array $queryParams,
        string $content,
        Response $response
    ): Response {
        if ($method == "OPTIONS") {
            return $this->responseFactory->createPreflight($response);
        }

        // Check header
        if (!preg_match("/^DiceRobot Panel\/[1-9]\.[0-9]\.[0-9]$/", $headers["x-dr-panel"] ?? "")) {
            return $this->responseFactory->createForbidden(null, null, $response);
        }

        // Check method
        if (!in_array($uri, self::PANEL_APIS[$method] ?? [])) {
            return $this->responseFactory->createMethodNotAllowed(null, null, $response);
        }

        if ($method == "POST" && $uri == "/connect") {
            return $this->connect($content, $response);
        }

        // Check authorization
        if (empty($auth = $headers["authorization"] ?? "") ||
            !preg_match("/Bearer\s(\S+)/", $auth, $matches) ||
            !$this->jwt->validate($matches[1])
        ) {
            return $this->responseFactory->createUnauthorized(null, null, $response);
        }

        if ($method == "GET") {
            if ($uri == "/pause") {
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
                $this->getConfig($queryParams, $response);
            } elseif ($uri == "/logs") {
                $this->getLogList($response);
            } elseif ($uri == "/log") {
                $this->getLog($queryParams, $response);
            } elseif ($uri == "/references") {
                $this->getReferenceList($response);
            } elseif ($uri == "/reference") {
                $this->getReference($queryParams, $response);
            } elseif ($uri == "/decks") {
                $this->getDeckList($response);
            } elseif ($uri == "/deck") {
                $this->getDeck($queryParams, $response);
            } elseif ($uri == "/rules") {
                $this->getRuleList($response);
            } elseif ($uri == "/rule") {
                $this->getRule($queryParams, $response);
            } elseif ($uri == "/mirai/status") {
                $this->getMiraiStatus($response);
            } elseif ($uri == "/mirai/start") {
                $this->startMirai($response);
            } elseif ($uri == "/mirai/stop") {
                $this->stopMirai($response);
            } elseif ($uri == "/mirai/restart") {
                $this->restartMirai($response);
            }

            return $response;
        }

        // Check content type
        if (($headers["content-type"] ?? "") != "application/json") {
            return $this->responseFactory->createBadRequest(null, null, $response);
        }

        if ($method == "POST") {
            if ($uri == "/skeleton/update") {
                $this->updateSkeleton($content, $response);
            } elseif ($uri == "/mirai/update") {
                $this->updateMirai($content, $response);
            }

            return $response;
        }

        if ($method == "PATCH") {
            if ($uri == "/config") {
                $this->setConfig($content, $response);
            } elseif ($uri == "/reference") {
                $this->setReference($queryParams, $content, $response);
            } elseif ($uri == "/deck") {
                $this->setDeck($queryParams, $content, $response);
            } elseif ($uri == "/rule") {
                $this->setRule($queryParams, $content, $response);
            }

            return $response;
        }

        if ($method == "PUT") {
            if ($uri == "/deck") {
                $this->addDeck($queryParams, $content, $response);
            } elseif ($uri == "/rule") {
                $this->addRule($queryParams, $content, $response);
            }

            return $response;
        }

        if ($method == "DELETE") {
            if ($uri == "/deck") {
                $this->deleteDeck($queryParams, $response);
            } elseif ($uri == "/rule") {
                $this->deleteRule($queryParams, $response);
            }

            return $response;
        }

        return $this->responseFactory->createBadRequest(null, null, $response);
    }

    /**
     * Connect.
     *
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function connect(string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, connect to application.");

        if (!is_array($data = json_decode($content, true))) {
            return $this->responseFactory->createBadRequest(null, null, $response);
        }

        if (!isset($data["password"]) || $data["password"] != $this->config->getString("panel.password")) {
            return $this->responseFactory->createForbidden(-900, null, $response);
        }

        return $this->responseFactory->create(0, ["token" => $this->jwt->generate()], $response);
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

        Process::kill(getmypid(), SIGUSR1);  // Send SIGUSR1
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
            Environment::getSystemctl() . " status {$this->config->getString("dicerobot.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->responseFactory->create(0, null, $response)->end();

            Process::kill(getmypid(), SIGTSTP);  // Send SIGTSTP
        } else {
            $this->responseFactory->create(1040, null, $response);
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

        if (is_null(Environment::getComposer())) {
            return $this->responseFactory->create(-910, null, $response);
        }

        if (!is_dir($root = $this->config->getString("root"))) {
            return $this->responseFactory->create(-1050, null, $response);
        }

        $code = $signal = -1;
        $output = "";

        extract(System::exec(
            Environment::getComposer() . " --no-interaction --no-ansi --quiet update --working-dir {$root} --no-dev 2>&1"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->logger->notice(
                "DiceRobot updated."
            );

            return $this->responseFactory->create($code, null, $response);
        } else {
            $this->logger->critical(
                "Failed to update DiceRobot. Code {$code}, signal {$signal}, output message: {$output}"
            );

            return $this->responseFactory->create(
                -1051,
                ["code" => $code, "signal" => $signal, "output" => $output],
                $response
            );
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
            Environment::getSystemctl() . " status {$this->config->getString("dicerobot.service.name")}"
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
     * @param array $params Query parameters.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getConfig(array $params, Response $response): Response
    {
        $this->logger->info("HTTP request received, get config.");

        $acceptableGroups = ["panel", "strategy", "order", "reply", "errMsg"];
        $groups = explode(",", $params["groups"] ?? "");
        $data = [];

        foreach ($groups as $group) {
            if (in_array($group, $acceptableGroups)) {
                $data[$group] = $this->config->getArray($group);
            } else {
                return $this->responseFactory->createBadRequest(-901, null, $response);
            }
        }

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
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function setConfig(string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, set config.");

        if (!is_array($data = json_decode($content, true)) || empty($data)) {
            return $this->responseFactory->createBadRequest(-1060, null, $response);
        }

        if (!$this->resource->getConfig()->setConfig($data)) {
            return $this->responseFactory->createBadRequest(-1061, null, $response);
        }

        $this->config->load($this->container->make(CustomConfig::class), $this->resource->getConfig());

        $this->logger->notice("Config set via panel.");

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
     * Get reference file list.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getReferenceList(Response $response): Response
    {
        $this->logger->info("HTTP request received, get reference list.");

        $references = $this->resource->getReferenceList();

        return $this->responseFactory->create(0, ["references" => $references], $response);
    }

    /**
     * Get parsed reference file content.
     *
     * @param array $params Query parameters.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getReference(array $params, Response $response): Response
    {
        $this->logger->info("HTTP request received, get reference.");

        if (false === $reference = $this->resource->getReferenceContent($params["name"] ?? "")) {
            return $this->responseFactory->create(-1090, null, $response);
        }

        return $this->responseFactory->create(0, ["reference" => $reference], $response);
    }

    /**
     * Set reference file content.
     *
     * @param array $params Query parameters.
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function setReference(array $params, string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, set reference.");

        $filename = $params["name"] ?? "";

        if (empty($filename) || !is_array($data = json_decode($content, true)) || empty($data)) {
            return $this->responseFactory->createBadRequest(-1120, null, $response);
        }

        if (!$this->resource->setReferenceContent($filename, $data)) {
            return $this->responseFactory->create(-1121, null, $response);
        }

        $this->logger->notice("Reference {$filename} set via panel.");

        $this->resource->reload();

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Get card deck file list.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getDeckList(Response $response): Response
    {
        $this->logger->info("HTTP request received, get card deck list.");

        $decks = $this->resource->getCardDeckList();

        return $this->responseFactory->create(0, ["decks" => $decks], $response);
    }

    /**
     * Get parsed card deck file content.
     *
     * @param array $params Query parameters.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getDeck(array $params, Response $response): Response
    {
        $this->logger->info("HTTP request received, get card deck.");

        if (false === $deck = $this->resource->getCardDeckContent($params["name"] ?? "")) {
            return $this->responseFactory->create(-1100, null, $response);
        }

        return $this->responseFactory->create(0, ["deck" => $deck], $response);
    }

    /**
     * Set card deck file content.
     *
     * @param array $params Query parameters.
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function setDeck(array $params, string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, set card deck.");

        $filename = $params["name"] ?? "";

        if (empty($filename) || !is_array($data = json_decode($content, true)) || empty($data)) {
            return $this->responseFactory->createBadRequest(-1130, null, $response);
        }

        if (!$this->resource->setCardDeckContent($filename, $data)) {
            return $this->responseFactory->create(-1131, null, $response);
        }

        $this->logger->notice("Card deck {$filename} set via panel.");

        $this->resource->reload();

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Create card deck file.
     *
     * @param array $params Query parameters.
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function addDeck(array $params, string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, add card deck.");

        $filename = $params["name"] ?? "";

        if (empty($filename) || !is_array($data = json_decode($content, true)) || empty($data)) {
            return $this->responseFactory->createBadRequest(-1150, null, $response);
        }

        if (false !== $this->resource->getCardDeckContent($filename)) {
            return $this->responseFactory->create(-1151, null, $response);
        }

        if (!$this->resource->setCardDeckContent($filename, $data)) {
            return $this->responseFactory->create(-1152, null, $response);
        }

        $this->logger->notice("Card deck {$filename} added via panel.");

        $this->resource->reload();

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Delete card deck file.
     *
     * @param array $params Query parameters.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function deleteDeck(array $params, Response $response): Response
    {
        $this->logger->info("HTTP request received, delete card deck.");

        $filename = $params["name"] ?? "";

        if (empty($filename)) {
            return $this->responseFactory->createBadRequest(-1170, null, $response);
        }

        if (!$this->resource->deleteCardDeck($filename)) {
            return $this->responseFactory->create(-1171, null, $response);
        }

        $this->logger->notice("Card deck {$filename} deleted via panel.");

        $this->resource->reload();

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Get check rule file list.
     *
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getRuleList(Response $response): Response
    {
        $this->logger->info("HTTP request received, get check rule list.");

        $rules = $this->resource->getCheckRuleList();

        return $this->responseFactory->create(0, ["rules" => $rules], $response);
    }

    /**
     * Get parsed check rule file content.
     *
     * @param array $params Query parameters.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function getRule(array $params, Response $response): Response
    {
        $this->logger->info("HTTP request received, get check rule.");

        if (false === $rule = $this->resource->getCheckRuleContent($params["name"] ?? "")) {
            return $this->responseFactory->create(-1110, null, $response);
        }

        return $this->responseFactory->create(0, ["rule" => $rule], $response);
    }

    /**
     * Set check rule file content.
     *
     * @param array $params Query parameters.
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function setRule(array $params, string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, set check rule.");

        $filename = $params["name"] ?? "";

        if (empty($filename) || !is_array($data = json_decode($content, true)) || empty($data)) {
            return $this->responseFactory->createBadRequest(-1140, null, $response);
        }

        if (!$this->resource->setCheckRuleContent($filename, $data)) {
            return $this->responseFactory->create(-1141, null, $response);
        }

        $this->logger->notice("Check rule {$filename} set via panel.");

        $this->resource->reload();

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Create check rule file.
     *
     * @param array $params Query parameters.
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function addRule(array $params, string $content, Response $response): Response
    {
        $this->logger->info("HTTP request received, add check rule.");

        $filename = $params["name"] ?? "";

        if (empty($filename) || !is_array($data = json_decode($content, true)) || empty($data)) {
            return $this->responseFactory->createBadRequest(-1160, null, $response);
        }

        if (false !== $this->resource->getCheckRuleContent($filename)) {
            return $this->responseFactory->create(-1161, null, $response);
        }

        if (!$this->resource->setCheckRuleContent($filename, $data)) {
            return $this->responseFactory->create(-1162, null, $response);
        }

        $this->logger->notice("Check rule {$filename} added via panel.");

        $this->resource->reload();

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Delete check rule file.
     *
     * @param array $params Query parameters.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     */
    protected function deleteRule(array $params, Response $response): Response
    {
        $this->logger->info("HTTP request received, delete check rule.");

        $filename = $params["name"] ?? "";

        if (empty($filename)) {
            return $this->responseFactory->createBadRequest(-1180, null, $response);
        }

        if (!$this->resource->deleteCheckRule($filename)) {
            return $this->responseFactory->create(-1181, null, $response);
        }

        $this->logger->notice("Check rule {$filename} deleted via panel.");

        $this->resource->reload();

        return $this->responseFactory->create(0, null, $response);
    }

    /**
     * Update DiceRobot skeleton.
     *
     * @param string $content HTTP request content.
     * @param Response $response HTTP response.
     *
     * @return Response HTTP response.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function updateSkeleton(string $content, Response $response): Response
    {
        $this->logger->notice("HTTP request received, update skeleton.");

        if (!is_array($files = json_decode($content, true))) {
            return $this->responseFactory->createBadRequest(-1070, null, $response);
        }

        /** @var Updater $updater */
        $updater = $this->container->make(Updater::class);
        $code = $updater->update($files);

        if ($code == -1) {
            $code = -1071;
        } elseif ($code == -2) {
            $code = -1072;
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
            Environment::getSystemctl() . " status {$this->config->getString("mirai.service.name")}"
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
            Environment::getSystemctl() . " start {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->logger->notice("Mirai started.");

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
            Environment::getSystemctl() . " stop {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->logger->notice("Mirai stopped.");

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
            Environment::getSystemctl() . " restart {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->logger->notice("Mirai restarted.");

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
            return $this->responseFactory->createBadRequest(-2030, null, $response);
        }

        foreach ($params as $param) {
            if (!preg_match("/^[1-9]\.[0-9]\.[0-9]$/", $param)) {
                return $this->responseFactory->createBadRequest(-2031, null, $response);
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
            $this->logger->notice("Mirai updated.");

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
