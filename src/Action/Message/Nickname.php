<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Nickname
 *
 * Set/Unset nickname of message sender.
 *
 * @order nn
 *
 *      Sample: .nn
 *              .nn Drsanwujiang
 *
 * @package DiceRobot\Action\Message
 */
class Nickname extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        list($newNickname) = $this->parseOrder();

        if (!empty($newNickname)) {
            $currentNickname = $this->getNickname();

            // Set nickname
            $this->chatSettings->setNickname($this->message->sender->id, $newNickname);

            $this->setReply("nicknameSet", [
                "昵称" => $currentNickname,
                "新昵称" => $newNickname
            ]);
        } else {
            // Unset nickname
            $this->chatSettings->setNickname($this->message->sender->id);

            $this->setReply("nicknameUnset", [
                "昵称" => $this->getNickname()
            ]);
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
        if (!preg_match("/^(.*)$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $nickname = $matches[1];

        /**
         * @var string $nickname Nickname.
         */
        return [$nickname];
    }
}
