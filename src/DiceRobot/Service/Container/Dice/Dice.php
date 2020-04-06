<?php
namespace DiceRobot\Service\Container\Dice;

use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\ExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Service\Customization;
use DiceRobot\Service\Rolling;
use Throwable;

/**
 * The dice.
 */
class Dice
{
    /** @var int Default dice surface number */
    private static int $defaultSurfaceNumber;

    /** @var string Order to roll */
    private string $order;

    /** @var string Visibility type of rolling. H: Private rolling, S: Only display final result */
    public ?string $vType = NULL;

    /** @var string Bonus/punishment dice type. B: Bonus dice, P: Punishment dice */
    public ?string $bpType = NULL;

    /** @var int Bonus/punishment dice number */
    public int $bpDiceNumber = 1;

    /** @var array Rolling result of bonus/punishment dice */
    public array $bpResult;

    /** @var string Rolling expression */
    public string $expression = "D100";

    /** @var array Subexpressions */
    public array $subexpressions = array();

    /** @var string Rolling reason */
    public string $reason = "";

    /** @var int Rolling result */
    public int $rollResult;

    /**
     * The constructor.
     *
     * @param string $order Rolling order
     *
     * @throws DiceNumberOverstepException
     * @throws SurfaceNumberOverstepException
     * @throws ExpressionErrorException
     */
    public function __construct(string $order = "")
    {
        $this->order = $order;

        // Default subexpression
        array_push($this->subexpressions,
            new Subexpression("D" . self::$defaultSurfaceNumber));
        $this->parseOrder();
        $this->roll();
    }

    /**
     * Set default surface number.
     *
     * @param int|null $default Default surface number
     */
    public static function setDefaultSurfaceNumber(?int $default = NULL): void
    {
        self::$defaultSurfaceNumber = $default ?? Customization::getSetting("defaultSurfaceNumber");
    }

    /**
     * Parse out dice type and rolling expression of the rolling order.
     *
     * @throws DiceNumberOverstepException
     * @throws SurfaceNumberOverstepException
     */
    private function parseOrder(): void
    {
        $order = $this->order;

        if (preg_match("/^[hs]/i", $order, $result))
        {
            $this->vType = strtoupper($result[0]);
            $order = preg_replace("/^[hs][\s]*/i", "", $order, 1);
        }
        if (preg_match("/^[bp]/i", $order, $result))
        {
            $this->bpType = strtoupper($result[0]);
            $order = preg_replace("/^[bp][\s]*/i", "", $order, 1);
        }
        if ($order == "")
            return;

        $this->order = $order;

        // In case the dice is a bonus/punishment dice
        if ($this->bpType)
        {
            if (preg_match("/^[1-9][0-9]*/", $order, $result))
            {
                $this->bpDiceNumber = (int) $result[0];
                $this->reason = preg_replace("/^[1-9][0-9]*[\s]*/", "", $order, 1);
            }
            else
                $this->reason = $this->order;

            return;
        }

        // In case the dice is a normal dice, parse operations. Sample: x1Dy1Kz1+x2Dy2+c reason
        preg_match("/^[\S]+[\s]*/", $order, $result);
        $expression = trim($result[0]);
        $this->reason = preg_replace("/^[\S]+[\s]*/", "", $order, 1);

        if (is_numeric($expression) || preg_match("/[^0-9dk+\-x*()（）]/i", $expression))
        {
            $this->reason = $order;
            return;
        }

        self::parseExpression($expression);
    }

