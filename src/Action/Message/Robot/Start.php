<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Robot;

use DiceRobot\Action\Message\RobotAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Start
 *
 * Set robot active.
 *
 * @order robot start
 *
 *      Sample: @Robot .robot start
 *              .robot start 12345678
 *              .robot start 5678
 *
 * @package DiceRobot\Action\Message\RobotOrder
 */
class Start extends RobotAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        list($targetId) = $this->parseOrder();

        if (!$this->checkId($targetId) || !$this->checkPermission()) {
            return;
        }

        $this->chatSettings->set("active", true);

        $this->setReply("robotStart");
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException Order is invalid.
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^(\d{4,})?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $targetId = empty($matches[1]) ? null : $matches[1];

        /**
         * @var string|null $targetId Targeted robot ID.
         */
        return [$targetId];
    }

    /**
     * Check the permission of message sender.
     *
     * @return bool Permitted.
     */
    protected function checkPermission(): bool
    {
        // Must be the owner or the administrator in the group
        if ($this->message instanceof GroupMessage && $this->message->sender->permission == "MEMBER") {
            $this->setReply("robotStartDenied");

            return false;
        }

        return true;
    }
}
