<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;

use const DiceRobot\DEFAULT_CONFIG;

/**
 * Class Config
 *
 * Resource container. Panel config.
 *
 * @package DiceRobot\Data\Resource
 */
class Config extends Resource
{
    /** @var string[] Acceptable groups. */
    private const ACCEPTABLE_GROUPS = [ "panel", "strategy", "order", "reply", "errMsg" ];

    /**
     * Set config.
     *
     * @param array $config Config data.
     *
     * @return bool Success.
     */
    public function setConfig(array $config): bool
    {
        $data = array_replace_recursive($this->data, $config);

        if (!$this->check($data)) {
            return false;
        }

        $this->data = $data;

        return true;
    }

    /**
     * Check groups and values.
     *
     * @param array $config Config data.
     *
     * @return bool Success.
     */
    protected function check(array &$config): bool
    {
        foreach ($config as $group => $items) {
            // Only accept ACCEPTABLE_GROUPS
            if (!in_array($group, self::ACCEPTABLE_GROUPS) || !is_array($items)) {
                return false;
            }

            $default = DEFAULT_CONFIG[$group];

            foreach ($items as $item => $value) {
                // Can NOT add item, new and default value must be of the same type
                if (!array_key_exists($item, $default) || gettype($value) != gettype($default[$item])) {
                    return false;
                }

                // Reset
                if ($value === $default[$item] || is_null($value)) {
                    unset($config[$group][$item]);
                }
            }
        }

        return true;
    }
}
