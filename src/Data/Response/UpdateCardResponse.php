<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;
use DiceRobot\Exception\CharacterCardException\PermissionDeniedException;

/**
 * Class UpdateCardResponse
 *
 * DTO. Response of updating character card.
 *
 * @package DiceRobot\Data\Response
 */
final class UpdateCardResponse extends DiceRobotResponse
{
    /** @var int Current value. */
    public int $currentValue;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->currentValue = (int) $this->data["current_value"];
    }

    /**
     * @inheritDoc
     *
     * @throws PermissionDeniedException User does not have permission to access the character card.
     */
    protected function validate(): void
    {
        if ($this->code == -3) {
            throw new PermissionDeniedException();
        }
    }
}
