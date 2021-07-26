<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Contact\{Friend, Group, Bot};
use DiceRobot\Data\Contact\Profile\{BotProfile, FriendProfile, MemberProfile};
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

    /** @var Bot Bot. */
    protected Bot $bot;

    /** @var BotProfile Bot profile. */
    protected BotProfile $profile;

    /** @var string[] Bot nickname in each group. */
    protected array $nicknames;

    /** @var Friend[] Friend list. */
    protected array $friends = [];

    /** @var Group[] Group list. */
    protected array $groups = [];

    /** @var FriendProfile[] Friend profile list. */
    protected array $friendProfiles = [];

    /** @var MemberProfile[] Group member profile list. */
    protected array $memberProfiles = [];

    /** @var int Friend count. */
    protected int $friendCount = 0;

    /** @var int Group count. */
    protected int $groupCount = 0;

    /**
     * The constructor.
     *
     * @param ApiService $api API service.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(ApiService $api, LoggerFactory $loggerFactory)
    {
        $this->api = $api;

        $this->logger = $loggerFactory->create("Robot");

        $this->logger->debug("Robot service created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Robot service destructed.");
    }

    /**
     * Initialize service.
     */
    public function initialize(): void
    {
        $this->bot = new Bot();
        $this->bot->id = 0;
        $this->bot->version = "Unknown";
        $this->profile = new BotProfile();
        $this->updateProfile([]);

        $this->logger->info("Robot service initialized.");
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
            $this->updateFriends($this->api->getFriendList()->getArray("data"));
            $this->updateGroups($this->api->getGroupList()->getArray("data"));

            // Update bot and profile
            $this->updateBot(
                $this->api->getSessionInfo()->getInt("data.qq.id"),
                $this->api->about()->getString("data.version")
            );
            $this->updateProfile($this->api->getBotProfile()->all());

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
     * Update bot.
     *
     * @param int $id Bot ID.
     * @param string $version Mirai API HTTP plugin version.
     */
    public function updateBot(int $id, string $version): void
    {
        $this->bot->id = $id;
        $this->bot->version = (string) preg_replace("/[a-zA-z]+/", "", $version);
    }

    /**
     * Update bot profile.
     *
     * @param array $profile Bot profile.
     */
    public function updateProfile(array $profile): void
    {
        $this->profile->nickname = $profile["nickname"] ?? "Unknown";
        $this->profile->email = $profile["email"] ?? "";
        $this->profile->age = $profile["age"] ?? 0;
        $this->profile->level = $profile["level"] ?? 0;
        $this->profile->sign = $profile["sign"] ?? "";
        $this->profile->sex = $profile["sex"] ?? "";
    }

    /**
     * Update robot nicknames cache of the group.
     *
     * @param int $groupId Group ID.
     */
    public function updateNickname(int $groupId, string $nickname): void
    {
        $this->nicknames[$groupId] = $nickname;
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
     * Get version of Mirai API HTTP plugin.
     *
     * @return string Plugin version.
     */
    public function getVersion(): string
    {
        return $this->bot->version;
    }

    /**
     * Get bot ID.
     *
     * @return int Bot ID.
     */
    public function getId(): int
    {
        return $this->bot->id;
    }

    /**
     * Get bot nickname.
     *
     * @return string Bot nickname.
     */
    public function getNickname(int $groupId = 0): string
    {
        if ($groupId <= 0) {
            return $this->profile->nickname;
        } else {
            if (!isset($this->nicknames[$groupId])) {
                $this->nicknames[$groupId] =
                    $this->api->getMemberInfo($groupId, $this->getId())->getString("memberName", "");
            }

            return $this->nicknames[$groupId];
        }
    }

    /**
     * Get friend.
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
     * Get group.
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
     * Get friend count.
     *
     * @return int Friend count.
     */
    public function getFriendCount(): int
    {
        return $this->friendCount;
    }

    /**
     * Get group count.
     *
     * @return int Group count.
     */
    public function getGroupCount(): int
    {
        return $this->groupCount;
    }
}
