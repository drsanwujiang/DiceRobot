<?php

declare(strict_types=1);

namespace DiceRobot\Util;

use DiceRobot\Data\Config;
use DiceRobot\Exception\RuntimeException;
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Swlib\Http\Exception\TransferException;
use Swlib\Saber;

/**
 * Class Updater
 *
 * DiceRobot skeleton updater.
 *
 * @package DiceRobot\Util
 */
class Updater
{
    /** @var array Files to be updated. */
    private const FILES = [
        "root" => [
            "src/Action/Event/README.md",
            "src/Action/Event/Sample.php",
            "src/Action/Message/README.md",
            "src/Action/Message/Sample.php",
            "src/Action/README.md",
            "src/README.md",
            "CHANGELOG.md",
            "composer.json",
            "dicerobot.php",
            "LICENSE",
            "README.md"
        ],
        "config" => [
            "bootstrap.php",
            "config.php",
            "container.php",
            "README.md"
        ],
        "data" => [
            "root" => [
                "README.md"
            ],
            "card" => [
                "README.md"
            ],
            "chat" => [
                "friend/README.md",
                "group/README.md",
                "README.md"
            ],
            "deck" => [
                "README.md",
                "塔罗牌.json"
            ],
            "reference" => [
                "AboutTemplate.json",
                "COCCharacterCardTemplate.json",
                "DNDCharacterCardTemplate.json",
                "HelloTemplate.json",
                "HelpTemplate.json",
                "NameTemplate.json",
                "README.md"
            ],
            "rule" => [
                "0.json",
                "1.json",
                "README.md"
            ],
        ],
        "logs" => [
            "README.md"
        ],
    ];

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** @var Saber File downloader. */
    protected Saber $downloader;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(Config $config, LoggerFactory $loggerFactory)
    {
        $this->config = $config;

        $this->logger = $loggerFactory->create("Updater");

        $this->downloader = Saber::create([
            "base_uri" => $this->config->getString("dicerobot.skeleton.uri"),
            "use_pool" => true,
            "headers" => [
                "Accept-Encoding" => "identity",
                "User-Agent" => "DiceRobot/{$this->config->getString("dicerobot.version")}"
            ],
            "before" => function (Saber\Request $request) {
                $this->logger->debug("Send to {$request->getUri()}, content: {$request->getBody()}");
            },
            "after" => function (Saber\Response $response) {
                $this->logger->debug("Receive from {$response->getUri()}, content: {$response->getBody()}");
            }
        ]);

        $this->logger->debug("Updater created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Updater destructed.");
    }

    /**
     * Update skeleton.
     *
     * @return int Result code.
     */
    public function update(): int
    {
        try {
            $this->updateFiles("", "root", self::FILES["root"]);
            $this->updateFiles("config/", "config", self::FILES["config"]);
            $this->updateFiles("logs/", "log.path", self::FILES["logs"]);
            $this->updateFiles("data/", "data.root", self::FILES["data"]["root"]);
            $this->updateFiles("data/card/", "data.card", self::FILES["data"]["card"]);
            $this->updateFiles("data/chat/", "data.chat", self::FILES["data"]["chat"]);
            $this->updateFiles("data/deck/", "data.deck", self::FILES["data"]["deck"]);
            $this->updateFiles("data/reference/", "data.reference", self::FILES["data"]["reference"]);
            $this->updateFiles("data/rule/", "data.rule", self::FILES["data"]["rule"]);

            return 0;
        } catch (TransferException $e) {
            $this->logger->error("Failed to update for network problem.");

            return -1;
        } catch (RuntimeException $e) {
            $this->logger->error($e);

            return -2;
        }
    }

    /**
     * Update specific files.
     *
     * @param string $uri URI.
     * @param string $dirKey Directory key.
     * @param array $files File list.
     *
     * @throws RuntimeException File cannot be updated
     */
    protected function updateFiles(string $uri, string $dirKey, array $files): void
    {
        $dir = $this->config->getString($dirKey);

        // Check directory
        if (empty($dir)) {
            $this->logger->info("{$dirKey} not set, update skipped.");

            return;
        }

        foreach ($files as $file) {
            $fileName = "{$dir}/{$file}";

            if ($this->downloader->download("{$uri}{$file}", $fileName)->getSuccess()) {
                $this->logger->info("{$fileName} updated.");
            } else {
                throw new RuntimeException("File {$fileName} cannot be updated.");
            }
        }
    }
}
