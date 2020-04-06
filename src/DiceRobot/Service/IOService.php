<?php
namespace DiceRobot\Service;

use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use Exception;

/**
 * The IO service.
 */
class IOService
{
    /**
     * Get file content.
     *
     * @param string $path File path
     *
     * @return array File content
     *
     * @throws FileDecodeException
     * @throws FileLostException
     */
    public static function getFile(string $path): array
    {
        if (!file_exists($path))
            throw new FileLostException();

        try
        {
            $jsonString = file_get_contents($path);
            $content = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (Exception $e)
        {
            throw new FileDecodeException();
        }

        return $content;
    }

    /**
     * Write content to the file.
     *
     * @param string $path File path
     * @param array $content File content
     *
     * @throws FileUnwritableException
     */
    public static function putFile(string $path, array $content): void
    {
        if (!is_writable($path))
            throw new FileUnwritableException();

        $jsonString = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents($path, $jsonString);
    }
}
