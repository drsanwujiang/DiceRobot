<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Resource\{CharacterCard, ChatSettings, CheckRule, Reference, Statistics};
use DiceRobot\Exception\RuntimeException;
use DiceRobot\Exception\CharacterCardException\LostException as CharacterCardLostException;
use DiceRobot\Exception\CheckRuleException\LostException as CheckRuleLostException;
use DiceRobot\Exception\FileException\LostException as FileLostException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Util\File;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;

/**
 * Class ResourceService
 *
 * Resource service.
 *
 * @package DiceRobot\Service
 */
class ResourceService
{
    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var array */
    protected array $directories;

    /** @var CharacterCard[] */
    protected array $characterCards = [];

    /** @var ChatSettings[][] */
    protected array $chatSettings = [ "friend" => [], "group" => [] ];

    /** @var Reference[] */
    protected array $references = [];

    /** @var CheckRule[] */
    protected array $checkRules = [];

    /** @var Statistics */
    protected Statistics $statistics;

    /**
     * The constructor.
     *
     * @param LoggerFactory $loggerFactory
     * @param Configuration $config
     */
    public function __construct(LoggerFactory $loggerFactory, Configuration $config)
    {
        $this->logger = $loggerFactory->create("Resource");
        $this->directories = $config->getArray("data");
        $this->directories["config.friend"] = $this->directories["config"] . "/friend";
        $this->directories["config.group"] = $this->directories["config"] . "/group";
    }

    /**
     * Initialize resource service.
     *
     * @return bool
     */
    public function initialize(): bool
    {
        if ($this->checkDirectories() && $this->loadAll())
        {
            $this->logger->notice("Resource service initialized.");

            return true;
        }
        else
        {
            $this->logger->alert("Initialize resource service failed.");

            return false;
        }
    }

