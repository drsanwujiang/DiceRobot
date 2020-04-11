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
     * Create directory.
     *
     * @param string $path Directory path
     *
     * @throws FileUnwritableException
     */
    public static function createDir(string $path): void
    {
        // Parent directory is not writable
        if (!is_writable(dirname($path)) || false === mkdir($path, 0755))
            throw new FileUnwritableException(
                "The directory '{$path}' cannot be created. " .
                "Please check the permission and make sure it has been granted."
            );
    }

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
        // File or directory is not writable
        if (file_exists($path) && !is_writable($path))
            throw new FileUnwritableException(
                "The file '{$path}' exists but is not writable. " .
                "Please check the permission and make sure it has been granted."
            );
        elseif (!file_exists($path) && !is_writable(dirname($path)))
            throw new FileUnwritableException(
                "The file '{$path}' cannot be created. " .
                "Please check the permission and make sure it has been granted."
            );

        $jsonString = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Other writing errors
        if (false === file_put_contents($path, $jsonString))
            throw new FileUnwritableException(
                "The file '{$path}' cannot be created. " .
                "Please check the permission and make sure it has been granted."
            );
    }
}
