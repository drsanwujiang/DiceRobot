<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotReloginEvent;
use DiceRobot\Exception\MiraiApiException;

/**
 * Class BotRelogin
 *
 * Action that responds to BotReloginEvent.
 *
 * Initialize API service (auth a new session).
 *
 * @event BotReloginEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotRelogin extends BotOnline
{
    /**
     * @var BotReloginEvent $event Event.
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
        $this->logger->notice("Robot is online (relogin).");

        $this->init();
    }
}
