<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Report\Message\{FriendMessage, TempMessage};
use DiceRobot\Data\Resource\CardDeck;

/**
 * Class PutBackAction
 *
 * Action of putting back.
 *
 * @package DiceRobot\Action\Message
 */
abstract class PutBackAction extends MessageAction
{
    /**
     * Check whether the put back group is set, if sender is friend.
     *
     * @return bool Group is set.
     */
    protected function checkGroup(): bool
    {
        // If sender is friend, put back group must be set
        if ($this->message instanceof FriendMessage && $this->chatSettings->getInt("putBackGroup") < 0) {
            $this->setReply("putBackGroupNotSet");

            return false;
        }

        return true;
    }

    /**
     * Whether the default card deck has been set.
     *
     * @return bool Validity.
     */
    protected function checkDeck(): bool
    {
        if ($this->message instanceof FriendMessage) {
            $groupSettings = $this->resource->getChatSettings("group", $this->chatSettings->getInt("putBackGroup"));
        } elseif ($this->message instanceof TempMessage) {
            $groupSettings = $this->resource->getChatSettings("group", $this->message->sender->group->id);
        } else {
            $groupSettings = $this->chatSettings;
        }

        if (empty($groupSettings->getString("defaultCardDeck")) ||
            !($groupSettings->get("cardDeck") instanceof CardDeck)
        ) {
            $this->setReply("putBackDeckNotSet");

            return false;
        }

        return true;
    }
}
