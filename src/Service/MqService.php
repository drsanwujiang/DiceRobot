<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Config;
use DiceRobot\Exception\DiceRobotException;
use DiceRobot\Factory\LoggerFactory;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\System;
use Swoole\Coroutine\WaitGroup;

/**
 * Class MqService
 *
 * Message queue service.
 *
 * @package DiceRobot\Service
 */
class MqService
{
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** @var MqttClient|null MQTT client. */
    protected ?MqttClient $client = null;

    /** @var WaitGroup Coroutine wait group. */
    protected WaitGroup $wg;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        Config $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory
    ) {
        $this->config = $config;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;

        $this->logger = $loggerFactory->create("MQ");

        $this->logger->debug("MQ service created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("MQ service destructed.");
    }

    /**
     * Initialize service.
     */
    public function initialize(): void
    {
        $this->wg = new WaitGroup();

        $this->logger->info("MQ service initialized.");
    }

    /**
     * Enable message queue.
     *
     * @return bool
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function enable(): bool {
        if ($this->client && $this->client->isConnected()) {
            return true;
        }

        // Try up to three times
        for ($retry = 0; $retry <= 2; $retry++) {
            if ($retry > 0) {
                System::sleep(10);  // Wait for 10 seconds

                $this->logger->notice("Retry to enable message queue.");
            }

            try {
                $response = $this->api->getMqCredential(
                    $this->robot->getId(),
                    $this->api->getToken($this->robot->getId())->token
                );

                break;
            } catch (DiceRobotException $e) {
                $this->logger->error("Failed to enable message queue, unable to call DiceRobot API.");
            }
        }

        if ($retry > 2) {
            return false;
        }

        $this->client = new MqttClient(
            $this->config->getString("dicerobot.mq.server"),
            $this->config->getInt("dicerobot.mq.port"),
            $response->clientId
        );

        $connectionSettings = (new ConnectionSettings)
            ->setUsername($response->credentialId)
            ->setPassword($response->credentialSecret)
            ->setConnectTimeout(5);

        if ($this->connect($connectionSettings)) {
            $this->loop();

            $this->logger->notice("Message queue enabled.");

            return true;
        } else {
            $this->disconnect();

            $this->logger->error("Failed to enable message queue, unable to communicate with DiceRobot MQ.");

            return false;
        }
    }

    /**
     * Disable message queue.
     *
     * @param bool $logError Whether error should be logged.
     */
    public function disable(bool $logError = true): void
    {
        if (is_null($this->client)) {
            return;
        }

        $this->client->interrupt();
        $this->wg->wait();  // Wait for disconnection

        if ($logError) {
            $this->logger->warning("Message queue disabled.");
        }
    }

    /**
     * Connect to DiceRobot MQ.
     *
     * @param ConnectionSettings $connectionSettings Connection settings.
     *
     * @return bool
     */
    private function connect(ConnectionSettings $connectionSettings): bool
    {
        try {
            $this->client->connect($connectionSettings, true);
            $this->logger->info("Connected to DiceRobot MQ.");

            $this->client->subscribe("card", [$this, "handleCard"], 0);
            $this->logger->info("Topic subscribed.");
        } catch (MqttClientException $e) {
            return false;
        }

        return true;
    }

    /**
     * Start MQTT loop.
     */
    private function loop(): void
    {
        $this->wg->add();

        go(function () {
            try {
                $this->client->loop();
            } catch (MqttClientException $e) {
                $this->logger->error("Failed to communicate with DiceRobot MQ.");
            }

            $this->disconnect();

            $this->wg->done();
        });
    }

    /**
     * Disconnect to DiceRobot MQ.
     */
    private function disconnect(): void
    {
        try {
            $this->client->disconnect();
        } catch (MqttClientException $e) {
            $this->logger->error("Failed to disconnect to DiceRobot MQ.");
        }

        $this->client = null;
    }

    /**
     * Handle message from card chanel.
     *
     * @param string $topic
     * @param string $message
     */
    private function handleCard(string $topic, string $message)
    {
        $this->logger->debug("Message on topic {$topic} received: {$message}");
    }
}
