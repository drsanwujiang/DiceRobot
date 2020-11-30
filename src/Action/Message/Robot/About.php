<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Robot;

use DiceRobot\Action\Message\RobotAction;
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Util\Convertor;

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

        $this->setRawReply(Convertor::toCustomString(
            $this->resource->getReference("AboutTemplate")->getString("templates.detail"),
            [
                "DiceRobot版本号" => $this->config->getString("dicerobot.version"),
                "机器人QQ昵称" => $this->robot->getNickname(),
                "机器人QQ号" => $this->robot->getId(),
                "机器人昵称" => $this->getRobotNickname()
            ]
        ));
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^$/", $this->order)) {
            throw new OrderErrorException;
        }

        return [];
    }
}
