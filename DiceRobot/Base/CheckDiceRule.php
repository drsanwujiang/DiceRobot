<?php /** @noinspection PhpUnusedAliasInspection */

namespace DiceRobot\Base;

use DiceRobot\Exception\COCCheckException\COCCheckRuleDangerousException;
use DiceRobot\Exception\COCCheckException\COCCheckRuleInvalidException;
use DiceRobot\Exception\COCCheckException\COCCheckRuleLostException;
use DiceRobot\Exception\COCCheckException\COCCheckRuleMatchFailedException;
use Throwable;

/**
 * Class CheckDiceRule
 *
 * Container of COC check rule.
 */
final class CheckDiceRule
{
    public string $name;
    public string $description;
    public string $intro;
    private array $levels;

    /** @noinspection PhpUnhandledExceptionInspection */
    public function __construct(array $rules, int $ruleIndex)
    {
        if (isset($rules[$ruleIndex]))
            $ruleArray = $rules[$ruleIndex];
        else
            throw new COCCheckRuleLostException();

        $this->name = $ruleArray["name"];
        $this->description = $ruleArray["description"];
        $this->intro = $ruleArray["intro"];
        $this->levels = $ruleArray["levels"];
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function getCheckLevel(int $result, int $value): string
    {
        foreach ($this->levels as $level => $condition)
        {
            $condition = str_replace(["{&result}", "{&value}", " "], [$result, $value, ""], $condition);

            /* For the check rules can only be added by robot owner not other user, logic expression check is not
             * necessary. Please make sure that logic expression is valid and safe.
             */
            //if (preg_match("/.*[a-z]+.*/i", $condition)) { throw new COCCheckRuleDangerousException(); }

            try
            {
                $evalCommand = "return $condition;";
                $evalResult = eval($evalCommand);
            }
            catch (Throwable $t)
            {
                throw new COCCheckRuleInvalidException();
            }

            if (!is_bool($evalResult))
                throw new COCCheckRuleInvalidException();

            if ($evalResult)
                return $level;
        }

        throw new COCCheckRuleMatchFailedException();
    }
}
