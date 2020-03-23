<?php
namespace DiceRobot;

/**
 * Response.
 */
abstract class Response
{
    protected ?string $reply = NULL;
    protected bool $atSender = false;
    protected bool $block = true;
    protected int $httpCode = 200;

    /**
     * @return string|null Reply. String when event type is message, null when event type is others.
     */
    public function getReply(): ?string
    {
        return $this->reply;
    }

    /**
     * @return bool Flag indicating the need of at message sender.
     */
    public function getAtSender(): bool
    {
        return $this->atSender;
    }

    /**
     * @return bool Flag indicating whether intercept this event and not let other plugins handle it.
     */
    public function getBlock(): bool
    {
        return $this->block;
    }

    /**
     * @return int HTTP code.
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
