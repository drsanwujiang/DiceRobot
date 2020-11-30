<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Resource\Reference;
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Exception\OrderErrorException;

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

        if (!$this->checkOrder($order, $reference)) {
            return;
        }

        $items = $reference->get("templates.mapping.{$order}");

        if (is_string($items)) {
            $this->setRawReply($reference->getString("items.{$items}"));
        } elseif (is_array($items)) {
            foreach ($items as $item) {
                $this->setRawReply($reference->getString("items.{$item}"));
            }
        }
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
        if (!preg_match("/^\.?([a-z ]+)?$/i", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $order = strtolower($matches[1] ?? "");

        /**
         * @var string $order Order to query.
         */
        return [$order];
    }

    /**
     * Check the order.
     *
     * @param string $order The order.
     * @param Reference $reference The reference.
     *
     * @return bool Validity.
     */
    protected function checkOrder(string $order, Reference $reference): bool
    {
        if (empty($order)) {
            $this->setRawReply($reference->getString("templates.detail"));

            return false;
        } elseif (!$reference->has("templates.mapping.{$order}")) {
            $this->setReply("helpOrderUnknown");

            return false;
        }

        return true;
    }
}
