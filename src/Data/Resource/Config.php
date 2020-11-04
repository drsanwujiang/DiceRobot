<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;

/**
 * Class Config
 *
 * Resource container. DiceRobot config.
 *
 * @package DiceRobot\Data\Resource
 */
class Config extends Resource
{
    /**
     * Set config.
     *
     * @param array $config
     *
     * @return bool
     */
    public function setConfig(array $config): bool
    {
        if (!$this->checkConfig($config)) {
            return false;
        }

        $this->data = array_replace_recursive($this->data, $config);

        return true;
    }

    /**
     * Check whether the config is valid.
     *
     * @param array $config
     *
     * @return bool
     */
    protected function checkConfig(array $config): bool
    {
        // Only accept "strategy", "order", "reply" and "errMsg"
        foreach ($config as $key => $value) {
            if (!is_array($value)) {
                return false;
            }

            if ($key === "strategy") {
                foreach ($value as $sKey => $sValue) {
                    if (!is_bool($sValue)) {
                        return false;
                    }
                }
            } elseif ($key === "order") {
                foreach ($value as $oKey => $oValue) {
                    if (!is_int($oValue)) {
                        return false;
                    }
                }
            } elseif ($key === "reply") {
                foreach ($value as $rKey => $rValue) {
                    if (!is_string($rValue)) {
                        return false;
                    }
                }
            } elseif ($key === "errMsg") {
                foreach ($value as $eKey => $eValue) {
                    if (!is_string($eValue)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }
}
