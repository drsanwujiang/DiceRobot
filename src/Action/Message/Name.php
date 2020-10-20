<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\FileException\LostException;
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
        list($language, $generateCount) = $this->parseOrder();

        if (!$this->checkRange($generateCount))
            return;

        $this->reply =
            Convertor::toCustomString(
                $this->config->getString("reply.nameGenerateResult"),
                [
                    "发送者QQ" => $this->message->sender->id,
                    "名称" => $this->generateNames($language, $generateCount)
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
        if (!preg_match("/^(cn|en|jp)?\s*([1-9][0-9]*)?$/i", $this->order, $matches))
            throw new OrderErrorException();

        /** @var string $language */
        $language = empty($matches[1]) ? "cn" : strtolower($matches[1]);
        /** @var int $generateCount */
        $generateCount = empty($matches[2]) ? 1 : (int) $matches[2];

        return [$language, $generateCount];
    }

    /**
     * Check the range.
     *
     * @param int $generateCount Generate count
     *
     * @return bool Validity
     */
    protected function checkRange(int $generateCount): bool
    {
        if ($generateCount > $this->config->getInt("order.maxGenerateCount"))
        {
            $this->reply = $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.nameGenerateCountOverstep"),
                    [
                        "最大生成次数" => $this->config->getInt("order.maxGenerateCount")
                    ]
                );

            return false;
        }

        return true;
    }

    /**
     * Generate names.
     *
     * @param string $language Targeted language
     * @param int $count Generate count
     *
     * @return string Names
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

        while ($count--)
        {
            $firstName = $this->draw($firstNames);
            $lastName = $language == "cn" ? $this->draw($lastNames, 2) : $this->draw($lastNames);
            $names[] =
                Convertor::toCustomString(
                    $reference->getString("templates.{$language}"),
                    [
                        "姓" => $firstName,
                        "名" => $lastName
                    ]
                );
        }

        return join("，", $names);
    }

    /**
     * Draw name.
     *
     * @param array $names Names
     * @param int $count Draw count
     *
     * @return string Name
     */
    protected function draw(array $names, int $count = 1): string
    {
        return Random::draw($names, $count, "");
    }
}
