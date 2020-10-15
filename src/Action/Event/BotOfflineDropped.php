<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOfflineEventDropped;
use DiceRobot\Enum\AppStatusEnum;

/**
 * Class BotOfflineDropped
 *
 * Action that responds to BotOfflineEventDropped.
 *
 * Hold application (HOLDING).
 *
 * @event BotOfflineEventDropped
 *
 * @package DiceRobot\Action\Event
 */
class BotOfflineDropped extends EventAction
{
    /**
     * @var BotOfflineEventDropped $event Event
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->notice("Robot is offline (dropped).");

        $this->app->setStatus(AppStatusEnum::HOLDING());
    }
}
