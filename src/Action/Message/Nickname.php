<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Util\Convertor;

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
        list($nickname) = $this->parseOrder();

        // Set nickname
        if (!empty($nickname))
        {
            $this->chatSettings->setNickname($this->message->sender->id, $nickname);

            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.nicknameChanged"),
                    [
                        "昵称" => $this->getNickname(),
                        "新昵称" => $nickname
                    ]
                );
        }
        // Unset nickname
        else
        {
            $this->chatSettings->setNickname($this->message->sender->id);

            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.nicknameUnset"),
                    [
                        "昵称" => $this->getNickname()
                    ]
                );
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
        if (!preg_match("/^(.*)$/", $this->order, $matches))
            throw new OrderErrorException;

        /** @var string $nickname */
        $nickname = $matches[1];

        return [$nickname];
    }
}
