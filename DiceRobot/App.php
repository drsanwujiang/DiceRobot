<?php
namespace DiceRobot;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Exception\InformativeException;

/**
 * DiceRobot application.
 */
final class App extends Parser
{
    private object $eventData;

    private array $currentGroup;
    private array $eventHandler = ["message" => [], "meta_event" => [], "notice" => [], "request" => []];

    public function __construct(object $eventData)
    {
        $this->eventData = $eventData;
        $this->parseEventData($eventData);
    }

    public function group(string $eventType, callable $callable): void
    {
        $this->currentGroup = $this->eventHandler[$eventType];
        $callable($this);
        $this->eventHandler[$eventType] = $this->currentGroup;
    }

    public function add(string $pattern, string $action): void
    {
        $this->currentGroup[$pattern] = $action;
    }

    public function addComparer(array $key, array $value, string $action): void
    {
        $this->currentGroup[] = array("key" => $key, "value" => $value, "action" => $action);
    }

    public function run()
    {
        $action = $this->parse();

        if (is_null($action))
        {
            $this->httpCode = 204;
            return;
        }

        $actionObject = new $action($this->eventData);

        $this->execute($actionObject);
    }

    private function parse(): ?string
    {
        if ($this->postType == "message")
        {
            foreach ($this->eventHandler[$this->postType] as $pattern => $actionName)
            {
                $actualPattern = "/^\\$pattern/i";

                if (preg_match($actualPattern, $this->message))
                {
                    $action = $actionName;
                    break;
                }
            }
        }
        else
        {
            foreach ($this->eventHandler[$this->postType] as $comparer)
            {
                for ($i = 0; $i < count($comparer["key"]); $i++)
                {
                    if ($comparer["key"][$i] != $comparer["value"][$i])
                        break;
                }

                if ($i == count($comparer["key"]))
                {
                    $action = $comparer["action"];
                    break;
                }
            }
        }

        return $action ?? NULL;
    }

    /** @noinspection PhpRedundantCatchClauseInspection */
    private function execute(AbstractAction &$actionObject): void
    {
        if (!$actionObject->checkActive())
        {
            $this->httpCode = $actionObject->getHttpCode();
            return;
        }

        try
        {
            $actionObject();
            $this->reply = $actionObject->getReply();
            $this->atSender = $actionObject->getAtSender();
        }
        catch (InformativeException $e)
        {
            $this->reply = $e;
        }

        $this->block = $actionObject->getBlock();
        $this->httpCode = $actionObject->getHttpCode();
    }
}
