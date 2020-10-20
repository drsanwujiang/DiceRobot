<?php

declare(strict_types=1);

use Selective\Config\Configuration;

use const DiceRobot\DEFAULT_CONFIG;

return [
    Configuration::class => function (): Configuration {
        return new Configuration(
            (array) array_replace_recursive(DEFAULT_CONFIG, require __DIR__ . "/config.php")
        );
    },
];
