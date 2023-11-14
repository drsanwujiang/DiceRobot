<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotJoinGroupEvent;
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Util\Convertor;

/**
 * Class BotJoinGroup
 *
 * Action that handles BotJoinGroupEvent.
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
    /** @var BotJoinGroupEvent $event Event. */
    public Event $event;

    /**
     * @inheritDoc
     *
     * @throws LostException
     */
    public function __invoke(): void
    {
        $this->logger->notice("Robot joined group {$this->event->group->id}.");

        // If group doesn't exist, update group list
        if (!$this->robot->hasGroup($this->event->group->id)) {
            $this->robot->updateGroups($this->api->getGroupList()->getArray("data"));
        }

        if (!$this->checkListen()) {
            return;
        }

        if (!$this->queryGroup()) {
            // Group is in black list
            $this->logger->warning("Robot joined delinquent group {$this->event->group->id}.");

            if ($this->checkQuitWhenDelinquent()) {
                // Quit group
                $this->api->sendGroupMessage(
                    $this->event->group->id,
                    Convertor::toMessageChain($this->config->getReply("botJoinGroupRejected"))
                );
                $this->api->quitGroup($this->event->group->id);

                $this->logger->notice("Robot quit group {$this->event->group->id}.");

                return;
            }
        }

        if ($this->checkSendHelloMessage()) {
            // Send hello message
            $message = Convertor::toCustomString(
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
     * @return bool Listen strategy.
     */
    protected function checkListen(): bool
    {
        return $this->config->getStrategy("listenBotJoinGroupEvent");
    }

    /**
     * Check whether the robot should quit the group when it is delinquent.
     *
     * @return bool Strategy.
     */
    protected function checkQuitWhenDelinquent(): bool
    {
        return $this->config->getStrategy("quitDelinquentGroup");
    }

    /**
     * Check whether the robot should send hello message when joining a group.
     *
     * @return bool Strategy.
     */
    protected function checkSendHelloMessage(): bool
    {
        return $this->config->getStrategy("sendHelloMessage");
    }

    /**
     * Request to query the group state, normal or delinquent.
     *
     * @return bool Group state, TRUE for normal and FALSE for delinquent.
     */
    protected function queryGroup(): bool
    {
        return $this->api->queryGroup(
            $this->event->group->id,
            $this->api->getToken($this->robot->getId())->token
        )->state;
    }
}
