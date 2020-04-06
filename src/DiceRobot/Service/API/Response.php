<?php
namespace DiceRobot\Service\API;

use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use Exception;

/**
 * The response of DiceRobot APIs.
 */
abstract class Response
{
    public int $code;
    public string $message;
    public array $data;

    /**
     * The constructor.
     *
     * @param string $jsonString JSON string
     *
     * @throws JSONDecodeException
     * @throws UnexpectedErrorException
     */
    public function __construct(string $jsonString)
    {
        $content = $this->decode($jsonString);

        $this->code = $content["code"];
        $this->message = $content["message"];
        $this->data = $content["data"] ?? [];

        $this->validate();
        $this->parse();
    }

    /**
     * JSON decoder.
     *
     * @param string $jsonString JSON string
     *
     * @return array JSON array
     *
     * @throws JSONDecodeException
     */
    private function decode(string $jsonString): array
    {
        try
        {
            $content = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (Exception $e)
        {
            throw new JSONDecodeException();
        }

        return $content;
    }

    /**
     * Validate the response. This method is overrideable.
     *
     * @throws UnexpectedErrorException
     */
    protected function validate(): void
    {
        if ($this->code != 0)
            $this->logError($this->code, $this->message);
    }

    /**
     * Child class can override this method to parse data, if necessary.
     */
    protected function parse():void {}

    /**
     * Log error.
     *
     * @param int $code
     * @param string $message
     *
     * @throws UnexpectedErrorException
     */
    protected function logError(int $code, string $message): void
    {
        throw new UnexpectedErrorException($code, $message, static::class);
    }
}
