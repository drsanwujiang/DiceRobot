<?php

declare(strict_types=1);

namespace DiceRobot\Util;

use Swoole\Coroutine\System;

/**
 * Class Environment
 *
 * Util class. Environment variables container.
 *
 * @package DiceRobot\Util
 */
class Environment
{
    /** @var string Systemctl path. */
    protected static string $systemctl = "/bin/systemctl";

    /** @var string|null Composer path. */
    protected static ?string $composer = null;

    /**
     * Initialize environment parameters.
     */
    public static function initialize(): void
    {
        self::checkComposer();
    }

    /**
     * Check composer path.
     */
    protected static function checkComposer(): void
    {
        $code = -1;

        // Test composer
        foreach (["/usr/local/bin/composer", "/usr/bin/composer"] as $path) {
            extract(System::exec(
                "{$path} --version --no-interaction --no-ansi --quiet 2>&1"
            ), EXTR_OVERWRITE);

            if ($code == 0) {
                self::$composer = $path;

                break;
            }
        }
    }

    /**
     * Get systemctl path.
     *
     * @return string Systemctl path.
     */
    public static function getSystemctl(): string
    {
        return self::$systemctl;
    }

    /**
     * Get composer path.
     *
     * @return string|null Composer path.
     */
    public static function getComposer(): ?string
    {
        return self::$composer;
    }
}
