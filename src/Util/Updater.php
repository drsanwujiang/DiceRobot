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
        "data.root" => [
            "README.md"
        ],
        "data.card" => [
            "README.md"
        ],
        "data.chat" => [
            "friend/README.md",
            "group/README.md",
            "README.md"
        ],
        "data.deck" => [
            "塔罗牌.json",
            "README.md"
        ],
        "data.reference" => [
            "AboutTemplate.json",
            "COCCharacterCardTemplate.json",
            "DNDCharacterCardTemplate.json",
            "HelloTemplate.json",
            "HelpTemplate.json",
            "NameTemplate.json",
            "README.md"
        ],
        "data.rule" => [
            "0.json",
            "1.json",
            "README.md"
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
     * @param array $files Files to be updated.
     *
     * @return int Result code.
     */
    public function update(array $files): int
    {
        try {
            $this->updateFiles($files, "", "root", "root");
            $this->updateFiles($files, "config/", "config", "config");
            $this->updateFiles($files, "logs/", "log.path", "logs");
            $this->updateFiles($files, "data/", "data.root", "data.root");
            $this->updateFiles($files, "data/card/", "data.card", "data.card");
            $this->updateFiles($files, "data/chat/", "data.chat", "data.chat");
            $this->updateFiles($files, "data/deck/", "data.deck", "data.deck");
            $this->updateFiles($files, "data/reference/", "data.reference", "data.reference");
            $this->updateFiles($files, "data/rule/", "data.rule", "data.rule");

            $this->logger->notice("Skeleton updated.");

            return 0;
        } catch (TransferException $e) {
            $this->logger->error("Failed to update skeleton for network problem.");
            $this->logger->warning("Skeleton update interrupted.");

            return -1;
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->warning("Skeleton update interrupted.");

            return -2;
        }
    }

    /**
     * Update specific files.
     *
     * @param array $files Files to be updated.
     * @param string $uri URI.
     * @param string $dirKey Directory key.
     * @param string $groupKey Group key.
     *
     * @throws RuntimeException File cannot be updated
     */
    protected function updateFiles(array &$files, string $uri, string $dirKey, string $groupKey): void
    {
        $dir = $this->config->getString($dirKey);

        // Check directory
        if (empty($dir)) {
            $this->logger->warning("Directory {$dirKey} not set, update skipped.");

            return;
        }

        $items = $files[$groupKey] ?? [];

        foreach ($items as $item) {
            // Only accept files in the file list
            if (in_array($item, self::FILES[$groupKey])) {
                $fileName = "{$dir}/{$item}";

                if ($this->downloader->download("{$uri}{$item}", $fileName)->getSuccess()) {
                    $this->logger->info("{$fileName} updated.");
                } else {
                    throw new RuntimeException("File {$fileName} cannot be updated.");
                }
            }
        }
    }
}
