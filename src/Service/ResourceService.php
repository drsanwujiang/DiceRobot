<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Resource\{CardDeck, CharacterCard, ChatSettings, CheckRule, Config, Reference, Statistics};
use DiceRobot\Exception\CardDeckException\NotFoundException as CardDeckNotFoundException;
use DiceRobot\Exception\CharacterCardException\LostException as CharacterCardLostException;
use DiceRobot\Exception\CheckRuleException\LostException as CheckRuleLostException;
use DiceRobot\Exception\FileException\LostException as FileLostException;
use DiceRobot\Exception\RuntimeException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Util\File;
use Psr\Log\LoggerInterface;

/**
 * Class ResourceService
 *
 * Resource service.
 *
 * @package DiceRobot\Service
 */
class ResourceService
{
    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** @var array Resource directories. */
    protected array $directories = [];

    /** @var bool Whether the resources are loaded. */
    protected bool $isLoaded = false;

    /** @var Config Panel config. */
    protected Config $config;

    /** @var Statistics Statistics. */
    protected Statistics $statistics;

    /** @var ChatSettings[][] Chat settings. */
    protected array $chatSettings = [ "friend" => [], "group" => [] ];

    /** @var CharacterCard[] Character cards. */
    protected array $characterCards = [];

    /** @var CheckRule[] Check rules. */
    protected array $checkRules = [];

    /** @var Reference[] References. */
    protected array $references = [];

    /** @var CardDeck[] Card decks. */
    protected array $cardDecks = [];

    /**
     * The constructor.
     *
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->create("Resource");
    }

    /**
     * Initialize resource service.
     *
     * @param \DiceRobot\Data\Config $config DiceRobot config.
     *
     * @throws RuntimeException Failed to initialize resource service.
     */
    public function initialize(\DiceRobot\Data\Config $config): void
    {
        $this->directories = $config->getArray("data");

        if (isset($this->directories["chat"])) {
            $this->directories["chat.friend"] = ($this->directories["chat"] ?? "") . "/friend";
            $this->directories["chat.group"] = ($this->directories["chat"] ?? "") . "/group";
        }

        if ($this->checkDirectories() && $this->loadAll()) {
            $this->logger->notice("Resource service initialized.");
        } else {
            $this->logger->alert("Initialize resource service failed.");

            throw new RuntimeException("Initialize resource service failed");
        }
    }

    /**
     * Check resource directories.
     *
     * @return bool Success.
     */
    public function checkDirectories(): bool
    {
        try {
            foreach ($this->directories as $directory) {
                if (!file_exists($directory)) {
                    File::createDirectory($directory);
                }

                File::checkDirectory($directory);
            }
        } catch (RuntimeException $e) {
            $this->logger->error($e);
            $this->logger->critical("Check directories failed.");

            return false;
        }

        $this->logger->info("Directories checked.");

        return true;
    }

    /**
     * Load all the resources.
     *
     * @return bool Success.
     */
    public function loadAll(): bool
    {
        if ($this->isLoaded && !$this->saveAll()) {
            $this->logger->critical("Reload resources failed.");

            return false;
        }

        try {
            $this->loadConfig();
            $this->loadStatistics();
            $this->loadChatSettings();
            $this->loadCharacterCards();
            $this->loadCheckRules();
            $this->loadReferences();
            $this->loadCardDecks();

            $this->isLoaded = true;
        } catch (RuntimeException $e) {
            $this->logger->error($e);
            $this->logger->critical("Load resources failed.");

            return false;
        }

        $this->logger->info("Resources loaded.");

        return true;
    }

    /**
     * Save all loaded resources.
     *
     * @return bool Success.
     */
    public function saveAll(): bool
    {
        try {
            $this->saveConfig();
            $this->saveStatistics();
            $this->saveChatSettings();
            $this->saveCharacterCards();
        } catch (RuntimeException $e) {
            $this->logger->error($e);
            $this->logger->critical("Save resources failed.");

            return false;
        }

        $this->logger->info("Resources saved.");

        return true;
    }

