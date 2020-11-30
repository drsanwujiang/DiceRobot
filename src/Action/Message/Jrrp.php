<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Jrrp
 *
 * Send message sender's luck of today.
 *
 * @order jrrp
 *
 *      Sample: .jrrp
 *
 * @package DiceRobot\Action\Message
 */
class Jrrp extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        $this->setReply("jrrpResult", [
            "昵称" => $this->getNickname(),
            "人品" => $this->api->jrrp($this->message->sender->id)->jrrp
        ]);
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
        if (!preg_match("/^$/", $this->order)) {
            throw new OrderErrorException;
        }

        return [];
    }
}
