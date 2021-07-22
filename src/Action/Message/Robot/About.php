<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Robot;

use DiceRobot\Action\Message\RobotAction;
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class About
 *
 * Send about information.
 *
 * @order robot about
 *
 *      Sample: .robot about
 *
 * @package DiceRobot\Action\Message\RobotOrder
 */
class About extends RobotAction
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
            $this->resource->getReference("AboutTemplate")->getString("templates.detail"),
            [
                "DiceRobot版本号" => $this->config->getString("dicerobot.version"),
                "机器人QQ昵称" => $this->robot->getNickname()
            ]
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