    /**
     * Load panel config.
     */
    protected function loadConfig(): void
    {
        if (isset($this->directories["root"])) {
            try {
                $this->config = new Config(File::getFile("{$this->directories["root"]}/config.json"));
            } catch (RuntimeException $e) {
                $this->config = new Config([]);
            }
        } else {
            $this->config = new Config([]);
        }
    }

    /**
     * Load statistics.
     */
    protected function loadStatistics(): void
    {
        if (isset($this->directories["root"])) {
            try {
                $this->statistics = new Statistics(File::getFile("{$this->directories["root"]}/statistics.json"));
            } catch (RuntimeException $e) {
                $this->statistics = new Statistics([]);
            }
        } else {
            $this->statistics = new Statistics([]);
        }
    }

    /**
     * Load chat settings.
     *
     * @throws RuntimeException
     */
    protected function loadChatSettings(): void
    {
        if (isset($this->directories["chat"])) {
            foreach (["friend", "group"] as $type) {
                $d = dir($this->directories["chat.{$type}"]);

                while (false !== $f = $d->read()) {
                    if (preg_match("/^([1-9][0-9]{4,9}).json$/", $f, $matches)) {
                        $this->chatSettings[$type][(int) $matches[1]] =
                            new ChatSettings(File::getFile("{$this->directories["chat.{$type}"]}/{$f}"));
                    }
                }

                $d->close();
            }
        }
    }

    /**
     * Load character cards.
     *
     * @throws RuntimeException
     */
    protected function loadCharacterCards(): void
    {
        if (isset($this->directories["card"])) {
            $d = dir($this->directories["card"]);

            while (false !== $f = $d->read()) {
                if (preg_match("/^([1-9][0-9]{0,5}).json$/", $f, $matches)) {
                    $this->characterCards[(int) $matches[1]] =
                        new CharacterCard(File::getFile("{$this->directories["card"]}/{$f}"));
                }
            }

            $d->close();
        }
    }

    /**
     * Load check rules.
     *
     * @throws RuntimeException
     */
    protected function loadCheckRules(): void
    {
        if (isset($this->directories["rule"])) {
            $d = dir($this->directories["rule"]);

            while (false !== $f = $d->read()) {
                if (preg_match("/^([0-9]{1,2}).json$/", $f, $matches)) {
                    $this->checkRules[(int) $matches[1]] =
                        new CheckRule(File::getFile("{$this->directories["rule"]}/{$f}"));
                }
            }

            $d->close();
        }
    }

    /**
     * Load references.
     *
     * @throws RuntimeException
     */
    protected function loadReferences(): void
    {
        if (isset($this->directories["reference"])) {
            $d = dir($this->directories["reference"]);

            while (false !== $f = $d->read()) {
                if (preg_match("/^([a-zA-z]+).json$/", $f, $matches)) {
                    $this->references[$matches[1]] =
                        new Reference(File::getFile("{$this->directories["reference"]}/{$f}"));
                }
            }

            $d->close();
        }
    }

    /**
     * Load card decks.
     *
     * @throws RuntimeException
     */
    protected function loadCardDecks(): void
    {
        if (isset($this->directories["deck"])) {
            $d = dir($this->directories["deck"]);

            while (false !== $f = $d->read()) {
                if (preg_match("/^(.+?).json$/", $f, $matches)) {
                    $this->cardDecks[$matches[1]] =
                        new CardDeck(File::getFile("{$this->directories["deck"]}/{$f}"));
                }
            }

            $d->close();
        }
    }

    /**
     * Save panel config.
     *
     * @throws RuntimeException
     */
    protected function saveConfig(): void
    {
        if (isset($this->directories["root"])) {
            File::putFile("{$this->directories["root"]}/config.json", (string) $this->config);
        }
    }

