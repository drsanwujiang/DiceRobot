<?php
namespace DiceRobot;

/**
 * Class Parser
 *
 * Base class of all the action class and RouteCollector class. Parser defines variables/methods used and assigns value
 * to these variables according to the event type.
 */
abstract class Parser
{
    protected string $postType;
    protected int $selfId;
    protected ?int $userId = NULL;
    protected ?string $subType = NULL;

    protected string $chatType;
    protected int $chatId;
    protected string $userName;
    protected string $userNickname;
    protected string $message;

    protected ?string $noticeType = NULL;
    protected ?int $groupId;

    protected ?string $requestType = NULL;
    protected string $flag;

    protected ?string $metaEventType = NULL;

    protected ?string $reply = NULL;
    protected bool $atSender = false;
    protected bool $block = true;
    protected int $httpCode = 200;

    /**
     * Parser constructor.
     *
     * @param object $eventData Event data decoded
     */
    protected function __construct(object $eventData)
    {
        // General fields
        $this->postType = $eventData->post_type;
        $this->selfId = $eventData->self_id;

        if ($this->postType == "message")
        {
            // Message fields
            $this->chatType = $eventData->message_type;
            $this->userId = $eventData->user_id;
            $this->userName = $eventData->sender->nickname;
            $this->message = trim($eventData->raw_message);

            if ($this->chatType == "group")
                $this->chatId = $eventData->group_id;
            elseif ($this->chatType == "discuss")
                $this->chatId = $eventData->discuss_id;
            elseif ($this->chatType == "private")
                $this->chatId = $eventData->user_id;
        }
        elseif ($this->postType == "notice")
        {
            // Notice fields
            $this->noticeType = $eventData->notice_type;
            $this->userId = $eventData->user_id;
            $this->subType = $eventData->sub_type ?? NULL;
            $this->groupId = $eventData->group_id ?? NULL;
        }
        elseif ($this->postType == "request")
        {
            // Request fields
            $this->requestType = $eventData->request_type;
            $this->subType = $eventData->sub_type ?? NULL;
            $this->flag = $eventData->flag;
            $this->groupId = $eventData->group_id ?? NULL;
            $this->userId = $eventData->user_id;
        }
        elseif ($this->postType == "meta_event")
        {
            // MetaEvent fields
            $this->metaEventType = $eventData->meta_event_type;
        }
    }

    /**
     * Actions should call this method when no need to response. For accurate recognition, this function should
     * always set HTTP code to 204.
     */
    final protected function noResponse(): void
    {
        $this->httpCode = 204;
    }

    /**
     * Action can redefine this method to implement specific function, and call it when the order is unable to be
     * resolved.
     */
    protected function unableToResolve(): void
    {
        $this->httpCode = 204;
    }

    /**
     * @return string|null Reply. String when event type is message, null when event type is others.
     */
    public function getReply(): ?string
    {
        return $this->reply;
    }

    /**
     * @return bool Flag indicating the need of at message sender.
     */
    public function getAtSender(): bool
    {
        return $this->atSender;
    }

    /**
     * @return bool Flag indicating whether intercept this event and not let other plugins handle it.
     */
    public function getBlock(): bool
    {
        return $this->block;
    }

    /**
     * @return int HTTP code.
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
