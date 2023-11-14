<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Robot;

use DiceRobot\Action\Message\RobotAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Goodbye
 *
 * Quit the group.
 *
 * @order robot goodbye
 *
 *      Sample: @Robot .robot goodbye
 *              .robot goodbye 12345678
 *              .robot goodbye 5678
 *
 * @package DiceRobot\Action\Message\RobotOrder
 */
class Goodbye extends RobotAction
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

        // Send goodbye message
        $this->sendMessage($this->config->getReply("robotGoodbye"));

        // Quit the group
        $this->api->quitGroup($this->message->sender->group->id);
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
     * @inheritDoc
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
                $this->setReply("robotGoodbyePrivate'");
            }

            // Will not go on in private chat
            return false;
        }
    }

    /**
     * Check the permission of message sender.
     *
     * @return bool Permitted.
     */
    protected function checkPermission(): bool
    {
        // Must be the owner or the administrator in the group
        if ($this->message->sender->permission == "MEMBER") {
            $this->setReply("robotGoodbyeDenied");

            return false;
        }

        return true;
    }
}
