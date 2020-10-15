<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Util\Convertor;

/**
 * Class Hello
 *
 * Send greetings according to the template.
 *
 * @order hello
 *
 *      Sample: .hello
 *
 * @package DiceRobot\Action\Message
 */
class Hello extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws LostException|OrderErrorException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        $this->reply =
            Convertor::toCustomString(
                $this->resource->getReference("HelloTemplate")->getString("templates.detail"),
                [
                    "机器人昵称" => $this->robot->getNickname(),
                    "机器人QQ号" => $this->robot->getId(),
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
        if (!preg_match("/^$/", $this->order))
            throw new OrderErrorException;

        return [];
    }
}
