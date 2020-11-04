<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Traits\ArrayReaderTrait;
use Psr\Container\ContainerInterface;

use const DiceRobot\DEFAULT_CONFIG;

/**
 * Class Config
 *
 * DTO. DiceRobot config.
 *
 * @package DiceRobot\Data
 */
class Config
{
    use ArrayReaderTrait;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container
     * @param Resource\Config|null $config
     */
    public function __construct(ContainerInterface $container, Resource\Config $config = null)
    {
        /** @var CustomConfig $customConfig */
        $customConfig = $container->make(CustomConfig::class);

        if ($config) {
            $this->__constructArrayReader(array_replace_recursive(
                DEFAULT_CONFIG,
                $config->all(),
                $customConfig->all()
            ));
        } else {
            $this->__constructArrayReader(array_replace_recursive(
                DEFAULT_CONFIG,
                $customConfig->all()
            ));
        }
    }
}
