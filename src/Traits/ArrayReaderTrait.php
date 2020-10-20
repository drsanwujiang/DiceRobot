<?php

declare(strict_types=1);

namespace DiceRobot\Traits;

/**
 * Trait ArrayReaderTrait
 *
 * The array reader trait.
 *
 * This trait is a simple version of ArrayReader in selective/array-reader.
 *
 * @package DiceRobot\Traits
 *
 * @author Daniel Opitz <d.opitz@outlook.com>
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/selective-php/array-reader
 */
trait ArrayReaderTrait
{
    /** @var array The data */
    protected array $data;

    /**
     * The constructor.
     *
     * @param array $data Data
     */
    protected function __constructArrayReader(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return all data as array.
     *
     * @return array The data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Test whether or not a given path exists in $data.
     *
     * @param string $path The path to check for
     *
     * @return bool The existence of path
     */
    public function has(string $path): bool
    {
        $keys = explode(".", $path);
        $value = $this->data;

        foreach ($keys as $key)
        {
            if (!array_key_exists($key, $value))
                return false;

            $value = $value[$key];
        }

        return true;
    }

    /**
     * Get value.
     *
     * @param string $path The path
     *
     * @return mixed|null The value
     */
    public function get(string $path)
    {
        $keys = explode(".", $path);
        $value = $this->data;

        foreach ($keys as $key)
        {
            if (!isset($value[$key]))
                return NULL;

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Get value as integer.
     *
     * @param string $key The key
     * @param int $default The default value
     *
     * @return int The value
     */
    public function getInt(string $key, int $default = -1): int
    {
        return (int) ($this->get($key) ?? $default);
    }

    /**
     * Get value as string.
     *
     * @param string $key The key
     * @param string $default The default value
     *
     * @return string The value
     */
    public function getString(string $key, string $default = ""): string
    {
        return (string) ($this->get($key) ?? $default);
    }

    /**
     * Get value as array.
     *
     * @param string $key The key
     * @param array $default The default value
     *
     * @return array The value
     */
    public function getArray(string $key, array $default = []): array
    {
        return (array) ($this->get($key) ?? $default);
    }

    /**
     * Get value as float.
     *
     * @param string $key The key
     * @param float|null $default The default value
     *
     * @return float The value
     */
    public function getFloat(string $key, float $default = -1.0): float
    {
        return (float) ($this->get($key) ?? $default);
    }

    /**
     * Get value as boolean.
     *
     * @param string $key The key
     * @param bool $default The default value
     *
     * @return bool The value
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) ($this->get($key) ?? $default);
    }
}
