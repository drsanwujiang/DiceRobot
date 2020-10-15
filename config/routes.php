<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

return [
    /**
     * 消息路由
     *
     * 格式为 "<优先级>" => <路由组>，路由组格式为 "<指令>" => <动作类>
     */
    "message" => [
        10 => [
            "setcoc" => \DiceRobot\Action\Message\SetCOC::class,
            "robot" => \DiceRobot\Action\Message\RobotOrderRouter::class,
            "dismiss" => \DiceRobot\Action\Message\Dismiss::class,  // Alias of .robot goodbye
        ],
        20 => [
            "ra" => \DiceRobot\Action\Message\Check::class,
            "sc" => \DiceRobot\Action\Message\SanCheck::class,

            "coc" => \DiceRobot\Action\Message\Coc::class,
            "dnd" => \DiceRobot\Action\Message\Dnd::class,

            "card" => \DiceRobot\Action\Message\BindCard::class,
            "hp" => \DiceRobot\Action\Message\ChangeAttribute::class,
            "mp" => \DiceRobot\Action\Message\ChangeAttribute::class,  // Alias
            "san" => \DiceRobot\Action\Message\ChangeAttribute::class,  // Alias

            "name" => \DiceRobot\Action\Message\Name::class,
            "nn" => \DiceRobot\Action\Message\Nickname::class,

            "set" => \DiceRobot\Action\Message\Set::class,

            "jrrp" => \DiceRobot\Action\Message\Jrrp::class,
            "orz" => \DiceRobot\Action\Message\Kowtow::class,

            "bot" =>\DiceRobot\Action\Message\RobotOrderRouter::class,  // Alias of .robot

            "help" => \DiceRobot\Action\Message\Help::class,
            "hello" => \DiceRobot\Action\Message\Hello::class
        ],
        100 => [
            "r" => \DiceRobot\Action\Message\Dicing::class,
            "w" => \DiceRobot\Action\Message\DicePool::class,
        ]
    ],

    /**
     * 事件路由
     *
     * 格式为  <事件类型> => <动作类>
     */
    "event" => [
        \DiceRobot\Data\Report\Event\BotInvitedJoinGroupRequestEvent::class =>
            \DiceRobot\Action\Event\BotInvitedJoinGroupRequest::class,
        \DiceRobot\Data\Report\Event\BotJoinGroupEvent::class =>
            \DiceRobot\Action\Event\BotJoinGroup::class,
        \DiceRobot\Data\Report\Event\BotLeaveEventKick::class =>
            \DiceRobot\Action\Event\BotLeaveKick::class,
        \DiceRobot\Data\Report\Event\BotOfflineEventActive::class =>
            \DiceRobot\Action\Event\BotOfflineActive::class,
        \DiceRobot\Data\Report\Event\BotOfflineEventDropped::class =>
            \DiceRobot\Action\Event\BotOfflineDropped::class,
        \DiceRobot\Data\Report\Event\BotOfflineEventForce::class =>
            \DiceRobot\Action\Event\BotOfflineForce::class,
        \DiceRobot\Data\Report\Event\BotOnlineEvent::class =>
            \DiceRobot\Action\Event\BotOnline::class,
        \DiceRobot\Data\Report\Event\BotReloginEvent::class =>
            \DiceRobot\Action\Event\BotRelogin::class,
        \DiceRobot\Data\Report\Event\NewFriendRequestEvent::class =>
            \DiceRobot\Action\Event\NewFriendRequest::class
    ]
];
