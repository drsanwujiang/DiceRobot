<?php
namespace DiceRobot\Base;

use Throwable;

/**
 * Class DiceOperation
 *
 * Container of rolling operation.
 */
final class DiceOperation
{
    /**
     * The default dice surface number read from robot settings. If subexpression doesn't contain surface number, like
     * "D" or "5D", this value will be added.
     *
     * @var int
     */
    private int $defaultSurfaceNumber;

    /**
     * Order to roll.
     *
     * @var string
     */
    private string $order;

    /**
     * Visibility type of rolling.
     *
     * H: Private rolling
     * S: Only display final result
     *
     * @var string
     */
    public ?string $vType = NULL;

    /**
     * Flag of bonus/punishment dice.
     *
     * B: Bonus dice
     * P: Punishment dice
     *
     * @var string
     */
    public ?string $bpType = NULL;

    /**
     * Bonus/punishment dice number, if the dice is a bonus/punishment dice.
     *
     * @var int
     */
    public int $bpDiceNumber = 1;

    /**
     * Rolling result of bonus/punishment dice.
     *
     * @var array
     */
    public array $bpResult;

    /**
     * Expression of rolling.
     *
     * @var string
     */
    public string $expression = "D100";

    /**
     * Subexpression objects.
     *
     * @var array
     */
    public array $subexpressions = array();

    /**
     * Reason to roll, which can be an empty string.
     *
     * @var string
     */
    public string $reason = "";

    /**
     * If the expression has been successfully executed.
     *
     * 0: Roll successfully
     * -1: Dice number or dice surface number out of range
     * -2: Subexpression is illegal
     *
     * @var int
     */
    public int $success = 0;

    /**
     * Result of the expression, if the dice is a normal dice.
     * Result of rolling, if the dice is a b/p dice.
     *
     * @var int
     */
    public int $rollResult;

    /**
     * DiceOperation constructor.
     *
     * @param string $order rolling order
     */
    public function __construct(string $order)
    {
        $defaultSurfaceNumber = RobotSettings::getSetting("defaultSurfaceNumber");
        $this->defaultSurfaceNumber = $defaultSurfaceNumber ??
            Customization::getCustomSetting("defaultSurfaceNumber");
        $this->order = $order;

        // Default subexpression
        array_push($this->subexpressions,
            new DiceSubexpression("D" . $this->defaultSurfaceNumber));

        $this->parseOrder();
        $this->roll();
    }

    /**
     * Parse out dice type and rolling expression of the rolling order.
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
                $this->bpDiceNumber = intval($result[0]);
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
     * @param string $expression rolling expression
     */
    private function parseExpression(string $expression): void
    {
        // Parse expression. Sample: 3D5+5+2D6k2
        $subexpressions = preg_split("/([+\-Xx*()（）])/", $expression, -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        $preSubexpressions = array();
        $offsetIncrement = 0;

        foreach ($subexpressions as &$subexpression)
        {
            if (is_numeric($subexpression[0]) ||
                preg_match("/^([1-9][0-9]*)?D[1-9][0-9]*(K([1-9][0-9]*)?)?$/i", $subexpression[0]))
            {
                // If subexpression is like 5D100K2 or 1, push to the temp array
                $subexpression[0] = str_replace(array("d", "k"), array("D", "K"), $subexpression[0]);
                $subexpression[1] += $offsetIncrement;

                array_push($preSubexpressions, new DiceSubexpression($subexpression[0], $subexpression[1]));
            }
            elseif (preg_match("/^([1-9][0-9]*)?D(K([1-9][0-9]*)?)?$/i", $subexpression[0]))
            {
                // If subexpression is like 5DK2
                $subexpression[0] = str_replace(array("d", "k"), array("D", "K"), $subexpression[0]);
                $subexpression[1] += $offsetIncrement;  // Change offset

                // Add default surface number after D
                $offsetIncrement += strlen($this->defaultSurfaceNumber);
                $expression = substr_replace($expression, $this->defaultSurfaceNumber,
                    stripos($expression, "D", $subexpression[1]) + 1, 0);
                $subexpression[0] = substr_replace($subexpression[0], $this->defaultSurfaceNumber,
                    strpos($subexpression[0], "D") + 1, 0);

                array_push($preSubexpressions, new DiceSubexpression($subexpression[0], $subexpression[1]));
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
        $this->expression = str_replace(array("（", "）", "d", "k", "x", "X"), array("(", ")", "D", "K", "*", "*"),
            $expression);
    }

    /**
     * Roll several dices determined by subexpressions and calculate summary.
     */
    private function roll(): void
    {
        // B/P dice
        if ($this->bpType)
        {
            // Check range
            if ($this->bpDiceNumber < 1 ||
                $this->bpDiceNumber > Customization::getCustomSetting("maxDiceNumber"))
            {
                $this->success = -1;
                return;
            }

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

        foreach ($this->subexpressions as &$subexpression)
        {
            if (!$subexpression->success)
            {
                $this->success = -1;  // Subexpression is illegal, out of range
                return;
            }
        }

        try
        {
            $evalCommand = "return " . $this->toArithmeticExpression() . ";";
            $this->rollResult = eval($evalCommand);
        }
        catch (Throwable $t)
        {
            $this->success = -2;

            error_log("DiceRobot catch an arithmetic expression error: " . $t->getMessage() . "\n" .
                "Exceptional rolling order: " . $this->order . "\n" .
                "Exceptional rolling expression: " . $this->expression . "\n" .
                "Exceptional command evaluated: " . $this->toArithmeticExpression());
        }
    }

    /**
     * Generate result expression, in which the subexpressions will be replaced with rolling result.
     *
     * @return string result expression
     */
    public function toResultExpression(): string
    {
        $expression = $this->expression;
        $resultExpression = "";
        $tempArray = array();

        foreach ($this->subexpressions as &$subexpression)
        {
            $tempArray = explode($subexpression->subexpression, $expression, 2);

            /** @noinspection PhpUndefinedMethodInspection */
            $resultExpression .= $tempArray[0] . $subexpression->getResultString();

            $expression = $tempArray[1];
        }

        return $resultExpression . $tempArray[1];
    }

    /**
     * Generate arithmetic expression, in which the subexpressions will be replaced with rolling summary.
     *
     * @return string arithmetic expression
     */
    public function toArithmeticExpression(): string
    {
        $expression = $this->expression;
        $arithmeticExpression = "";
        $tempArray = array();

        foreach ($this->subexpressions as &$subexpression)
        {
            $tempArray = explode($subexpression->subexpression, $expression, 2);
            $arithmeticExpression .= $tempArray[0] . $subexpression->rollSummary;
            $expression = $tempArray[1];
        }

        return $arithmeticExpression . $tempArray[1];
    }
}
