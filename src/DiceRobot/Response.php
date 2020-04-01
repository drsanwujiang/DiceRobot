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
     * Get the reply.
     *
     * @return string|null The reply. String when event type is message, null when event type is others
     */
    public function getReply(): ?string
    {
        return $this->reply;
    }

    /**
     * Get the flag indicating the need to at message sender.
     *
     * @return bool The flag
     */
    public function getAtSender(): bool
    {
        return $this->atSender;
    }

    /**
     * Get the flag indicating whether intercept this event and not let other plugins handle it.
     *
     * @return bool The flag
     */
    public function getBlock(): bool
    {
        return $this->block;
    }

    /**
     * Get HTTP code.
     *
     * @return int HTTP code
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
