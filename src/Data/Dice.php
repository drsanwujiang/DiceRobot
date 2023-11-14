<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Util\Random;
use Throwable;

/**
 * Class Dice
 *
 * Generalized dice, which is actually a complex dicing expression.
 *
 * @package DiceRobot\Data
 */
class Dice
{
    /** @var int Maximum dice number. */
    protected static int $maxDiceNumber;

    /** @var string[] B/P dice type wording. */
    protected static array $bpDiceType;

    /** @var int Default dice surface number. */
    public int $defaultSurfaceNumber;

    /** @var string Order. */
    public string $order;

    /** @var string|null Visibility type of dicing. H: Private, S: Only display final result. */
    public ?string $vType = null;

    /** @var string|null Bonus/Punishment dice type. B: Bonus dice, P: Punishment dice. */
    public ?string $bpType = null;

    /** @var int Bonus/Punishment dice number. */
    public int $bpDiceNumber;

    /** @var int[] Bonus/Punishment dicing result. */
    public array $bpResult;

    /** @var string Dicing expression. */
    public string $expression;

    /** @var string[] Split dicing expressions. */
    public array $expressions;

    /** @var Subexpression[] Subexpressions. */
    public array $subexpressions;

    /** @var string Dicing reason. */
    public string $reason = "";

    /** @var int Dicing result. */
    public int $result;

    /**
     * Initialize dice.
     *
     * @param Config $config DiceRobot config.
     */
    public static function initialize(Config $config): void
    {
        static::$maxDiceNumber = $config->getInt("order.maxDiceNumber");
        static::$bpDiceType = $config->getArray("wording.bpDiceType");
    }

    /**
     * The constructor.
     *
     * @param string $order Dicing order.
     * @param int $defaultSurfaceNumber Default dice surface number.
     *
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    public function __construct(string $order = "", int $defaultSurfaceNumber = 100)
    {
        $this->order = $order;
        $this->defaultSurfaceNumber = $defaultSurfaceNumber < 1 ? 100 : $defaultSurfaceNumber;
        // Set default expression
        $this->expression = "D{$this->defaultSurfaceNumber}";
        $this->expressions[] = "D{$this->defaultSurfaceNumber}";
        // Set default subexpression
        $this->subexpressions[] = new Subexpression("1D{$this->defaultSurfaceNumber}");

        $this->parseOrder();
        $this->getResult();
    }

    /**
     * Clone dice, and regenerate result.
     *
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws ExpressionInvalidException
     */
    public function __clone()
    {
        foreach ($this->subexpressions as &$subexpression) {
            $subexpression = clone $subexpression;
        }

        $this->getResult();
    }

    /**
     * Parse the order to dice type and dicing expression.
     *
     * @throws DiceNumberOverstepException
     * @throws ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function parseOrder(): void
    {
        preg_match("/^([hs])?\s*([bp])?(.*)\s*$/i", $this->order, $matches);
        $this->vType = empty($matches[1]) ? null : strtoupper($matches[1]);
        $this->bpType = empty($matches[2]) ? null : strtoupper($matches[2]);
        $order = (string) ($matches[3] ?? "");

        if ($this->bpType) {
            // Bonus/Punishment dice
            preg_match("/^([1-9]\d*)?\s*([\S\s]*)$/i", $order, $matches);
            $this->bpDiceNumber = empty($matches[1]) ? 1 : (int) $matches[1];
            $this->reason = $matches[2];
        } else {
            // Normal dice, parse expressions. Sample: x1Dy1Kz1+x2Dy2+c reason
            preg_match("/^([\ddk+\-x*()（）]+)?\s*([\S\s]*)$/i", $order, $matches);
            // Replace Chinese brackets, d, k, x and X
            $expression = str_replace(["（", "）", "x", "X"], ["(", ")", "*", "*"], $matches[1]);
            $this->reason = $matches[2];

            // "0" will be recognized as empty
            if (!empty($expression) || is_numeric($expression)) {
                $this->parseExpression($expression);
            }
        }
    }

    /**
     * Parse dicing expression to several subexpressions.
     *
     * @param string $expression Dicing expression.
     *
     * @throws DiceNumberOverstepException
     * @throws ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function parseExpression(string $expression): void
    {
        // Check expression
        if (preg_match("/([dk+\-*])\\1+/i", $expression) ||  // Duplicated symbol
            substr_count($expression, "(") != substr_count($expression, ")")  // Parentheses not equal
        ) {
            $this->reason = $this->order;

            return;
        }

        // Parse expression. Sample: 3D5+5+2D6k2
        $expressions = preg_split(
            "/((?:[1-9]\d*)?D(?:[1-9]\d*)?(?:K(?:[1-9]\d*)?)?)/i",
            $expression,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );
        $subexpressions = [];

        foreach ($expressions as $index => &$splitExpression) {
            // Screen out subexpression (xDy or xDyKz)
            if (preg_match(
                "/^([1-9]\d*)?D([1-9]\d*)?(K([1-9]\d*)?)?$/i",
                $splitExpression,
                $matches
            )) {
                $diceNumber = empty($matches[1]) ? "1" : $matches[1];
                $surfaceNumber = empty($matches[2]) ? $this->defaultSurfaceNumber : $matches[2];
                $kNumber = empty($matches[4]) ? "1" : $matches[4];
                $filledExpression = "{$diceNumber}D{$surfaceNumber}";
                $splitExpression = ($diceNumber == "1" ? "" : $diceNumber) . "D{$surfaceNumber}";

                if (!empty($matches[3])) {
                    $filledExpression .= "K{$kNumber}";
                    $splitExpression .= "K{$kNumber}";
                }

                $subexpressions[$index] = new Subexpression($filledExpression);
            } elseif (!preg_match("/^[0-9+\-*()]+$/", $splitExpression)) {
                $this->reason = $this->order;

                return;
            }
        }

        $this->subexpressions = $subexpressions;
        $this->expressions = $expressions;
        $this->expression = join($expressions);
    }

    /**
     * Get result according to the dice type.
     *
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws ExpressionInvalidException
     */
    protected function getResult(): void
    {
        if ($this->bpType) {
            $this->bp();
        } else {
            $this->calculate();
        }
    }

