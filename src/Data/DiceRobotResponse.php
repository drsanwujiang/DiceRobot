<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Exception\ApiException\UnexpectedErrorException;

/**
 * Class Response
 *
 * DTO. Response of DiceRobot APIs.
 *
 * @package DiceRobot\Data
 */
abstract class DiceRobotResponse
{
    /** @var int Return code */
    public int $code;

    /** @var string Return message */
    public string $message;

    /** @var array Requested data */
    public array $data;

    /**
     * The constructor.
     *
     * @param array $content Return content
     *
     * @throws UnexpectedErrorException
     */
    public function __construct(array $content)
    {
        $this->code = (int) $content["code"];
        $this->message = (string) $content["message"];
        $this->data = (array) ($content["data"] ?? []);

        // Throw custom exception first
        $this->validate();

        // Throw uniform exception
        if ($this->code != 0) {
            throw new UnexpectedErrorException();
        }

        $this->parse();
    }

    /**
     * Validate return code.
     */
    protected function validate(): void {}

    /**
     * Parse data to properties.
     */
    protected function parse(): void {}
}
