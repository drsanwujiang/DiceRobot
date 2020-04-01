<?php
namespace DiceRobot\Service;

use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use Exception;

final class IOService
{
    /**
     * Get file content.
     *
     * @param string $path File path
     *
     * @return array File content
     *
     * @throws FileLostException
     * @throws JSONDecodeException
     */
    public static function getFile(string $path): array
    {
        if (!file_exists($path))
            throw new FileLostException();

        try
        {
            $jsonString = file_get_contents($path);
            $jsonArray = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (Exception $e)
        {
            throw new JSONDecodeException();
        }

        return $jsonArray;
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