    /**
     * Roll bonus/punishment dices and get result.
     *
     * @throws DiceNumberOverstepException Dice number exceeds the maximum.
     */
    protected function bp(): void
    {
        // Check range
        if ($this->bpDiceNumber < 1 || $this->bpDiceNumber > static::$maxDiceNumber) {
            throw new DiceNumberOverstepException();
        }

        $this->bpResult = Random::generate($this->bpDiceNumber, 10);
        $this->result = $this->subexpressions[0]->result;
        $tensPlace = intdiv($this->result, 10);

        if ($this->bpType === "B" && $tensPlace > min($this->bpResult)) {
            $this->result -= ($tensPlace - min($this->bpResult)) * 10;
        } elseif ($this->bpType === "P" && $tensPlace < max($this->bpResult)) {
            $this->result += (max($this->bpResult) - $tensPlace) * 10;
        }

        $this->result = min($this->result, 100);  // Prevent result from over range
    }

    /**
     * Eval arithmetic expression to calculate result.
     *
     * @throws ExpressionErrorException Expression cannot be successfully evaluated.
     * @throws ExpressionInvalidException Expression contains characters other than permitted characters.
     */
    protected function calculate(): void
    {
        $arithmeticExpression = $this->toArithmeticExpression();

        // Rest of the possible invalid expression
        if (preg_match("/^\)|^\*|\d\(|[+\-*]\)|\)\d|\($|[+\-*]$/", $arithmeticExpression)) {
            throw new ExpressionInvalidException();
        }

        try {
            $command = "return {$arithmeticExpression};";
            $this->result = eval($command);
        } catch (Throwable $t) {
            throw new ExpressionErrorException(
                $t->getMessage(),
                $this->order,
                $this->expression,
                $arithmeticExpression
            );
        }
    }

    /**
     * Generate result expression, in which the subexpressions will be replaced with joint dicing results.
     *
     * @return string Result expression.
     */
    protected function toResultExpression(): string
    {
        $expressions = $this->expressions;

        foreach ($this->subexpressions as $index => $subexpression) {
            $expressions[$index] = $subexpression->getResultString();
        }

        return join($expressions);
    }

    /**
     * Generate arithmetic expression, in which the subexpressions will be replaced with dicing result.
     *
     * @return string Arithmetic expression.
     */
    protected function toArithmeticExpression(): string
    {
        $expressions = $this->expressions;

        foreach ($this->subexpressions as $index => $subexpression) {
            $expressions[$index] = $subexpression->result;
        }

        return join($expressions);
    }

    /**
     * Get easily readable expression (replace * with ×).
     *
     * @return string Expression.
     */
    public function getExpression(): string
    {
        return str_replace("*", "×", $this->expression);
    }

    /**
     * Get easily readable result expression (replace * with ×).
     *
     * @return string Result expression.
     */
    public function getResultExpression(): string
    {
        return str_replace("*", "×", $this->toResultExpression());
    }

    /**
     * Get easily readable arithmetic expression (replace * with ×).
     *
     * @return string Arithmetic expression.
     */
    public function getArithmeticExpression(): string
    {
        return str_replace("*", "×", $this->toArithmeticExpression());
    }

    /**
     * Get complete expression according to dice type.
     *
     * @param bool $simplify Forcedly simplify the result.
     *
     * @return string Complete expression.
     */
    public function getCompleteExpression(bool $simplify = false): string
    {
        if (!$this->bpType) {
            // Normal dice
            $completeExpression = $expression = $this->expression;

            if ($this->vType === "S" || $simplify) {
                // Simplest result
                $completeExpression .= "={$this->result}";
            } else {
                // Full result
                $resultExpression = $this->toResultExpression();
                $arithmeticExpression = $this->toArithmeticExpression();

                $completeExpression .= $expression == $resultExpression ? "" : "={$resultExpression}";
                $completeExpression .= $resultExpression == $arithmeticExpression ? "" : "={$arithmeticExpression}";
                $completeExpression .= $arithmeticExpression == $this->result ? "" : "={$this->result}";
            }
        } else {
            // B/P dice
            $completeExpression = "{$this->bpType}{$this->bpDiceNumber}";

            if ($this->vType === "S" || $simplify) {
                // Simplest result
                $completeExpression .= "={$this->result}";
            } else {
                // Full result
                $completeExpression .=
                    "={$this->toResultExpression()}" .
                    "[" . static::$bpDiceType[$this->bpType] . ":" . join(" ", $this->bpResult) . "]" .
                    "={$this->result}";
            }
        }

        return str_replace("*", "×", $completeExpression);
    }
}
