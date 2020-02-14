<?php
namespace DiceRobot;

use DiceRobot\Exception\InformativeException;

/**
 * Class RouteCollector
 *
 * Define variables and methods about routing, call corresponding class to handle the event.
 */
abstract class RouteCollector extends Parser
{
    private object $eventData;

    private array $currentGroup;
    private array $eventHandler = array(
        "message" => array(),
        "meta_event" => array(),
        "notice" => array(),
        "request" => array()
    );

    public function __construct(object $eventData)
    {
        parent::__construct($eventData);

        $this->eventData = $eventData;
    }


    protected function group(string $eventType, callable $callable): void
    {
        $this->currentGroup = $this->eventHandler[$eventType];
        $callable($this);
        $this->eventHandler[$eventType] = $this->currentGroup;
    }

    protected function add(string $pattern, string $action): void
    {
        $this->currentGroup[$pattern] = $action;
    }

    protected function addComparer(array $key, array $value, string $action): void
    {
        $this->currentGroup[] = array("key" => $key, "value" => $value, "action" => $action);
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

    /**
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function run()
    {
        $action = $this->parse();

        if (is_null($action))
        {
            $this->unableToResolve();
            return;
        }

        $actionObject = new $action($this->eventData);

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
