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
    public int $beforeSanity;

    /** @var int Current sanity. */
    public int $afterSanity;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->checkSuccess = (bool) $this->data["check_success"];
        $this->beforeSanity = (int) $this->data["before_sanity"];
        $this->afterSanity = (int) $this->data["after_sanity"];
    }

    /**
     * @inheritDoc
     *
     * @throws PermissionDeniedException User does not have permission to access the character card.
     */
    protected function validate(): void
    {
        if ($this->code == -1024) {
            throw new PermissionDeniedException();
        }
    }
}
