<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Log;

use DiceRobot\Action\Message\LogAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Stop
 *
 * Stop logging.
 *
 * @order log stop
 *
 *      Sample: .log stop
 *
 * @package DiceRobot\Action\Message\Log
 */
class Stop extends LogAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        if (!$this->checkExists()) {
            $this->setReply("logNotExist");

            return;
        }

        $this->chatSettings->set("isLogging", false);

        $this->setReply("logStop");
    }
}
