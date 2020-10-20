<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\Response;

/**
 * Class GetNicknameResponse
 *
 * DTO. Response of getting robot nickname.
 *
 * @package DiceRobot\Data\Response
 */
class GetNicknameResponse extends Response
{
    /** @var string Robot nickname */
    public string $nickname;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->nickname = (string) $this->data["nickname"];
    }
}
