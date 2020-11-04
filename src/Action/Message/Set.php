<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Util\Convertor;

/**
 * Class Set
 *
 * Set default dice surface number.
 *
 * @order set
 *
 *      Sample: .set
 *              .set 50
 *
 * @package DiceRobot\Action\Message
 */
class Set extends MessageAction
{
    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        list($defaultSurfaceNumber) = $this->parseOrder();

        if (!$this->checkRange($defaultSurfaceNumber)) {
            return;
        }

        if ($defaultSurfaceNumber) {
            // Set the default dice surface number of this chat
            $this->chatSettings->set("defaultSurfaceNumber", $defaultSurfaceNumber);

            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.setResult"),
                    [

                        "默认骰子面数" => $defaultSurfaceNumber
                    ]
                );
        } else {
            // Reset the default dice surface number of this chat to the default value of the robot
            $this->chatSettings->set("defaultSurfaceNumber", null);

            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.setResetResult"),
                    [
                        "默认骰子面数" => $this->config->getInt("order.defaultSurfaceNumber")
                    ]
                );
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches)) {
            return [-1];
        }

        /** @var int|null $defaultSurfaceNumber */
        $defaultSurfaceNumber = empty($matches[1]) ? null : (int) $matches[1];

        return [$defaultSurfaceNumber];
    }

    /**
     * Check the range.
     *
     * @param int|null $defaultSurfaceNumber Default dice surface number
     *
     * @return bool Validity
     */
    protected function checkRange(?int $defaultSurfaceNumber): bool
    {
        if (!is_null($defaultSurfaceNumber) &&
            ($defaultSurfaceNumber < 1 || $defaultSurfaceNumber > $this->config->getInt("order.maxSurfaceNumber"))
        ) {
            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.setDefaultSurfaceNumberInvalid"),
                    [
                        "最大骰子面数" => $this->config->getInt("order.maxSurfaceNumber")
                    ]
                );

            return false;
        }

        return true;
    }
}
