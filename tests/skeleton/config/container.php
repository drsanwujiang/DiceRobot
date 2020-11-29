<?php

declare(strict_types=1);

use DiceRobot\Data\CustomConfig;

return [
    CustomConfig::class => function (): CustomConfig {
        return new CustomConfig(require __DIR__ . "/config.php");
    },
];
