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
    /** @var int Current value */
    public int $afterValue;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->afterValue = (int) $this->data["after_value"];
    }

    /**
     * @inheritDoc
     *
     * @throws PermissionDeniedException
     */
    protected function validate(): void
    {
        if ($this->code == -1012) {
            throw new PermissionDeniedException();
        }
    }
}
