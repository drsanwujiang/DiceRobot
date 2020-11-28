<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Config;
use DiceRobot\Data\Contact\{Friend, Group, Robot};
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Class RobotService
 *
 * Robot service.
 *
 * @package DiceRobot\Service
 */
class RobotService
{
    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** @var Robot Robot. */
    protected Robot $robot;

    /** @var Friend[] Friend list. */
    protected array $friends = [];

    /** @var Group[] Group list. */
    protected array $groups = [];

    /** @var int Count of friends. */
    protected int $friendCount = 0;

    /** @var int Count of groups. */
    protected int $groupCount = 0;

    /**
     * The constructor.
     *
     * @param LoggerFactory $loggerFactory Logger factory.
     * @param ApiService $api API service.
     */
    public function __construct(LoggerFactory $loggerFactory, ApiService $api)
    {
        $this->api = $api;

        $this->logger = $loggerFactory->create("Robot");
    }

    /**
     * Initialize robot service.
     *
     * @param Config $config DiceRobot config.
     */
    public function initialize(Config $config): void
    {
        $this->robot = new Robot();
        $this->robot->id = $config->getInt("mirai.robot.id");
        $this->robot->nickname = "Unknown";
        $this->robot->authKey = $config->getString("mirai.robot.authKey");
    }

    /**
     * Update robot service.
     *
     * @return bool Success.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function update(): bool
    {
        try {
            // Update lists
            $this->updateFriends($this->api->getFriendList()->all());
            $this->updateGroups($this->api->getGroupList()->all());

            // Update robot
            $this->updateNickname($this->api->getNickname($this->getId())->nickname);
            $this->updateVersion($this->api->about()->getString("data.version"));

            // Report to DiceRobot API
            $this->api->updateRobotAsync($this->getId());

            $this->logger->info("Robot updated.");

            return true;
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->alert("Failed to update robot, unable to call Mirai API.");
        } catch (InternalErrorException | NetworkErrorException | UnexpectedErrorException $e) {  // TODO: catch (InternalErrorException | NetworkErrorException | UnexpectedErrorException) in PHP 8
            $this->logger->alert("Failed to update robot, unable to call DiceRobot API.");
        }

        return false;
    }

    /**
     * Update robot's nickname
     *
     * @param string $nickname Robot's nickname.
     */
    public function updateNickname(string $nickname): void
    {
        $this->robot->nickname = $nickname;
    }

    /**
     * Update version of Mirai API HTTP plugin.
     *
     * @param string $version Plugin version.
     */
    public function updateVersion(string $version): void
    {
        $this->robot->version = $version;
    }

    /**
     * Update friend list.
     *
     * @param array $friends Friend list.
     */
    public function updateFriends(array $friends): void
    {
        foreach ($friends as $friend) {
            $_friend = new Friend();
            $_friend->id = (int) ($friend["id"] ?? 0);
            $_friend->nickname = (string) ($friend["nickname"] ?? "");
            $_friend->remark = (string) ($friend["remark"] ?? "");

            $this->friends[$_friend->id] = $_friend;
        }

        $this->friendCount = count($friends);
    }

    /**
     * Update group list.
     *
     * @param array $groups Group list.
     */
    public function updateGroups(array $groups): void
    {
        foreach ($groups as $group) {
            $_group = new Group();
            $_group->id = (int) ($group["id"] ?? 0);
            $_group->name = (string) ($group["name"] ?? "");
            $_group->permission = (string) ($group["permission"] ?? "");

            $this->groups[$_group->id] = $_group;
        }

        $this->groupCount = count($groups);
    }

    /**
     * Test whether the friend exists.
     *
     * @param int $friendId Friend ID.
     *
     * @return bool Existence.
     */
    public function hasFriend(int $friendId): bool
    {
        return array_key_exists($friendId, $this->friends);
    }

    /**
     * Test whether the group exists.
     *
     * @param int $groupId Group ID.
     *
     * @return bool Existence.
     */
    public function hasGroup(int $groupId): bool
    {
        return array_key_exists($groupId, $this->groups);
    }

    /**
     * Get robot's ID.
     *
     * @return int Robot's ID.
     */
    public function getId(): int
    {
        return $this->robot->id;
    }

    /**
     * Get robot's nickname.
     *
     * @return string Robot's nickname.
     */
    public function getNickname(): string
    {
        return $this->robot->nickname;
    }

    /**
     * Get robot's Authorization key of Mirai API HTTP plugin.
     *
     * @return string Authorization key.
     */
    public function getAuthKey(): string
    {
        return $this->robot->authKey;
    }

    /**
     * Get robot's friend.
     *
     * @param int $friendId Friend ID.
     *
     * @return Friend|null Friend, or null if it does not exist.
     */
    public function getFriend(int $friendId): ?Friend
    {
        return $this->friends[$friendId] ?? null;
    }

    /**
     * Get robot's group.
     *
     * @param int $groupId Group ID.
     *
     * @return Group|null Group, or null if it does not exist.
     */
    public function getGroup(int $groupId): ?Group
    {
        return $this->groups[$groupId] ?? null;
    }

    /**
     * Get the count of friends.
     *
     * @return int Count of friends.
     */
    public function getFriendCount(): int
    {
        return $this->friendCount;
    }

    /**
     * Get the count of groups.
     *
     * @return int Count of groups.
     */
    public function getGroupCount(): int
    {
        return $this->groupCount;
    }
}
