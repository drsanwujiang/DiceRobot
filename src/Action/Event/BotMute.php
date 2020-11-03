<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotMuteEvent;
use DiceRobot\Exception\MiraiApiException;

/**
 * Class BotMute
 *
 * Action that responds to BotMuteEvent.
 *
 * Quit the group.
 *
 * @event BotMuteEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotMute extends EventAction
{
    /**
     * @var BotMuteEvent $event Event
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     *
     * @throws MiraiApiException
     */
    public function __invoke(): void
    {
        if (!$this->checkListen()) {
            return;
        }

        if ($this->checkQuitWhenMuted()) {
            // Quit the group
            $this->api->quitGroup($this->event->operator->group->id);
        }
    }

    /**
     * @inheritDoc
     *
     * @return bool Listened
     */
    protected function checkListen(): bool
    {
        return $this->config->getBool("strategy.listenBotMuteEvent");
    }

    /**
     * Check whether the robot should quit the group when muted.
     *
     * @return bool
     */
    protected function checkQuitWhenMuted(): bool
    {
        return $this->config->getBool("strategy.quitGroupWhenMuted");
    }
}
