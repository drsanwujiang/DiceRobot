<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;
use DiceRobot\Exception\CharacterCardException\PermissionDeniedException;

/**
 * Class SanityCheckResponse
 *
 * DTO. Response of sanity check.
 *
 * @package DiceRobot\Data\Response
 */
final class SanityCheckResponse extends DiceRobotResponse
{
    /** @var bool Check success. */
    public bool $checkSuccess;

    /** @var int Previous sanity. */
    public int $previousSanity;

    /** @var int Current sanity. */
    public int $currentSanity;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->checkSuccess = (bool) $this->data["check_success"];
        $this->previousSanity = (int) $this->data["previous_value"];
        $this->currentSanity = (int) $this->data["after_value"];
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
