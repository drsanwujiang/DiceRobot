<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;
use DiceRobot\Exception\CheckRuleException\{DangerousException, InvalidException, MatchFailedException};
use Throwable;

/**
 * Class CheckRule
 *
 * Resource container. COC check rule.
 *
 * @package DiceRobot\Data\Resource
 */
class CheckRule extends Resource
{
    /**
     * @inheritDoc
     *
     * @param array $data Check rule data.
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->data["name"] ??= "";
        $this->data["description"] ??= "";
        $this->data["introduction"] ??= "";
        $this->data["levels"] ??= [];
    }

    /**
     * Get check level.
     *
     * @param int $result Check result.
     * @param int $value Check value.
     *
     * @return string Check level.
     *
     * @throws DangerousException Check rule has dangerous item.
     * @throws InvalidException Check rule has invalid item.
     * @throws MatchFailedException Check rule is imperfect.
     */
    public function getCheckLevel(int $result, int $value): string
    {
        foreach ($this->getArray("levels") as $level => $condition)
        {
            $condition = str_replace(["{&result}", "{&value}", " "], [$result, $value, ""], $condition);

            if (preg_match("/[a-zA-Z]/", $condition)) {
                throw new DangerousException();
            }

            try {
                $evalCommand = "return $condition;";
                $evalResult = eval($evalCommand);
            } catch (Throwable $t) {  // TODO: catch (Throwable) in PHP 8
                throw new InvalidException();
            }

            if (!is_bool($evalResult)) {
                throw new InvalidException();
            }

            if ($evalResult) {
                return $level;
            }
        }

        throw new MatchFailedException();
    }
}
