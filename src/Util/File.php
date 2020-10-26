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
     * @param string $path Directory path
     *
     * @throws RuntimeException
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
     * @param string $path Directory path
     *
     * @throws RuntimeException
     */
    public static function createDirectory(string $path): void
    {
        // Parent directory is not writable
        if (!is_writable(dirname($path)) || false === mkdir($path, 0755)) {
            throw new RuntimeException("Directory {$path} cannot be created.");
        }
    }

    /**
     * Get file content.
     *
     * @param string $path File path
     *
     * @return array File content
     *
     * @throws RuntimeException
     */
    public static function getFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File {$path} does not exist.");
        }

        if (false === $jsonString = file_get_contents($path)) {
            throw new RuntimeException("File {$path} exists but cannot be read.");
        }

        if (!is_array($content = json_decode($jsonString, true))) {
            throw new RuntimeException("File {$path} exists but cannot be parsed.");
        }

        return $content;
    }

    /**
     * Write content to the file.
     *
     * @param string $path File path
     * @param string $content File content
     *
     * @throws RuntimeException
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
}