    /**
     * Check resource directories.
     *
     * @return bool
     */
    public function checkDirectories(): bool
    {
        try
        {
            foreach ($this->directories as $directory)
            {
                if (!file_exists($directory))
                    File::createDirectory($directory);

                File::checkDirectory($directory);
            }
        }
        catch (RuntimeException $e)
        {
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
     * @return bool
     */
    public function loadAll(): bool
    {
        try
        {
            $this->loadCharacterCards();
            $this->loadChatSettings();
            $this->loadReferences();
            $this->loadCheckRules();
            $this->loadStatistics();
        }
        catch (RuntimeException $e)
        {
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
     * @return bool
     */
    public function saveAll(): bool
    {
        try
        {
            $this->saveCharacterCards();
            $this->saveChatSettings();
            //$this->saveReferences();
            //$this->saveCheckRules();
            $this->saveStatistics();
        }
        catch (RuntimeException $e)
        {
            $this->logger->error($e);
            $this->logger->critical("Save resources failed.");

            return false;
        }

        $this->logger->info("Resources saved.");

        return true;
    }

    /**
     * Load character cards.
     *
     * @throws RuntimeException
     */
    protected function loadCharacterCards(): void
    {
        $d = dir($this->directories["card"]);

        while (false !== $f = $d->read())
            if (preg_match("/^([1-9][0-9]{0,5}).json/", $f, $matches))
                $this->characterCards[(int) $matches[1]] =
                    new CharacterCard(File::getFile("{$this->directories["card"]}/{$f}"));

        $d->close();
    }

    /**
     * Load chat settings.
     *
     * @throws RuntimeException
     */
    protected function loadChatSettings(): void
    {
        foreach (["friend", "group"] as $type)
        {
            $d = dir($this->directories["config.{$type}"]);

            while (false !== $f = $d->read())
                if (preg_match("/^([1-9][0-9]{4,9}).json/", $f, $matches))
                    $this->chatSettings[$type][(int) $matches[1]] =
                        new ChatSettings(File::getFile("{$this->directories["config.{$type}"]}/{$f}"));

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
        $d = dir($this->directories["reference"]);

        while (false !== $f = $d->read())
            if (preg_match("/^([a-zA-z]+).json/", $f, $matches))
                $this->references[$matches[1]] =
                    new Reference(File::getFile("{$this->directories["reference"]}/{$f}"));

        $d->close();
    }

    /**
     * Load check rules.
     *
     * @throws RuntimeException
     */
    protected function loadCheckRules(): void
    {
        $d = dir($this->directories["rule"]);

        while (false !== $f = $d->read())
            if (preg_match("/^([0-9]{1,2}).json/", $f, $matches))
                $this->checkRules[(int) $matches[1]] =
                    new CheckRule(File::getFile("{$this->directories["rule"]}/{$f}"));

        $d->close();
    }

    /**
     * Load statistics.
     */
    protected function loadStatistics(): void
    {
        try
        {
            $this->statistics = new Statistics(File::getFile("{$this->directories["config"]}/statistics.json"));
        }
        catch (RuntimeException $e)
        {
            $this->statistics = new Statistics([]);
        }
    }

    /**
     * Save character cards.
     *
     * @throws RuntimeException
     */
    protected function saveCharacterCards(): void
    {
        foreach ($this->characterCards as $cardId => $card)
            File::putFile("{$this->directories["card"]}/{$cardId}.json", (string) $card);
    }

    /**
     * Save chat settings.
     *
     * @throws RuntimeException
     */
    protected function saveChatSettings(): void
    {
        foreach (["friend", "group"] as $type)
            foreach ($this->chatSettings[$type] as $chatId => $chatSettings)
                File::putFile("{$this->directories["config.{$type}"]}/{$chatId}.json", (string) $chatSettings);
    }

    /**
     * Save references.
     *
     * @throws RuntimeException
     */
    protected function saveReferences(): void
    {
        foreach ($this->references as $name => $reference)
            File::putFile("{$this->directories["reference"]}/{$name}.json", (string) $reference);
    }

    /**
     * Save check rules.
     *
     * @throws RuntimeException
     */
    protected function saveCheckRules(): void
    {
        foreach ($this->checkRules as $ruleId => $rule)
            File::putFile("{$this->directories["rule"]}/{$ruleId}.json", (string) $rule);
    }

    /**
     * Save statistics.
     *
     * @throws RuntimeException
     */
    protected function saveStatistics(): void
    {
        File::putFile("{$this->directories["config"]}/statistics.json", (string) $this->statistics);
    }

    /**
     * Set character card.
     *
     * @param int $cardId
     * @param CharacterCard $card
     */
    public function setCharacterCard(int $cardId, CharacterCard $card): void
    {
        $this->characterCards[$cardId] = $card;
    }

    /**
     * Get character card.
     *
     * @param int $cardId
     *
     * @return CharacterCard
     *
     * @throws CharacterCardLostException
     */
    public function getCharacterCard(int $cardId): CharacterCard
    {
        if (!isset($this->characterCards[$cardId]))
            throw new CharacterCardLostException();

        return $this->characterCards[$cardId];
    }

    /**
     * Get chat settings.
     *
     * @param string $chatType
     * @param int $chatId
     *
     * @return ChatSettings
     */
    public function getChatSettings(string $chatType, int $chatId): ChatSettings
    {
        if ($chatType == "group" || $chatType == "friend")
        {
            if (!isset($this->chatSettings[$chatType][$chatId]))
                $this->chatSettings[$chatType][$chatId] = new ChatSettings();

            return $this->chatSettings[$chatType][$chatId];
        }

        return new ChatSettings();
    }

    /**
     * Get reference.
     *
     * @param string $referenceKey
     *
     * @return Reference
     *
     * @throws FileLostException
     */
    public function getReference(string $referenceKey): Reference
    {
        if (!isset($this->references[$referenceKey]))
            throw new FileLostException();

        return $this->references[$referenceKey];
    }

    /**
     * Get check rule.
     *
     * @param int $ruleId
     *
     * @return CheckRule
     *
     * @throws CheckRuleLostException
     */
    public function getCheckRule(int $ruleId): CheckRule
    {
        if (!isset($this->checkRules[$ruleId]))
            throw new CheckRuleLostException();

        return $this->checkRules[$ruleId];
    }

    /**
     * Get statistics.
     *
     * @return Statistics
     */
    public function getStatistics(): Statistics
    {
        return $this->statistics;
    }
}
