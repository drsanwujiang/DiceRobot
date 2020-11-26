<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\RobotOrder;

use DiceRobot\Action\RobotOrderAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\{MiraiApiException, OrderErrorException};

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
class Goodbye extends RobotOrderAction
{
    /**
     * @inheritDoc
     *
     * @throws MiraiApiException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($targetId) = $this->parseOrder();

        if (!$this->checkId($targetId) || !$this->checkPermission()) {
            return;
        }

        // Send goodbye message
        $this->sendMessage($this->config->getString("reply.robotOrderGoodbye"));

        // Quit the group
        $this->api->quitGroup($this->message->sender->group->id);
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^([0-9]{4,})?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $targetId = empty($matches[1]) ? null : $matches[1];

        /**
         * @var string|null $targetId Targeted robot ID
         */
        return [$targetId];
    }

    /**
     * @inheritDoc
     *
     * @param string|null $targetId Targeted robot ID
     *
     * @return bool Validity
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
            // Will not go on in private chat
            if (is_null($targetId) || $targetId == $robotId || $targetId == substr($robotId, -4))
                $this->reply = $this->config->getString("reply.robotOrderGoodbyePrivately'");

            return false;
        }
    }

    /**
     * Check the permission of message sender.
     *
     * @return bool Validity
     */
    protected function checkPermission(): bool
    {
        // Must be the owner or the administrator in the group
        if ($this->message->sender->permission == "MEMBER") {
            $this->reply = $this->config->getString("reply.robotOrderGoodbyeDenied");

            return false;
        }

        return true;
    }
}
