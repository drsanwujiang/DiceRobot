<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class GetNicknameResponse
 *
 * DTO. Response of getting robot nickname.
 *
 * @package DiceRobot\Data\Response
 */
class GetNicknameResponse extends DiceRobotResponse
{
    /** @var string Robot's nickname. */
    public string $nickname;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->nickname = (string) $this->data["nickname"];
    }
}
