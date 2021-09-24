<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\PutBack;

use DiceRobot\Action\Message\PutBackAction;
use DiceRobot\Data\Deck;
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
use DiceRobot\Data\Resource\CardDeck;
use DiceRobot\Exception\CardDeckException\InvalidException;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Pet
 *
 * Put a card back on the top/bottom of the deck.
 *
 * @order put top/bottom
 *
 *      Sample: .put top Card
 *              .put bottom Card
 *
 * @package DiceRobot\Action\Message\PutBack
 */
class Put extends PutBackAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException|InvalidException
     */
    public function __invoke(): void
    {
        list($content) = $this->parseOrder();

        if (!$this->checkGroup()) {
            return;
        }

        if ($this->message instanceof GroupMessage) {
            $deckName = $this->chatSettings->get("defaultCardDeck");
            $cardDeck = $this->chatSettings->get("cardDeck");

            if (empty($deckName) || !($cardDeck instanceof CardDeck)) {
                $this->setReply("putBackDeckNotSet");

                return;
            }

            $deck = $cardDeck->getDeck($deckName);
            $this->putBack($deck, $content);

            $this->setReply("putBackResult");
        } elseif ($this->message instanceof FriendMessage || $this->message instanceof TempMessage) {
            $groupId = $this->message instanceof FriendMessage ?
                $this->chatSettings->getInt("putBackGroup") : $this->message->sender->group->id;

            if (!$this->robot->hasGroup($groupId)) {
                $this->setReply("putBackGroupInvalid");

                return;
            }

            $groupSettings = $this->resource->getChatSettings("group", $groupId);
            $deckName = $groupSettings->get("defaultCardDeck");
            $cardDeck = $groupSettings->get("cardDeck");

            if (empty($deckName) || !($cardDeck instanceof CardDeck)) {
                $this->setReply("putBackDeckNotSet");

                return;
            }

            $deck = $cardDeck->getDeck($deckName);
            $this->putBack($deck, $content);

            $this->sendGroupMessageAsync($this->getCustomReply("putBackPrivate", [
                "昵称" => $groupSettings->getNickname($this->message->sender->id) ??
                    $this->api->getMemberInfo($groupId, $this->message->sender->id)->getString("memberName")
            ]), $groupId);
            $this->setReply("putBackResult");
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
        if (empty($this->order)) {
            throw new OrderErrorException;
        }

        $content = $this->order;

        /**
         * @var string $content Card content.
         */
        return [$content];
    }

    /**
     * Put the card back into the deck.
     *
     * @param Deck $deck The deck.
     * @param string $content The card.
     */
    protected function putBack(Deck $deck, string $content): void
    {
        if (in_array($this->match, ["top", "顶", "牌顶", "牌堆顶", "顶部"])) {
            $deck->putBackOnTop($content);
        } elseif (in_array($this->match, ["bottom", "btm", "底", "牌底", "牌堆底", "底部"])) {
            $deck->putBackOnBottom($content);
        }
    }
}