    /**
     * Parse rolling expression to several subexpression split by mathematical symbols.
     *
     * @param string $expression Rolling expression
     *
     * @throws DiceNumberOverstepException
     * @throws SurfaceNumberOverstepException
     */
    private function parseExpression(string $expression): void
    {
        // Parse expression. Sample: 3D5+5+2D6k2
        $subexpressions = preg_split("/([+\-Xx*()（）])/", $expression, -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        $preSubexpressions = [];
        $offsetIncrement = 0;

        foreach ($subexpressions as &$subexpression)
        {
            if (is_numeric($subexpression[0]) ||
                preg_match("/^([1-9][0-9]*)?D[1-9][0-9]*(K([1-9][0-9]*)?)?$/i", $subexpression[0]))
            {
                // If subexpression is like 5D100K2 or 1, push to the temp array
                $subexpression[0] = str_replace(["d", "k"], ["D", "K"], $subexpression[0]);
                $subexpression[1] += $offsetIncrement;

                array_push($preSubexpressions, new Subexpression($subexpression[0], $subexpression[1]));
            }
            elseif (preg_match("/^([1-9][0-9]*)?D(K([1-9][0-9]*)?)?$/i", $subexpression[0]))
            {
                // If subexpression is like 5DK2
                $subexpression[0] = str_replace(["d", "k"], ["D", "K"], $subexpression[0]);
                $subexpression[1] += $offsetIncrement;  // Change offset

                // Add default surface number after D
                $offsetIncrement += strlen(self::$defaultSurfaceNumber);
                $expression = substr_replace($expression, self::$defaultSurfaceNumber,
                    stripos($expression, "D", $subexpression[1]) + 1, 0);
                $subexpression[0] = substr_replace($subexpression[0], self::$defaultSurfaceNumber,
                    strpos($subexpression[0], "D") + 1, 0);

                array_push($preSubexpressions, new Subexpression($subexpression[0], $subexpression[1]));
            }
            else
            {
                $this->reason = $this->order;
                return;
            }
        }

        // If successfully parse expression, apply change
        $this->subexpressions = $preSubexpressions;
        // Replace Chinese brackets, d, k, x and X
        $this->expression = str_replace(["（", "）", "d", "k", "x", "X"], ["(", ")", "D", "K", "*", "*"], $expression);
    }

    /**
     * Roll several dices determined by subexpressions and calculate summary.
     *
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     */
    private function roll(): void
    {
        // B/P dice
        if ($this->bpType)
        {
            // Check range
            if ($this->bpDiceNumber < 1 || $this->bpDiceNumber > Customization::getSetting("maxDiceNumber"))
                throw new DiceNumberOverstepException();

            $this->bpResult = Rolling::roll($this->bpDiceNumber, 10);
            $this->rollResult = $this->subexpressions[0]->rollSummary;
            $tensPlace = intdiv($this->rollResult, 10);

            if ($this->bpType == "B" && $tensPlace > min($this->bpResult))
                $this->rollResult -= ($tensPlace - min($this->bpResult)) * 10;
            elseif ($this->bpType == "P" && $tensPlace < max($this->bpResult))
                $this->rollResult += (max($this->bpResult) - $tensPlace) * 10;

            $this->rollResult = min($this->rollResult, 100);  // Prevent result from over range
            return;
        }

        try
        {
            $evalCommand = "return {$this->toArithmeticExpression()};";
            $this->rollResult = eval($evalCommand);
        }
        catch (Throwable $t)
        {
            throw new ExpressionErrorException(
                $t->getMessage(),
                $this->order,
                $this->expression,
                $this->toArithmeticExpression()
            );
        }
    }

    /**
     * Generate arithmetic expression, in which the subexpressions will be replaced with rolling summary.
     *
     * @return string Arithmetic expression
     */
    private function toArithmeticExpression(): string
    {
        $expression = $this->expression;
        $arithmeticExpression = "";
        $tempArray = [];

        foreach ($this->subexpressions as &$subexpression)
        {
            $tempArray = explode($subexpression->subexpression, $expression, 2);
            $arithmeticExpression .= $tempArray[0] . $subexpression->rollSummary;
            $expression = $tempArray[1];
        }

        return $arithmeticExpression . $tempArray[1];
    }

    /**
     * Replace * with ×.
     *
     * @return string Expression
     */
    public function getExpression(): string
    {
        return str_replace("*", "×", $this->expression);
    }

    /**
     * Generate result expression, in which the subexpressions will be replaced with rolling result.
     *
     * @return string Result expression
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function getResultExpression(): string
    {
        $expression = $this->expression;
        $resultExpression = "";
        $tempArray = [];

        foreach ($this->subexpressions as &$subexpression)
        {
            $tempArray = explode($subexpression->subexpression, $expression, 2);
            $resultExpression .= $tempArray[0] . $subexpression->getResultString();
            $expression = $tempArray[1];
        }

        return str_replace("*", "×", $resultExpression . $tempArray[1]);
    }

    /**
     * Generate arithmetic expression, Replace * with ×.
     *
     * @return string Arithmetic expression
     */
    public function getArithmeticExpression(): string
    {
        return str_replace("*", "×", $this->toArithmeticExpression());
    }

    /**
     * Generate complete expression according to dice type.
     *
     * @return string Complete expression
     */
    public function getCompleteExpression(): string
    {
        if (!$this->bpType)
        {
            // Normal dice
            if ($this->vType != "S")
            {
                $expression = $this->getExpression();
                $resultExpression = $this->getResultExpression();
                $arithmeticExpression = $this->getArithmeticExpression();
                $completeExpression = $expression . "=" . $resultExpression;
                $completeExpression .= $resultExpression == $arithmeticExpression ? "" : "=" . $arithmeticExpression;
                $completeExpression .= $this->rollResult == $arithmeticExpression ? "" : "=" . $this->rollResult;
            }
            else
            {
                $completeExpression = $this->getExpression() . "=" . $this->rollResult;
            }
        }
        else
        {
            // B/P dice
            if ($this->vType != "S")
                $completeExpression = $this->bpType . $this->bpDiceNumber . "=" . $this->getResultExpression() .
                    "[" . Customization::getWording("BPDiceType", $this->bpType) . ":" .
                    join(" ", $this->bpResult) . "]" . "=" . $this->rollResult;
            else
                $completeExpression = $this->bpType . $this->bpDiceNumber . "=" . $this->rollResult;
        }

        return $completeExpression;
    }
}
