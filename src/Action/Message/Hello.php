<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Exception\OrderErrorException;

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

        $this->setRawReply($this->getCustomString(
            $this->resource->getReference("HelloTemplate")->getString("templates.detail")
        ));
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
