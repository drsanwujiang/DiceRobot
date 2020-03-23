<?php
namespace DiceRobot\Base;

/**
 * Class DiceSubexpression
 *
 * Container of minimum subexpression of a rolling expression.
 */
final class DiceSubexpression
{
    /** @var string Subexpression */
    public string $subexpression;

    /** @var int Offset of subexpression in the parent expression */
    private int $offset = 0;

    /** @var int Subexpression type. 0: Constant, 1: Normal dice expression, 2: K dice, take several maximum */
    private int $type;

    /** @var int Constant expression value */
    private int $constant;

    /** @var int Dice number */
    private int $diceNumber = 1;

    /** @var int Dice surface number */
    private int $surfaceNumber = 100;

    /** @var int K dice number */
    private int $kNumber = 1;

    /** @var array Rolling result */
    private array $rollResult;

    /** @var int Rolling result summary */
    public int $rollSummary;

    /** @var bool Success flag */
    public bool $success = true;

    /**
     * DiceSubexpression constructor.
     *
     * @param string $subexpression Rolling subexpression
     * @param int $offset Offset of subexpression in rolling expression
     */
    public function __construct(string $subexpression, int $offset = 0)
    {
        $this->subexpression = $subexpression;
        $this->offset = $offset;

        $this->parseExpression();
        $this->checkRange();
        $this->roll();
    }

    /**
     * Parse out dice type, dice number, dice surface number, K number of this subexpression.
     */
    private function parseExpression(): void
    {
        if (is_numeric($this->subexpression))
        {
            $this->type = 0;
            $this->constant = intval($this->subexpression);
        }
        elseif (preg_match("/^([1-9][0-9]*)?D[1-9][0-9]*$/", $this->subexpression) == 1)
        {
            $this->type = 1;
            $orderArray = explode("D", $this->subexpression, 2);
            $this->diceNumber = $orderArray[0] == "" ? 1 : intval($orderArray[0]);
            $this->surfaceNumber = $orderArray[1];
        }
        elseif (preg_match("/K([1-9][0-9]*)?$/i", $this->subexpression) == 1)
        {
            $this->type = 2;
            $orderArray = preg_split("/([DK])/", $this->subexpression);
            $this->diceNumber = $orderArray[0] == "" ? 1 : intval($orderArray[0]);
            $this->surfaceNumber = $orderArray[1];
            $this->kNumber = $orderArray[2] == "" ? 1 : intval($orderArray[2]);
        }
    }

    /**
     * Check the range of dice number and dice surface number.
     */
    private function checkRange(): void
    {
        if ($this->diceNumber < 1 ||
            $this->diceNumber > Customization::getCustomSetting("maxDiceNumber") ||
            $this->surfaceNumber < 1 ||
            $this->surfaceNumber > Customization::getCustomSetting("maxSurfaceNumber") ||
            $this->kNumber > $this->diceNumber)
            $this->success = false;
    }

    /**
     * Roll a dice determined by this subexpression and calculate summary.
     */
    private function roll(): void
    {
        if ($this->type == 0)
        {
            $this->rollResult = array(intval($this->subexpression));
            $this->rollSummary = intval($this->subexpression);
        }
        elseif ($this->type == 1)
        {
            $this->rollResult = Rolling::roll($this->diceNumber, $this->surfaceNumber);
            $this->rollSummary = array_sum($this->rollResult);
        }
        elseif ($this->type == 2)
        {
            $this->rollResult = Rolling::roll($this->diceNumber, $this->surfaceNumber);

            for ($i = count($this->rollResult); $i > $this->kNumber; $i--)
                array_splice($this->rollResult, array_search(min($this->rollResult), $this->rollResult),
                             1);

            $this->rollSummary = array_sum($this->rollResult);
        }
    }

    /**
     * Generate result string with all points joint by plus sign.
     *
     * @return string Result string
     */
    public function getResultString(): string
    {
        if (count($this->rollResult) == 1)
            return $this->rollResult[0];

        return "(" . join("+", $this->rollResult) . ")";
    }
}
