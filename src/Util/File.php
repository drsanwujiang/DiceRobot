<?php

declare(strict_types=1);

namespace DiceRobot\Util;

use DiceRobot\Exception\RuntimeException;

/**
 * Class File
 *
 * Util class. File reader and writer.
 *
 * @package DiceRobot\Util
 */
class File
{
    /**
     * Check if the directory is readable.
     *
     * @param string $path Directory path.
     *
     * @throws RuntimeException Directory is unreadable.
     */
    public static function checkDirectory(string $path): void
    {
        if (!is_readable($path)) {
            throw new RuntimeException("Directory {$path} exists but cannot be read.");
        }
    }

    /**
     * Create directory.
     *
     * @param string $path Directory path.
     *
     * @throws RuntimeException Parent directory is unwritable.
     */
    public static function createDirectory(string $path): void
    {
        // Parent directory is not writable
        if (!is_writable(dirname($path)) || false === mkdir($path, 0755)) {
            throw new RuntimeException("Directory {$path} cannot be created.");
        }
    }

    /**
     * Get file list of specific directory.
     *
     * @param string $path Directory path.
     *
     * @return string[] File list.
     *
     * @throws RuntimeException Directory is unreadable.
     */
    public static function getFileList(string $path): array
    {
        self::checkDirectory($path);

        if (false === $files = scandir($path)) {
            throw new RuntimeException("Get file list in {$path} failed.");
        }

        return array_diff($files, ["..", "."]);
    }

    /**
     * Get file content.
     *
     * @param string $path File path.
     *
     * @return string File content.
     *
     * @throws RuntimeException Failed to get the file.
     */
    public static function getFile(string $path): string
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File {$path} does not exist.");
        }

        if (false === $content = file_get_contents($path)) {
            throw new RuntimeException("File {$path} exists but cannot be read.");
        }

        return $content;
    }

    /**
     * Write content to the file.
     *
     * @param string $path File path.
     * @param string $content File content.
     *
     * @throws RuntimeException Failed to put the file.
     */
    public static function putFile(string $path, string $content): void
    {
        // File or directory is not writable
        if (file_exists($path) && !is_writable($path)) {
            throw new RuntimeException("File {$path} exists but is not writable.");
        } elseif (!file_exists($path) && !is_writable(dirname($path))) {
            throw new RuntimeException("File {$path} cannot be created, for directory unwritable.");
        }

        // Other writing errors
        if (false === file_put_contents($path, $content)) {
            throw new RuntimeException("File {$path} cannot be created, for other reason.");
        }
    }

    /**
     * Get JSON file content.
     *
     * @param string $path File path.
     *
     * @return array JSON array.
     *
     * @throws RuntimeException Failed to get the file.
     */
    public static function getJsonFile(string $path): array
    {
        $content = self::getFile($path);

        // Try to decode the file
        if (!is_array($json = json_decode($content, true))) {
            // Try to decode as UTF-8 BOM
            if (!is_array($json = json_decode(ltrim($content, "\xEF\xBB\xBF"), true))) {
                throw new RuntimeException("File {$path} exists but cannot be parsed.");
            }
        }

        return $json;
    }
}
