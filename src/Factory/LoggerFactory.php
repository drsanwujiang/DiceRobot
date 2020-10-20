<?php

declare(strict_types=1);

namespace DiceRobot\Factory;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\{RotatingFileHandler, StreamHandler};
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;

/**
 * Class LoggerFactory
 *
 * The factory of PSR-3 logger interface.
 *
 * @package DiceRobot\Factory
 */
class LoggerFactory
{
    /** @var Logger Logger */
    protected Logger $logger;

    /**
     * The constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $filenameFormat = "{filename}-{date}";
        $dateFormat = "Y-m-d H:i:s P";
        $filename = sprintf('%s/%s', $config->getString("log.path"), $config->getString("log.filename"));

        $formatter = new LineFormatter(null, $dateFormat, false, true);

        $streamHandler = new StreamHandler("php://stdout", $config->getInt("log.level.console"));
        $streamHandler->setFormatter($formatter);

        $rotatingFileHandler = new RotatingFileHandler($filename, 0, $config->getInt("log.level.file"));
        $rotatingFileHandler->setFilenameFormat($filenameFormat, RotatingFileHandler::FILE_PER_DAY);
        $rotatingFileHandler->setFormatter($formatter);

        $logger = new Logger("default");
        $logger->pushHandler($streamHandler);
        $logger->pushHandler($rotatingFileHandler);

        $this->logger = $logger;
    }

    /**
     * Create a logger with same handlers but different channel.
     *
     * @param string $channel Channel name
     *
     * @return LoggerInterface The logger
     */
    public function create(string $channel): LoggerInterface
    {
        return $this->logger->withName($channel);
    }
}
