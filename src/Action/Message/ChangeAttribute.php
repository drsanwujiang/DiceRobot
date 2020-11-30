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
 * Class ChangeAttribute
 *
 * Change investigator's attributes (HP, MP or SAN).
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
class ChangeAttribute extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException|LostException
     * @throws NotBoundException|OrderErrorException|SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($attribute, $symbol, $expression) = $this->parseOrder();

        list($success, $result, $fullResult) = $this->getResult($expression);

        if (!$success) {
            return;
        }

        // Load card
        $cardId = $this->chatSettings->getCharacterCardId($this->message->sender->id);
        $card = $this->resource->getCharacterCard($cardId);

        // Request server to change attribute
        $response = $this->updateCard($cardId, $attribute, (int) ($symbol . $result));

        // Apply change to the character card
        $card->setAttribute($attribute, $response->afterValue);

        $this->setReply("changeAttributeResult", [
            "昵称" => $this->getNickname(),
            "属性" => $attribute,
            "增减" => $this->config->getString("wording.attributeChange.{$symbol}", ),
            "变动值" => $fullResult,
            "当前值" => $response->afterValue
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

        $attribute = strtoupper($matches[1]);

        if (!preg_match("/^([+-])\s*(\S+)$/i", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $symbol = $matches[1];
        $expression = $matches[2];

        /**
         * @var string $attribute Attribute name.
         * @var string $symbol Addition/Subtraction symbol.
         * @var string $expression Dicing expression.
         */
        return [$attribute, $symbol, $expression];
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
            $this->setReply("changeAttributeWrongExpression");
        }

        return [$success, $result, $fullResult];
    }

    /**
     * Update character card.
     *
     * @param int $cardId Character card ID.
     * @param string $attribute Attribute name.
     * @param int $change Change value.
     *
     * @return UpdateCardResponse The response.
     */
    protected function updateCard(int $cardId, string $attribute, int $change): UpdateCardResponse
    {
        return $this->api->updateCard(
            $cardId,
            $attribute,
            $change,
            $this->api->authorize($this->robot->getId(), $this->message->sender->id)->token
        );
    }
}
