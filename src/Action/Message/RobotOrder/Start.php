<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\RobotOrder;

use DiceRobot\Action\RobotOrderAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\{MiraiApiException, OrderErrorException};
use DiceRobot\Util\Convertor;

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
class Start extends RobotOrderAction
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

        $this->chatSettings->set("active", true);

        $this->reply = Convertor::toCustomString(
            $this->config->getString("reply.robotOrderStart"),
            [
                "机器人昵称" => $this->getRobotNickname()
            ]
        );
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
     * Check the permission of message sender.
     *
     * @return bool Validity
     */
    protected function checkPermission(): bool
    {
        // Must be the owner or the administrator in the group
        if ($this->message instanceof GroupMessage && $this->message->sender->permission == "MEMBER") {
            $this->reply = $this->config->getString("reply.robotOrderStartDenied");

            return false;
        }

        return true;
    }
}
