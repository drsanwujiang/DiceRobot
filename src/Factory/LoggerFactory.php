<?php

declare(strict_types=1);

namespace DiceRobot\Factory;

use DiceRobot\Data\Config;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\{RotatingFileHandler, StreamHandler};
use Psr\Log\LoggerInterface;

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

    /** @var StreamHandler Stream handler */
    protected StreamHandler $streamHandler;

    /** @var RotatingFileHandler Rotating file handler */
    protected RotatingFileHandler $rotatingFileHandler;

    /**
     * The constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $filenameFormat = "{filename}-{date}";
        $dateFormat = "Y-m-d H:i:s P";

        $this->logger = new Logger("default");
        $formatter = new LineFormatter(null, $dateFormat, false, true);

        $this->streamHandler =
            new StreamHandler("php://stdout", $config->getInt("log.level.console"));
        $this->streamHandler->setFormatter($formatter);
        $this->logger->pushHandler($this->streamHandler);

        if (!empty($path = $config->getString("log.path"))) {
            $filename = sprintf('%s/%s', $path, $config->getString("log.filename"));
            $this->rotatingFileHandler =
                new RotatingFileHandler($filename, 0, $config->getInt("log.level.file"));
            $this->rotatingFileHandler->setFilenameFormat($filenameFormat, RotatingFileHandler::FILE_PER_DAY);
            $this->rotatingFileHandler->setFormatter($formatter);
            $this->logger->pushHandler($this->rotatingFileHandler);
        }
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

    /**
     * Reload config.
     *
     * @param Config $config
     */
    public function reload(Config $config): void
    {
        $this->streamHandler->setLevel($config->getInt("log.level.console"));

        if (isset($this->rotatingFileHandler)) {
            $this->rotatingFileHandler->setLevel($config->getInt("log.level.file"));
        }
    }
}
