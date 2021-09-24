<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\PutBack;

use DiceRobot\Action\Message\PutBackAction;
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Set
 *
 * Set the default group for putting back.
 *
 * @order put set
 *
 *      Sample: .put set
 *              .put set 12345678
 *
 * @package DiceRobot\Action\Message\PutBack
 */
class Set extends PutBackAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        list($groupId) = $this->parseOrder();

        if ($this->message instanceof GroupMessage) {
            if ($groupId >= 0) {
                $this->setReply("putBackSetGroupWithId");
            } else {
                if ($this->robot->hasFriend($this->message->sender->id)) {
                    $friendSettings = $this->resource->getChatSettings("friend", $this->message->sender->id);
                    $friendSettings->set("putBackGroup", $this->message->sender->group->id);
                }

                $this->setReply("putBackSetGroup");
            }
        } elseif ($this->message instanceof FriendMessage) {
            if ($groupId >= 0) {
                if ($this->robot->hasGroup($groupId)) {
                    $this->chatSettings->set("putBackGroup", $groupId);

                    $this->setReply("putBackSetPrivate");
                } else {
                    $this->setReply("putBackSetPrivateInvalid");
                }
            } else {
                $this->chatSettings->set("putBackGroup", -1);

                $this->setReply("putBackSetClear");
            }
        } elseif ($this->message instanceof TempMessage) {
            $this->setReply("putBackSetTemp");
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
        if (!preg_match("/^([0-9]{4,})?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $groupId = empty($matches[1]) ? -1 : (int) $matches[1];

        /**
         * @var int $groupId Targeted group ID.
         */
        return [$groupId];
    }
}
