<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotJoinGroupEvent;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Util\Convertor;

/**
 * Class BotJoinGroup
 *
 * Action that responds to BotJoinGroupEvent.
 *
 * Send greetings according to the template when the group is normal, or send message and quit when the group
 * is delinquent.
 *
 * @event BotJoinGroupEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotJoinGroup extends EventAction
{
    /**
     * @var BotJoinGroupEvent $event Event
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     *
     * @throws InternalErrorException|LostException|MiraiApiException|NetworkErrorException|UnexpectedErrorException
     */
    public function __invoke(): void
    {
        if (!$this->checkListen()) {
            return;
        }

        if ($this->queryGroup() && $this->checkQuitWhenDelinquent()) {
            // Group is in black list, quit
            $this->api->sendGroupMessage(
                $this->event->group->id,
                Convertor::toMessageChain($this->config->getString("reply.botJoinGroupRejected"))
            );
            $this->api->quitGroup($this->event->group->id);
        } elseif ($this->checkSendHelloMessage()) {
            // Send hello message
            $message =
                Convertor::toCustomString(
                    $this->resource->getReference("HelloTemplate")->getString("templates.detail"),
                    [
                        "机器人昵称" => $this->robot->getNickname(),
                        "机器人QQ号" => $this->robot->getId(),
                    ]
                );

            $this->api->sendGroupMessage(
                $this->event->group->id,
                Convertor::toMessageChain($message)
            );
        }
    }

    /**
     * @inheritDoc
     *
     * @return bool Listened
     */
    protected function checkListen(): bool
    {
        return $this->config->getBool("strategy.listenBotJoinGroupEvent");
    }

    /**
     * Check whether the robot should quit the group when it is delinquent.
     *
     * @return bool
     */
    protected function checkQuitWhenDelinquent(): bool
    {
        return $this->config->getBool("strategy.quitDelinquentGroup");
    }

    /**
     * Check whether the robot should send hello message when joining a group.
     *
     * @return bool
     */
    protected function checkSendHelloMessage(): bool
    {
        return $this->config->getBool("strategy.sendHelloMessage");
    }

    /**
     * Query if this group is delinquent.
     *
     * @return bool Delinquent
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    protected function queryGroup(): bool
    {
        return $this->api->queryGroup(
            $this->event->group->id,
            $this->api->auth($this->robot->getId())->token
        )->state;
    }
}
