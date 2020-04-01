<?php
namespace DiceRobot\Service\Container;

use DiceRobot\Exception\InformativeException\CheckRuleException\DangerousException;
use DiceRobot\Exception\InformativeException\CheckRuleException\InvalidException;
use DiceRobot\Exception\InformativeException\CheckRuleException\LostException;
use DiceRobot\Exception\InformativeException\CheckRuleException\MatchFailedException;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use Throwable;

/**
 * COC check rule container.
 */
final class CheckRule extends Reference
{
    public string $name;
    public string $description;
    public string $intro;
    private array $levels;

    /**
     * Constructor.
     *
     * @param int $ruleIndex Rule index
     *
     * @throws FileLostException
     * @throws JSONDecodeException
     * @throws LostException
     * @throws ReferenceUndefinedException
     */
    public function __construct(int $ruleIndex)
    {
        parent::__construct("COCCheckRule");

        if (isset($this->reference["rules"][$ruleIndex]))
            $rule = $this->reference["rules"][$ruleIndex];
        else
            throw new LostException();

        $this->name = $rule["name"];
        $this->description = $rule["description"];
        $this->intro = $rule["intro"];
        $this->levels = $rule["levels"];
    }

    /**
     * Get check level.
     *
     * @param int $result Check result
     * @param int $value Check value
     *
     * @return string Check level
     *
     * @throws DangerousException
     * @throws InvalidException
     * @throws MatchFailedException
     *
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function getCheckLevel(int $result, int $value): string
    {
        foreach ($this->levels as $level => $condition)
        {
            $condition = str_replace(["{&result}", "{&value}", " "], [$result, $value, ""], $condition);

            /* For the check rules can only be added by robot owner not other user, logic expression check is not
             * necessary. Please make sure that logic expression is valid and safe.
             */
            //if (preg_match("/.*[a-z]+.*/i", $condition)) { throw new DangerousException(); }

            try
            {
                $evalCommand = "return $condition;";
                $evalResult = eval($evalCommand);
            }
            catch (Throwable $t)
            {
                throw new InvalidException();
            }

            if (!is_bool($evalResult))
                throw new InvalidException();

            if ($evalResult)
                return $level;
        }

        throw new MatchFailedException();
    }
}
