<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Report\Message\GroupMessage;

/**
 * Class RobotAction
 *
 * Action of robot control.
 *
 * @package DiceRobot\Action
 */
abstract class RobotAction extends MessageAction
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
            if (is_null($targetId)) {
                // Must at robot if no robot ID
                return $this->at;
            } else {
                // Must be the full QQ ID or the last 4 digital number
                return $targetId == $robotId || $targetId == substr($robotId, -4);
            }
        } else {
            if (is_null($targetId) || $targetId == $robotId || $targetId == substr($robotId, -4)) {
                return true;
            }

            // Will not go on in private chat
            return false;
        }
    }
}
