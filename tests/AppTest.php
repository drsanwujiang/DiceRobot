<?php

declare(strict_types=1);

namespace DiceRobot\Tests;

use DiceRobot\App;
use DiceRobot\Enum\AppStatusEnum;

use function Co\run;

class AppTest extends TestCase
{
    public function testInitialize(): void
    {
        run(function () {
            $container = $this->getContainer();
            /** @var App $app */
            $app = $container->get(App::class);

            $app->initialize();

            $this->assertEquals(AppStatusEnum::HOLDING(), $app->getStatus());

            $app->stop();
        });
    }
}
