<?php

declare(strict_types=1);

namespace DiceRobot\Action;

use DiceRobot\Data\Report\Message\GroupMessage;

/**
 * Class RobotOrderAction
 *
 * Action of robot order.
 *
 * @package DiceRobot\Action
 */
abstract class RobotOrderAction extends MessageAction
{
    /**
     * @inheritDoc
     */
    abstract public function __invoke(): void;

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     */
    abstract protected function parseOrder(): array;

    /**
     * Check the targeted robot ID.
     *
     * @param string|null $targetId Targeted robot ID.
     *
     * @return bool Validity.
     */
    protected function checkId(?string $targetId): bool
    {
        $robotId = (string) $this->robot->getId();

        if ($this->message instanceof GroupMessage) {
            // Must at robot if no robot ID
            if (is_null($targetId))
                return $this->at;
            // Must be the full QQ ID or the last 4 digital number
            else
                return $targetId == $robotId || $targetId == substr($robotId, -4);
        } else {
            if (is_null($targetId) || $targetId == $robotId || $targetId == substr($robotId, -4))
                return true;

            return false;
        }
    }
}
