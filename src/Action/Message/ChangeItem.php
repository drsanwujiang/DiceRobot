<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Response\UpdateCardResponse;
use DiceRobot\Exception\CharacterCardException\{LostException, NotBoundException};
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Exception\OrderErrorException;

/**
 * Class ChangeItem
 *
 * Change character card's item (HP, MP or SAN).
 *
 * @order hp/mp/san
 *
 *      Sample: .hp+5
 *              .hp-D3
 *              .mp-2
 *              .mp+2D3
 *              .san+1
 *              .san-3D5K2
 *
 * @package DiceRobot\Action\Message
 */
class ChangeItem extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException|LostException
     * @throws NotBoundException|OrderErrorException|SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($item, $symbol, $expression) = $this->parseOrder();

        list($success, $result, $fullResult) = $this->getResult($expression);

        if (!$success) {
            return;
        }

        // Load card
        $cardId = $this->chatSettings->getCharacterCardId($this->message->sender->id);
        $card = $this->resource->getCharacterCard($cardId);

        // Request to change item
        $response = $this->updateCard($cardId, $item, (int) ($symbol . $result));

        // Apply change to the character card
        $card->setItem($item, $response->currentValue);

        $this->setReply("changeItemResult", [
            "昵称" => $this->getNickname(),
            "项目" => $item,
            "增减" => $this->config->getString("wording.itemChange.{$symbol}", ),
            "变动值" => $fullResult,
            "当前值" => $response->currentValue
        ]);
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
        if (!preg_match("/^(hp|mp|san)$/i", $this->match, $matches)) {
            throw new OrderErrorException;
        }

        $item = strtoupper($matches[1]);

        if (!preg_match("/^([+-])\s*(\S+)$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $symbol = $matches[1];
        $expression = $matches[2];

        /**
         * @var string $item Item name.
         * @var string $symbol Addition/Subtraction symbol.
         * @var string $expression Dicing expression.
         */
        return [$item, $symbol, $expression];
    }

    /**
     * Get dicing result and complete expression.
     *
     * @param string $expression Dicing expression.
     *
     * @return array Dicing result and complete expression.
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function getResult(string $expression): array
    {
        $dice = new Dice($expression, $this->chatSettings->getInt("defaultSurfaceNumber"));

        $success = $dice->reason == "";
        $result = $dice->result;
        $fullResult = $dice->getCompleteExpression();

        if (!$success) {
            $this->setReply("changeItemWrongExpression");
        }

        return [$success, $result, $fullResult];
    }

    /**
     * Request to update character card.
     *
     * @param int $cardId Character card ID.
     * @param string $item Item name.
     * @param int $change Change value.
     *
     * @return UpdateCardResponse The response.
     */
    protected function updateCard(int $cardId, string $item, int $change): UpdateCardResponse
    {
        return $this->api->updateCard(
            $this->message->sender->id,
            $cardId,
            $item,
            $change,
            $this->api->getToken($this->robot->getId())->token
        );
    }
}
