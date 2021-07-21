<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use Cake\Chronos\Chronos;
use Co\System;
use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\Message;
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage};
use DiceRobot\Data\Resource\ChatSettings;
use DiceRobot\Exception\DiceRobotException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use DiceRobot\Util\Convertor;
use Psr\Log\LoggerInterface;

/**
 * Class LogHandler
 *
 * TRPG log handler.
 *
 * @package DiceRobot\Handlers
 */
class LogHandler
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

        $this->logger = $loggerFactory->create("Handler");

        $this->logger->debug("Log handler created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Log handler destructed.");
    }

    /**
     * Handle TRPG log operation.
     *
     * @param Message $message Message.
     * @param MessageAction|null $action Message action.
     * @param DiceRobotException|null $exception Exception.
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function handle(
        Message $message,
        MessageAction $action = null,
        DiceRobotException $exception = null
    ): void {
        $chatSettings = $this->getChatSettings($message);

        if (empty($uuid = $chatSettings->getString("logUuid")) || !$chatSettings->getBool("isLogging")) {
            return;
        }

        if ($message && $action && $exception) {
            $log = $this->getLogItem(null, null, $action->getRobotNickname());
            $log["messageChain"] = Convertor::toMessageChain($this->config->getErrMsg((string) $exception));

            // Avoid empty log
            if (!empty($log["messageChain"])) {
                $this->updateLog($uuid, $log);
            }
        } elseif ($message && $action) {
            $log = $this->getLogItem(null, null, $action->getRobotNickname());

            foreach ($action->getReplies() as $reply) {
                $log["messageChain"] = Convertor::toMessageChain($reply);

                // Avoid empty log
                if (!empty($log["messageChain"])) {
                    $this->updateLog($uuid, $log);

                    // Sleep 0.5s
                    System::sleep(0.5);
                }
            }
        } else {
            $log = $this->getLogItem(
                $message->source->time,
                $message->sender->id,
                $message instanceof GroupMessage ? $message->sender->memberName : $message->sender->nickname
            );

            foreach ($message->fragments as $fragment) {
                $log["messageChain"][] = $fragment->toMessage();
            }

            // Avoid empty log
            if (!empty($log["messageChain"])) {
                $this->updateLog($uuid, $log);
            }
        }
    }

    /**
     * Get chat settings from message.
     *
     * @param Message $message Message.
     *
     * @return ChatSettings Chat settings.
     */
    protected function getChatSettings(Message $message): ChatSettings
    {
        if ($message instanceof FriendMessage) {
            $chatType = "friend";
            $chatId = $message->sender->id;
        } elseif ($message instanceof GroupMessage) {
            $chatType = "group";
            $chatId = $message->sender->group->id;
        } else {
            $chatType = "temp";
            $chatId = 0;
        }

        return $this->resource->getChatSettings($chatType, $chatId);
    }

    /**
     * Get TRPG log item.
     *
     * @param int|null $time Timestamp.
     * @param int|null $id Message sender ID.
     * @param string $nickname Message sender nickname.
     *
     * @return array Log item.
     */
    protected function getLogItem(?int $time, ?int $id, string $nickname): array
    {
        return [
            "time" => $time ?? Chronos::now()->timestamp,
            "sender" => [
                "id" => $id ?? $this->robot->getId(),
                "nickname" => $nickname
            ],
            "messageChain" => []
        ];
    }

    /**
     * Request to update TRPG log.
     *
     * @param string $uuid Log UUID.
     * @param array $message Log message.
     */
    protected function updateLog(string $uuid, array $message): void
    {
        $this->api->updateLogAsync($uuid, $message);
    }
}
