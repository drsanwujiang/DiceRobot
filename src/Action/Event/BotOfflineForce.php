<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOfflineEventForce;
use DiceRobot\Enum\AppStatusEnum;

/**
 * Class BotOfflineForce
 *
 * Action that responds to BotOfflineEventForce.
 *
 * Hold application (HOLDING).
 *
 * @event BotOfflineEventForce
 *
 * @package DiceRobot\Action\Event
 */
class BotOfflineForce extends EventAction
{
    /**
     * @var BotOfflineEventForce $event Event
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->warning("Robot is offline (forced).");

        $this->app->setStatus(AppStatusEnum::HOLDING());
    }
}
