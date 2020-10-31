<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Data\Contact\{Friend, Group, Robot};
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;

/**
 * Class RobotService
 *
 * Robot service.
 *
 * @package DiceRobot\Service
 */
class RobotService
{
    /** @var LoggerInterface Logger */
    protected LoggerInterface $logger;

    /** @var ApiService API service */
    protected ApiService $api;

    /** @var Robot Robot */
    protected Robot $robot;

    /** @var Friend[] Friend list */
    protected array $friends = [];

    /** @var Group[] Group list */
    protected array $groups = [];

    /** @var int Friends count */
    protected int $friendsCount = 0;

    /** @var int Groups count */
    protected int $groupsCount = 0;

    /**
     * The constructor.
     *
     * @param LoggerFactory $loggerFactory
     * @param ApiService $api
     * @param Configuration $config
     */
    public function __construct(LoggerFactory $loggerFactory, ApiService $api, Configuration $config)
    {
        $this->logger = $loggerFactory->create("Robot");
        $this->api = $api;
        $this->robot = new Robot();
        $this->robot->id = $config->getInt("mirai.robot.id");
        $this->robot->nickname = "Unknown";
        $this->robot->authKey = $config->getString("mirai.robot.authKey");
    }

    /**
     * Update robot service.
     *
     * @return bool
     */
    public function update(): bool
    {
        try {
            // Update lists
            $this->updateFriends($this->api->getFriendList()->all());
            $this->updateGroups($this->api->getGroupList()->all());
            $this->updateNickname($this->api->getNickname($this->getId())->nickname);

            // Report to DiceRobot API
            $this->api->updateRobotAsync($this->getId());

            $this->logger->info("Robot updated.");

            return true;
        } catch (MiraiApiException | InternalErrorException | NetworkErrorException | UnexpectedErrorException $e) {  // TODO: catch (MiraiApiException | InternalErrorException | NetworkErrorException | UnexpectedErrorException) in PHP 8
            $this->logger->alert("Update robot failed, unable to call Mirai API.");

            return false;
        }
    }

    /**
     * @param string $nickname
     */
    public function updateNickname(string $nickname): void
    {
        $this->robot->nickname = $nickname;
    }

    /**
     * @param array $friends
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

        $this->friendsCount = count($friends);
    }

    /**
     * @param array $groups
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

        $this->groupsCount = count($groups);
    }

    /**
     * @param int $friendId
     *
     * @return bool
     */
    public function hasFriend(int $friendId): bool
    {
        return array_key_exists($friendId, $this->friends);
    }

    /**
     * @param int $groupId
     *
     * @return bool
     */
    public function hasGroup(int $groupId): bool
    {
        return array_key_exists($groupId, $this->groups);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->robot->id;
    }

    /**
     * @return string
     */
    public function getNickname(): string
    {
        return $this->robot->nickname;
    }

    /**
     * @return string
     */
    public function getAuthKey(): string
    {
        return $this->robot->authKey;
    }

    /**
     * @param int $friendId
     *
     * @return Friend|null
     */
    public function getFriend(int $friendId): ?Friend
    {
        return $this->friends[$friendId] ?? null;
    }

    /**
     * @param int $groupId
     *
     * @return Group|null
     */
    public function getGroup(int $groupId): ?Group
    {
        return $this->groups[$groupId] ?? null;
    }

    /**
     * @return int
     */
    public function getFriendsCount(): int
    {
        return $this->friendsCount;
    }

    /**
     * @return int
     */
    public function getGroupsCount(): int
    {
        return $this->groupsCount;
    }
}
