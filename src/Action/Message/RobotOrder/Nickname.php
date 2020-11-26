<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\RobotOrder;

use DiceRobot\Action\RobotOrderAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\{MiraiApiException, OrderErrorException};
use DiceRobot\Util\Convertor;

/**
 * Class Nickname
 *
 * Set robot nickname.
 *
 * @order robot nn
 *
 *      Sample: @Robot .robot nn
 *              .robot nn 12345678
 *              .robot nn 5678
 *              @Robot .robot nn Sakura
 *              .robot nn 12345678 Sakura
 *              .robot nn 5678 Sakura
 *
 * @package DiceRobot\Action\Message\RobotOrder
 */
class Nickname extends RobotOrderAction
{
    /**
     * @inheritDoc
     *
     * @throws MiraiApiException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($targetId, $nickname) = $this->parseOrder();

        if (!$this->checkId($targetId)) {
            return;
        }

        // Change robot's group card
        if ($this->message instanceof GroupMessage) {
            $this->api->setMemberName(
                $this->message->sender->group->id,
                $this->robot->getId(),
                $nickname
            );
        }

        // Set nickname
        if (!empty($nickname)) {
            $this->chatSettings->set("robotNickname", $nickname);

            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.robotOrderNicknameSet"),
                    [
                        "机器人新昵称" => $nickname
                    ]
                );
        } else {
            // Unset nickname
            $this->chatSettings->set("robotNickname", null);

            $this->reply = $this->config->getString("reply.robotOrderNicknameUnset");
        }
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
        if (!preg_match("/^([0-9]{4,})?\s*(.*)$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $targetId = empty($matches[1]) ? null : $matches[1];
        $nickname = $matches[2];

        /**
         * @var string|null $targetId Targeted robot ID
         * @var string $nickname Robot nickname
         */
        return [$targetId, $nickname];
    }
}
