<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Data\Resource\CardDeck;

/**
 * Class DeckAction
 *
 * Action of default deck.
 *
 * @package DiceRobot\Action\Message
 */
abstract class DeckAction extends MessageAction
{
    /**
     * Check the permission of message sender.
     *
     * @return bool Permitted.
     */
    protected function checkPermission(): bool
    {
        // Must be the owner or the administrator in the group
        if ($this->message instanceof GroupMessage && $this->message->sender->permission == "MEMBER") {
            $this->setReply("deckDenied");

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
        if (empty($this->chatSettings->getString("defaultCardDeck")) ||
            !($this->chatSettings->get("cardDeck") instanceof CardDeck)
        ) {
            $this->setReply("deckNotSet");

            return false;
        }

        return true;
    }
}
