<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Robot;

use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\OrderErrorException;

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
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        list($robotId) = $this->parseOrder();

        if (!$this->checkId($robotId) || !$this->checkPermission()) {
            return;
        }

        $this->chatSettings->set("active", false);

        $this->setReply("robotStop");
    }

    /**
     * @inheritDoc
     *
     * @return bool Permitted.
     */
    protected function checkPermission(): bool
    {
        // Must be the owner or the administrator in the group
        if ($this->message instanceof GroupMessage && $this->message->sender->permission == "MEMBER") {
            $this->setReply("robotStopDenied");

            return false;
        }

        return true;
    }
}
