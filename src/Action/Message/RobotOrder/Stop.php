<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\RobotOrder;

use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\{MiraiApiException, OrderErrorException};

/**
 * Class Stop
 *
 * Set robot inactive.
 *
 * @order robot stop
 *
 *      Sample: @Robot .robot stop
 *              .robot stop 12345678
 *              .robot stop 5678
 *
 * @package DiceRobot\Action\Message\RobotOrder
 */
class Stop extends Start
{
    /**
     * @inheritDoc
     *
     * @throws MiraiApiException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($robotId) = $this->parseOrder();

        if (!$this->checkId($robotId) || !$this->checkPermission()) {
            return;
        }

        $this->chatSettings->set("active", false);

        $this->setReply("robotOrderStop", [
            "机器人昵称" => $this->getRobotNickname()
        ]);
    }

    /**
     * @inheritDoc
     *
     * @return bool Validity
     */
    protected function checkPermission(): bool
    {
        // Must be the owner or the administrator in the group
        if ($this->message instanceof GroupMessage && $this->message->sender->permission == "MEMBER") {
            $this->setReply("robotOrderStopDenied");

            return false;
        }

        return true;
    }
}
