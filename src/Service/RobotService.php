<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Contact\{Friend, Group, Robot};
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
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->robot = new Robot();
        $this->robot->id = $config->getInt("mirai.robot.id");
        $this->robot->nickname = "Unknown";
        $this->robot->authKey = $config->getString("mirai.robot.authKey");
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
        foreach ($friends as $friend)
        {
            $_friend = new Friend();
            $_friend->id = $friend["id"] ?? 0;
            $_friend->nickname = $friend["nickname"] ?? "";
            $_friend->remark = $friend["remark"] ?? "";

            $this->friends[$_friend->id] = $_friend;
        }

        $this->friendsCount = count($friends);
    }

    /**
     * @param array $groups
     */
    public function updateGroups(array $groups): void
    {
        foreach ($groups as $group)
        {
            $_group = new Group();
            $_group->id = $group["id"] ?? 0;
            $_group->name = $group["name"] ?? "";
            $_group->permission = $group["permission"] ?? "";

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
        return $this->friends[$friendId] ?? NULL;
    }

    /**
     * @param int $groupId
     *
     * @return Group|null
     */
    public function getGroup(int $groupId): ?Group
    {
        return $this->groups[$groupId] ?? NULL;
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
