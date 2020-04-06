<?php
namespace DiceRobot;

/**
 * Event data parser.
 */
abstract class Parser extends Response
{
    public string $postType;
    public int $selfId;
    public ?int $userId = NULL;
    public ?string $subType = NULL;

    public string $chatType;
    public int $chatId;
    public string $userName;
    public string $message;

    public ?string $noticeType = NULL;
    public ?int $groupId = NULL;

    public ?string $requestType = NULL;
    public string $flag;

    public ?string $metaEventType = NULL;

    /**
     * The constructor.
     *
     * @param object $eventData The event data
     */
    protected function __construct(object $eventData)
    {
        $this->parse($eventData);
    }

    /**
     * Parse the event data and initialize the corresponding variables.
     *
     * @param object $eventData The event data
     */
    private function parse(object $eventData): void
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

            $this->message = "." . trim(mb_substr($eventData->raw_message, 1));

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
}
