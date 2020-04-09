<?php
namespace DiceRobot\Service\Container;

use DiceRobot\Exception\InformativeException\CharacterCardException\NotBoundException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\IOService;

/**
 * The chat settings.
 */
class ChatSettings
{
    private static string $configDir;

    private string $settingsDir;
    private string $settingsPath;
    private array $settings;

    /**
     * The constructor.
     *
     * @param string $chatType Chat type
     * @param int $chatId Chat ID
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     */
    public function __construct(string $chatType, int $chatId)
    {
        $this->settingsDir = self::$configDir . $chatType . "/";
        $this->settingsPath = $this->settingsDir . $chatId . ".json";

        $this->load();
    }

    /**
     * Set the dir of chat settings files.
     *
     * @param string $dir The dir
     */
    public static function setDir(string $dir): void
    {
        self::$configDir = $dir;
    }

    /**
     * Load chat settings.
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     */
    private function load(): void
    {
        if (!file_exists($this->settingsDir))
            IOService::createDir($this->settingsDir);

        if (file_exists($this->settingsPath))
            $this->settings = IOService::getFile($this->settingsPath);

        $this->settings ??= [];
    }

    /**
     * Save chat settings.
     *
     * @throws FileUnwritableException
     */
    private function save(): void
    {
        ksort($this->settings);
        IOService::putFile($this->settingsPath, $this->settings);
    }

    /**
     * Get chat setting.
     *
     * @param string $key Setting key
     *
     * @return mixed|null Setting value
     */
    public function get(string $key)
    {
        return $this->settings[$key] ?? NULL;
    }

    /**
     * Set chat setting.
     *
     * @param string $key Setting key
     * @param mixed|null $value Setting value
     *
     * @throws FileUnwritableException
     */
    public function set(string $key, $value): void
    {
        if (is_null($value))
            unset($this->settings[$key]);
        else
            $this->settings[$key] = $value;

        $this->save();
    }

    /**
     * Get user nickname.
     *
     * @param string $userId User ID
     *
     * @return string|null User nickname
     */
    public function getNickname(string $userId): ?string
    {
        return $this->settings["nicknames"][$userId] ?? NULL;
    }

    /**
     * Set user nickname.
     *
     * @param string $userId User ID
     * @param string|null $nickname User nickname
     *
     * @throws FileUnwritableException
     */
    public function setNickname(string $userId, ?string $nickname): void
    {
        if (is_null($nickname))
            unset($this->settings["nicknames"][$userId]);
        else
            $this->settings["nicknames"][$userId] = $nickname;

        $this->save();
    }

    /**
     * Get user's character card ID.
     *
     * @param string $userId User ID
     *
     * @return int Character card ID
     *
     * @throws NotBoundException
     */
    public function getCharacterCardId(string $userId): int
    {
        $cardId = $this->settings["characterCards"][$userId] ?? NULL;

        if (empty($cardId))
            throw new NotBoundException();

        return $cardId;
    }

    /**
     * Set user's character card ID.
     *
     * @param string $userId User ID
     * @param int|null $cardId Character card ID
     *
     * @throws FileUnwritableException
     */
    public function setCharacterCardId(string $userId, ?int $cardId): void
    {
        if (is_null($cardId))
            unset($this->settings["characterCards"][$userId]);
        else
            $this->settings["characterCards"][$userId] = $cardId;

        $this->save();
    }
}
