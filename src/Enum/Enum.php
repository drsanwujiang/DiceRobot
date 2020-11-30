<?php

declare(strict_types=1);

namespace DiceRobot\Enum;

/**
 * Class Enum
 *
 * Base Enum class.
 *
 * @package DiceRobot\Enum
 */
abstract class Enum extends \MyCLabs\Enum\Enum
{
    /**
     * Compare this enum and the variable. Note: this function will not check types, only compare values.
     *
     * @param Enum $variable Enum.
     *
     * @return bool Is less than $variable.
     */
    public function lessThan(Enum $variable): bool
    {
        return $this->getValue() < $variable->getValue();
    }

    /**
     * Compare this enum and the variable. Note: this function will not check types, only compare values.
     *
     * @param Enum $variable Enum.
     *
     * @return bool Is greater than $variable.
     */
    public function greaterThan(Enum $variable): bool
    {
        return $this->getValue() > $variable->getValue();
    }
}
