<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Resource\Reference;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\FileException\LostException;

/**
 * Class Help
 *
 * Send help information according to the template.
 *
 * @order help
 *
 *      Sample: .help
 *
 * @package DiceRobot\Action\Message
 */
class Help extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws LostException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($order) = $this->parseOrder();

        $reference = $this->resource->getReference("HelpTemplate");

        if (!$this->checkOrder($order, $reference))
            return;

        $actualOrder = $reference->getString("items.mapping.{$order}");

        $this->reply = $reference->getString("items.order.{$actualOrder}");
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
        if (!preg_match("/^\.?([a-z ]+)?$/i", $this->order, $matches))
            throw new OrderErrorException;

        $order = $matches[1] ?? "";

        return [$order];
    }

    /**
     * Check the order.
     *
     * @param string $order The order
     * @param Reference $reference The reference
     *
     * @return bool Validity
     */
    protected function checkOrder(string $order, Reference $reference): bool
    {
        if (empty($order))
        {
            $this->reply = $reference->getString("templates.detail");

            return false;
        }
        elseif (empty($reference->get("items.mapping.{$order}")))
        {
            $this->reply = $this->config->getString("reply.helpOrderUnknown");

            return false;
        }

        return true;
    }
}
