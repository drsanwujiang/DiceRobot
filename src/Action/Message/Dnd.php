<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Exception\{DiceRobotException, OrderErrorException};
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Util\Convertor;

/**
 * Class Dnd
 *
 * Generate character card of adventurer.
 *
 * @order dnd
 *
 *      Sample: .dnd
 *              .dnd8
 *
 * @package DiceRobot\Action\Message
 */
class Dnd extends MessageAction
{
    /** @var string DND generation rule. */
    protected const DND_GENERATE_RULE = "4D6K3";

    /**
     * @inheritDoc
     *
     * @throws DiceRobotException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($count) = $this->parseOrder();

        if (!$this->checkRange($count)) {
            return;
        }

        $this->setReply("dndGenerateResult", [
            "冒险者属性" => $this->generateAttributes($count)
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
        if (!preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $count = empty($matches[1]) ? 1 : (int) $matches[1];

        /**
         * @var int $count Generation count.
         */
        return [$count];
    }

    /**
     * Check the range.
     *
     * @param int $count Generation count.
     *
     * @return bool Validity.
     */
    protected function checkRange(int $count): bool
    {
        $maxGenerateCount = $this->config->getOrder("maxGenerateCount");

        if ($count < 1 || $count > $maxGenerateCount) {
            $this->setReply("dndGenerateCountOverstep", [
                "最大生成次数" => $maxGenerateCount
            ]);

            return false;
        }

        return true;
    }

    /**
     * Generate attributes of character card.
     *
     * @param int $count Generation count.
     *
     * @return string Attributes.
     *
     * @throws LostException
     */
    protected function generateAttributes(int $count): string
    {
        $attributes = "";
        $attributesTemplate =
            $this->resource->getReference("DNDCharacterCardTemplate")->getString("templates.attributes");

        while ($count--) {
            $results = [];

            for ($i = 0; $i < 6; $i++) {
                $dice = isset($dice) ? clone $dice : new Dice(self::DND_GENERATE_RULE);
                $results[$i] = $dice->result;
            }

            $attributes .= Convertor::toCustomString($attributesTemplate, [
                "力量" => $results[0],
                "体质" => $results[1],
                "敏捷" => $results[2],
                "智力" => $results[3],
                "感知" => $results[4],
                "魅力" => $results[5],
                "属性总和" => array_sum($results)
            ]);
            $attributes .= "\n";
        }

        return rtrim($attributes);
    }
}
