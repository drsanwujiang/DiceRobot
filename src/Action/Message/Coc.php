<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Data\Resource\Reference;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Util\{Convertor, Random};

/**
 * Class Coc
 *
 * Generate character card of investigator.
 *
 * @order coc
 *
 *      Sample: .coc
 *              .coc6 7
 *              .coc8
 *              .cocd
 *              .coc6d
 *
 * @package DiceRobot\Action\Message
 */
class Coc extends MessageAction
{
    /** @var string[][]|string[] COC generate rule */
    protected const COC_GENERATE_RULE = [
        6 => [
            "3D6", "3D6", "3D6",
            "3D6", "3D6", "2D6+6",
            "2D6+6", "3D6+3", "1D10"
        ],
        7 => [
            "3D6X5", "3D6X5", "(2D6+6)X5",
            "3D6X5", "3D6X5", "(2D6+6)X5",
            "3D6X5", "(2D6+6)X5", "3D6X5"
        ],
        "age" => "7D6+8"
    ];

    /**
     * @inheritDoc
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException|LostException
     * @throws OrderErrorException|SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($version, $generateCount, $detailed) = $this->parseOrder();

        if (!$this->checkRange($generateCount)){
            return;
        }

        $reference = $this->resource->getReference("COCCharacterCardTemplate");  // Load reference
        $attributes = $this->generateAttributes($version, $generateCount, $reference);
        $details = $detailed ? "\n{$this->generateDetails($reference)}" : "";
        $atSender = ($this->message instanceof GroupMessage) ? "[mirai:at:{$this->message->sender->id}] " : "";

        $this->setReply("cocGenerateCardHeading", [
            "@发送者" => $atSender,
            "COC版本" => $version,
            "调查员属性" => $attributes,
            "调查员详细信息" => $details
        ]);
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
        if (!preg_match("/^([6-7]?)\s*(?:([1-9][0-9]*)|(d))?$/i", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $version = empty($matches[1]) ? 7 : (int) $matches[1];
        $generateCount = empty($matches[2]) ? 1 : (int) $matches[2];
        $detailed = !empty($matches[3]);

        /**
         * @var int $version COC version
         * @var int $generateCount Count of generation
         * @var bool $detailed Detailed generation flag
         */
        return [$version, $generateCount, $detailed];
    }

    /**
     * Check the range.
     *
     * @param int $generateCount Count of generation
     *
     * @return bool Validity
     */
    protected function checkRange(int $generateCount): bool
    {
        $maxGenerateCount = $this->config->getOrder("maxGenerateCount");

        if ($generateCount > $maxGenerateCount) {
            $this->setReply("cocGenerateCountOverstep", [
                "最大生成次数" => $maxGenerateCount
            ]);

            return false;
        }

        return true;
    }

    /**
     * Generate attributes of character card.
     *
     * @param int $version COC version
     * @param int $count Count of generation
     * @param Reference $reference The reference
     *
     * @return string Attributes
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function generateAttributes(int $version, int $count, Reference $reference): string
    {
        $attributes = "";
        $attributesTemplate = $reference->getString("templates.attributes.{$version}");
        /** @var Dice[] $dices */
        $dices = [];

        while ($count--) {
            $results = [];

            for ($i = 0; $i < 9; $i++) {
                $dices[$i] = isset($dices[$i]) ? clone $dices[$i] : new Dice(self::COC_GENERATE_RULE[$version][$i]);
                $results[$i] = $dices[$i]->result;
            }

            $attributes .= Convertor::toCustomString($attributesTemplate, [
                "力量" => $results[0],
                "体质" => $results[1],
                "意志" => $results[2],
                "敏捷" => $results[3],
                "外表" => $results[4],
                "体型" => $results[5],
                "智力" => $results[6],
                "教育" => $results[7],
                "财产" => $results[8],  // COC 6
                "幸运" => $results[8],  // COC 7
                "属性总和" => array_sum($results),
                "属性总和-不包括幸运" => array_sum($results) - $results[8]  //  COC 7
            ]);
            $attributes .= "\n";
        }

        return rtrim($attributes);
    }

    /**
     * Generate details of character card.
     *
     * @param Reference $reference The reference
     *
     * @return string Details
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function generateDetails(Reference $reference): string
    {
        return Convertor::toCustomString($reference->getString("templates.details"), [
            "性别" => $this->draw($reference->getArray("items.sex")),
            "年龄" => (new Dice(self::COC_GENERATE_RULE["age"]))->result,
            "职业" => $this->draw($reference->getArray("items.occupation")),
            "个人描述" => $this->draw($reference->getArray("items.profile"), 3),
            "思想与信念" => $this->draw($reference->getArray("items.belief")),
            "重要之人" => $this->draw($reference->getArray("items.significantPerson")),
            "与重要之人的关系" => $this->draw($reference->getArray("items.relationship")),
            "意义非凡之地" => $this->draw($reference->getArray("items.meaningfulLocation")),
            "宝贵之物" => $this->draw($reference->getArray("items.treasure")),
            "特质" => $this->draw($reference->getArray("items.trait"))
        ]);
    }

    /**
     * Draw items.
     *
     * @param array $target The target
     * @param int $count Draw count
     *
     * @return string Item(s)
     */
    protected function draw(array $target, int $count = 1): string
    {
        return Random::draw($target, $count, "，");
    }
}
