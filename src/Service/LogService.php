<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Config;
use DiceRobot\Exception\RuntimeException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Util\File;
use Psr\Log\LoggerInterface;

/**
 * Class LogService
 *
 * Log service.
 *
 * @package DiceRobot\Service
 */
class LogService
{
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(Config $config, LoggerFactory $loggerFactory)
    {
        $this->config = $config;

        $this->logger = $loggerFactory->create("Log");

        $this->logger->debug("Log service created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Log service destructed.");
    }

    /**
     * Initialize service.
     *
     * @throws RuntimeException Failed to initialize log service.
     */
    public function initialize(): void
    {
        if ($this->checkDirectory($this->config->getString("log.path"))) {
            $this->logger->info("Log service initialized.");
        } else {
            $this->logger->critical("Failed to initialize log service.");

            throw new RuntimeException("Failed to initialize log service.");
        }
    }

    /**
     * Check lig directory.
     *
     * @param string $dir Log directory.
     *
     * @return bool Success.
     */
    public function checkDirectory(string $dir): bool
    {
        try {
            if (!file_exists($dir)) {
                File::createDirectory($dir);
            }

            File::checkDirectory($dir);
        } catch (RuntimeException $e) {
            $this->logger->error($e);
            $this->logger->critical("Failed to check directory.");

            return false;
        }

        $this->logger->info("Directory checked.");

        return true;
    }

    /**
     * Get log file list.
     *
     * @return string[] Log file list.
     */
    public function getLogList(): array
    {
        $list = [];
        $fileInfo = pathinfo($this->config->getString("log.filename"));

        foreach (File::getFileList($this->config->getString("log.path")) as $log) {
            if (preg_match(
                "/^{$fileInfo["filename"]}-20\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])\.{$fileInfo["extension"]}$/",
                $log
            )) {
                $list[] = $log;
            }
        }

        return $list;
    }

    /**
     * Get log file content.
     *
     * @param string $filename Log file name.
     *
     * @return array|false Parsed log file content, or false if log file not exists.
     */
    public function getLog(string $filename)
    {
        $fileInfo = pathinfo($this->config->getString("log.filename"));

        if (!preg_match(
            "/^{$fileInfo["filename"]}-20\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])\.{$fileInfo["extension"]}$/",
            $filename
        )) {
            return false;
        }

        try {
            $content = File::getFile("{$this->config->getString("log.path")}/{$filename}");
        } catch (RuntimeException $e) {
            return false;
        }

        $logs = [];
        $splitLog = array_map(
            "trim",
            preg_split(
                "/(\[20\d{2}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[1-2]\d|3[0-1]) (?:[0-1]\d|2[0-3]):[0-5]\d:[0-5]\d [+-][0-1]\d:[0-5]\d])/",
                $content,
                -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
            )
        );

        for ($i = 0, $max = count($splitLog) - 1; $i < $max; $i++) {
            if (preg_match("/^\[20\d{2}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[1-2]\d|3[0-1]) (?P<time>(?:[0-1]\d|2[0-3]):[0-5]\d:[0-5]\d) [+-][0-1]\d:[0-5]\d] (?P<channel>[a-zA-Z]+)\.(?P<level>[A-Z]+): (?P<message>[\S\s]+)$/",
                "{$splitLog[$i]} {$splitLog[$i + 1]}", $matches
            )) {
                $logs[] = [
                    "time" => $matches["time"],
                    "channel" => $matches["channel"],
                    "level" => $matches["level"],
                    "message" => $matches["message"]
                ];

                $i++;  // Skip next
            }
        }

        return $logs;
    }
}
