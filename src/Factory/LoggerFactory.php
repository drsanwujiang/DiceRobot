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
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var Logger Logger. */
    protected Logger $logger;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        $this->logger = new Logger("default");

        $this->setHandlers();
    }

    /**
     * Set handlers.
     */
    protected function setHandlers(): void
    {
        $handlers = [];
        $dateFormat = "Y-m-d H:i:s P";
        $formatter = new LineFormatter(null, $dateFormat, true, true);

        // Set console log
        $streamHandler =
            new StreamHandler("php://stdout", $this->config->getInt("log.level.console"));
        $streamHandler->setFormatter($formatter);
        $handlers[] = $streamHandler;

        // Set file log
        if (!empty($path = $this->config->getString("log.path"))) {
            $filenameFormat = "{filename}-{date}";
            $filename = sprintf('%s/%s', $path, $this->config->getString("log.filename"));
            $rotatingFileHandler = new RotatingFileHandler(
                $filename,
                $this->config->getInt("log.maxFiles"),
                $this->config->getInt("log.level.file")
            );

            $rotatingFileHandler->setFilenameFormat($filenameFormat, RotatingFileHandler::FILE_PER_DAY);
            $rotatingFileHandler->setFormatter($formatter);
            $handlers[] = $rotatingFileHandler;
        }

        $this->logger->setHandlers($handlers);
    }

    /**
     * Create a logger with same handlers but different channel.
     *
     * @param string $channel Channel name.
     *
     * @return LoggerInterface The logger.
     */
    public function create(string $channel): LoggerInterface
    {
        return $this->logger->withName($channel);
    }

    /**
     * Reload config.
     */
    public function reload(): void
    {
        $this->setHandlers();
    }
}
