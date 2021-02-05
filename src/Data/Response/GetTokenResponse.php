<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class GetTokenResponse
 *
 * DTO. Response of getting token.
 *
 * @package DiceRobot\Data\Response
 */
final class GetTokenResponse extends DiceRobotResponse
{
    /** @var string Access token. */
    public string $token;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->token = (string) $this->data["token"];
    }
}