    /**
     * Save statistics.
     *
     * @throws RuntimeException
     */
    protected function saveStatistics(): void
    {
        if (isset($this->directories["root"])) {
            File::putFile("{$this->directories["root"]}/statistics.json", (string) $this->statistics);
        }
    }

    /**
     * Save chat settings.
     *
     * @throws RuntimeException
     */
    protected function saveChatSettings(): void
    {
        if (isset($this->directories["chat"])) {
            foreach (["friend", "group"] as $type) {
                foreach ($this->chatSettings[$type] as $chatId => $chatSettings) {
                    File::putFile("{$this->directories["chat.{$type}"]}/{$chatId}.json", (string) $chatSettings);
                }
            }
        }
    }

    /**
     * Save character cards.
     *
     * @throws RuntimeException
     */
    protected function saveCharacterCards(): void
    {
        if (isset($this->directories["card"])) {
            foreach ($this->characterCards as $cardId => $card) {
                File::putFile("{$this->directories["card"]}/{$cardId}.json", (string) $card);
            }
        }
    }

    /**
     * Set character card.
     *
     * @param int $cardId Character card ID.
     * @param CharacterCard $card Character card.
     */
    public function setCharacterCard(int $cardId, CharacterCard $card): void
    {
        $this->characterCards[$cardId] = $card;
    }

    /**
     * Get panel config.
     *
     * @return Config Panel config.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get statistics.
     *
     * @return Statistics Statistics.
     */
    public function getStatistics(): Statistics
    {
        return $this->statistics;
    }

    /**
     * Get chat settings.
     *
     * @param string $chatType Chat type.
     * @param int $chatId Chat ID.
     *
     * @return ChatSettings Chat settings.
     */
    public function getChatSettings(string $chatType, int $chatId): ChatSettings
    {
        if ($chatType == "group" || $chatType == "friend") {
            if (!isset($this->chatSettings[$chatType][$chatId])) {
                $this->chatSettings[$chatType][$chatId] = new ChatSettings();
            }

            return $this->chatSettings[$chatType][$chatId];
        }

        return new ChatSettings();
    }

    /**
     * Get character card.
     *
     * @param int $cardId Character card ID.
     *
     * @return CharacterCard Character card.
     *
     * @throws CharacterCardLostException Character card file cannot be found.
     */
    public function getCharacterCard(int $cardId): CharacterCard
    {
        if (!isset($this->characterCards[$cardId])) {
            throw new CharacterCardLostException();
        }

        return $this->characterCards[$cardId];
    }

    /**
     * Get check rule.
     *
     * @param int $ruleId Check rule ID.
     *
     * @return CheckRule Check rule.
     *
     * @throws CheckRuleLostException Check rule file cannot be found.
     */
    public function getCheckRule(int $ruleId): CheckRule
    {
        if (!isset($this->checkRules[$ruleId])) {
            throw new CheckRuleLostException();
        }

        return $this->checkRules[$ruleId];
    }

    /**
     * Get reference.
     *
     * @param string $referenceKey Reference key.
     *
     * @return Reference Reference.
     *
     * @throws FileLostException Reference file cannot be found.
     */
    public function getReference(string $referenceKey): Reference
    {
        if (!isset($this->references[$referenceKey])) {
            throw new FileLostException();
        }

        return $this->references[$referenceKey];
    }

    /**
     * Get corresponding card deck of the public deck.
     *
     * @param string $publicDeckKey Public deck key.
     *
     * @return CardDeck Card deck.
     *
     * @throws CardDeckNotFoundException Card deck file cannot be found.
     */
    public function getCardDeck(string $publicDeckKey): CardDeck
    {
        $deck = CardDeck::getCardDeck($publicDeckKey);

        if (is_null($deck)) {
            throw new CardDeckNotFoundException();
        }

        // Clone deck to prevent source deck from being modified
        return clone $deck;
    }
}
