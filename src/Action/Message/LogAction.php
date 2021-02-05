<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class LogAction
 *
 * Action of TRPG log.
 *
 * @package DiceRobot\Action\Message
 */
abstract class LogAction extends MessageAction
{
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

    /**
     * Check whether there is unfinished log.
     *
     * @return bool
     */
    protected function checkExists(): bool
    {
        return !empty($this->chatSettings->getString("logUuid"));
    }
}
