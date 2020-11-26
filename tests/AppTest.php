<?php

declare(strict_types=1);

namespace DiceRobot\Tests;

use DiceRobot\App;
use DiceRobot\Enum\AppStatusEnum;

class AppTest extends TestCase
{
    public function testInitialize(): void
    {
        $container = $this->getContainer();
        $app = $container->get(App::class);

        $app->initialize();

        $this->assertEquals(AppStatusEnum::HOLDING(), $app->getStatus());

        $app->stop();
    }
}
