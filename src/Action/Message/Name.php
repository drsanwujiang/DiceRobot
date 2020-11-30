<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Util\{Convertor, Random};

/**
 * Class Name
 *
 * Generate random name.
 *
 * @order name
 *
 *      Sample: .name
 *              .name 5
 *              .name cn
 *              .name jp 10
 *              .name en 20
 *
 * @package DiceRobot\Action\Message
 */
class Name extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws LostException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($language, $count) = $this->parseOrder();

        if (!$this->checkRange($count)) {
            return;
        }

        $this->setReply("nameGenerateResult", [
            "发送者QQ" => $this->message->sender->id,
            "名称" => $this->generateNames($language, $count)
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
        if (!preg_match("/^(cn|en|jp)?\s*([1-9][0-9]*)?$/i", $this->order, $matches)) {
            throw new OrderErrorException();
        }

        $language = empty($matches[1]) ? "cn" : strtolower($matches[1]);
        $count = empty($matches[2]) ? 1 : (int) $matches[2];

        /**
         * @var string $language Language.
         * @var int $count Count of generation.
         */
        return [$language, $count];
    }

    /**
     * Check the range.
     *
     * @param int $count Count of generation.
     *
     * @return bool Validity.
     */
    protected function checkRange(int $count): bool
    {
        $maxGenerateCount = $this->config->getOrder("maxGenerateCount");

        if ($count > $maxGenerateCount) {
            $this->setReply("nameGenerateCountOverstep", [
                "最大生成次数" => $maxGenerateCount
            ]);

            return false;
        }

        return true;
    }

    /**
     * Generate names.
     *
     * @param string $language Targeted language.
     * @param int $count Count of generation.
     *
     * @return string Generated names.
     *
     * @throws LostException
     */
    protected function generateNames(string $language, int $count): string
    {
        $reference = $this->resource->getReference("NameTemplate");
        $firstNames = $reference->getArray("items.{$language}.firstName");
        $lastNames = $language == "cn" ?
            $reference->getArray("items.{$language}.lastNameSingle") :
            $reference->getArray("items.{$language}.lastName");
        $names = [];

        while ($count--) {
            $firstName = $this->draw($firstNames);
            $lastName = $language == "cn" ? $this->draw($lastNames, 2) : $this->draw($lastNames);
            $names[] = Convertor::toCustomString($reference->getString("templates.{$language}"), [
                "姓" => $firstName,
                "名" => $lastName
            ]);
        }

        return join("，", $names);
    }

    /**
     * Draw name(s).
     *
     * @param array $names Names.
     * @param int $count Draw count.
     *
     * @return string Name(s).
     */
    protected function draw(array $names, int $count = 1): string
    {
        return Random::draw($names, $count, "");
    }
}
