<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOfflineEventActive;
use DiceRobot\Enum\AppStatusEnum;

/**
 * Class BotOfflineActive
 *
 * Action that responds to BotOfflineEventActive.
 *
 * Hold application (HOLDING).
 *
 * @event BotOfflineEventActive
 *
 * @package DiceRobot\Action\Event
 */
class BotOfflineActive extends EventAction
{
    /**
     * @var BotOfflineEventActive $event Event.
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->warning("Robot is offline (active).");

        $this->app->setStatus(AppStatusEnum::HOLDING());
    }
}
