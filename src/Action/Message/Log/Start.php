<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Log;

use DiceRobot\Action\Message\LogAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Start
 *
 * Start logging.
 *
 * @order log start
 *
 *      Sample: .log start
 *
 * @package DiceRobot\Action\Message\Log
 */
class Start extends LogAction
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

        $this->chatSettings->set("isLogging", true);

        $this->setReply("logStart");
    }
}
