<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Robot;

use DiceRobot\Action\Message\RobotAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Nickname
 *
 * Set robot's nickname.
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
class Nickname extends RobotAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        list($targetId, $nickname) = $this->parseOrder();

        if (!$this->checkId($targetId)) {
            return;
        }

        // Change robot's group card
        if ($this->message instanceof GroupMessage) {
            $this->api->setMemberInfo(
                $this->message->sender->group->id,
                $this->robot->getId(),
                $nickname
            );
        }

        if (!empty($nickname)) {
            // Set nickname
            $this->chatSettings->set("robotNickname", $nickname);

            $this->setReply("robotNicknameSet", [
                "机器人新昵称" => $nickname
            ]);
        } else {
            // Unset nickname
            $this->chatSettings->set("robotNickname", "");

            $this->setReply("robotNicknameUnset");
        }
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
        if (!preg_match("/^(\d{4,})?\s*(.*)$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $targetId = empty($matches[1]) ? null : $matches[1];
        $nickname = $matches[2];

        /**
         * @var string|null $targetId Targeted robot ID.
         * @var string $nickname Robot nickname.
         */
        return [$targetId, $nickname];
    }
}
