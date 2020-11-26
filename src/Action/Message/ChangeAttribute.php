<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Response\UpdateCardResponse;
use DiceRobot\Exception\{DiceException\DiceNumberOverstepException,
    DiceException\ExpressionErrorException,
    DiceException\ExpressionInvalidException,
    DiceException\SurfaceNumberOverstepException,
    DiceRobotException,
    OrderErrorException};
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Exception\CharacterCardException\NotBoundException;
use DiceRobot\Util\Convertor;

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
     * @throws DiceRobotException|InternalErrorException|NetworkErrorException|NotBoundException|OrderErrorException
     * @throws UnexpectedErrorException
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

        $this->reply =
            Convertor::toCustomString(
                $this->config->getString("reply.changeAttributeResult"),
                [
                    "昵称" => $this->getNickname(),
                    "属性" => $attribute,
                    "增减" => $this->config->getString("wording.attributeChange.{$symbol}", ),
                    "变动值" => $fullResult,
                    "当前值" => $response->afterValue
                ]
            );
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
         * @var string $attribute Attribute name
         * @var string $symbol Addition/Subtraction symbol
         * @var string $expression Dicing expression
         */
        return [$attribute, $symbol, $expression];
    }

    /**
     * Get dicing result and complete expression.
     *
     * @param string $expression Dicing expression
     *
     * @return array Check result and complete expression
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
            $this->reply = $this->config->getString("reply.changeAttributeWrongExpression");
        }

        return [$success, $result, $fullResult];
    }

    /**
     * Update character card.
     *
     * @param int $cardId Character card ID
     * @param string $attribute Attribute name
     * @param int $change The change value
     *
     * @return UpdateCardResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    protected function updateCard(int $cardId, string $attribute, int $change): UpdateCardResponse
    {
        return $this->api->updateCard(
            $cardId,
            $attribute,
            $change,
            $this->api->auth($this->robot->getId(), $this->message->sender->id)->token
        );
    }
}
