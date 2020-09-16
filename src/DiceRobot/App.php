<?php
namespace DiceRobot;

use DiceRobot\Exception\InformativeException;

/**
 * DiceRobot application.
 */
final class App extends RouteCollector
{
    /**
     * @var int Progress of the application
     *
     * 1: Running
     * 0: Finished
     * -1: Failed to parse the event data
     * -2: No order filtered
     * -3: Matching failed
     * -4: Executing failed
     */
    private int $status = 1;

    /** @var object The event data */
    private object $eventData;

    /** @var string Action name */
    private string $actionName;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->collect();

        if ($this->status > 0)
            parent::__construct($this->eventData);
    }

    /**
     * Get progress of the application.
     *
     * @return int The progress
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Collect the event data.
     */
    private function collect(): void
    {
        if (is_object($eventData = json_decode(file_get_contents("php://input"))))
            $this->eventData = $eventData;
        else
            $this->status = -1;  // Failed to parse the event data
    }

    /**
     * Run the application.
     *
     * @return int Progress of the application
     */
    public function run(): int
    {
        if ($this->status < 0 || $this->filter() < 0 || $this->match() < 0)
            $this->httpCode = 204;
        else
        {
            try
            {
                $action = new $this->actionName($this->eventData);
                $this->execute($action);
            }
            catch (InformativeException $e)
            {
                $this->status = -4;  // Executing failed
                $this->reply = $e;
            }
        }

        $this->respond();
        return $this->status;
    }

    /**
     * Filter the order.
     *
     * @return int Progress of the application
     */
    private function filter(): int
    {
        if ($this->postType != "message")
            return $this->status;

        if (preg_match("/^\s*[.。]([\s\S]+)/u", $this->message, $matches))
        {
            $this->message = $this->eventData->raw_message = "." . trim($matches[1]);
            return $this->status;
        }

        $this->status = -2;  // No order filtered
        return $this->status;
    }

    /**
     * Match the order to the action.
     *
     * @return int Progress of the application
     */
    private function match(): int
    {
        if ($this->postType == "message")
        {
            foreach ($this->eventHandler[$this->postType] as $pattern => $className)
            {
                $actualPattern = "/$pattern/i";

                if (preg_match($actualPattern, $this->message))
                {
                    $this->actionName = $className;
                    return $this->status;
                }
            }
        }
        else
        {
            foreach ($this->eventHandler[$this->postType] as $comparer)
            {
                for ($i = 0; $i < count($comparer["group1"]); $i++)
                {
                    if ($comparer["group1"][$i] != $comparer["group2"][$i])
                        break;
                }

                if ($i == count($comparer["group1"]))
                {
                    $this->actionName = $comparer["action"];
                    return $this->status;
                }
            }
        }

        $this->status = -3;  // Matching failed
        return $this->status;
    }

    /**
     * Execute the action.
     *
     * @param Action $action The action object
     *
     * @throws InformativeException
     *
     * @noinspection PhpDocRedundantThrowsInspection
     */
    private function execute(Action $action): void
    {
        if (!$action->checkActive())
        {
            $this->reply = $action->getReply();
            $this->httpCode = $action->getHttpCode();
            return;
        }

        $action();
        $this->reply = $action->getReply();
        $this->atSender = $action->getAtSender();
        $this->block = $action->getBlock();
        $this->httpCode = $action->getHttpCode();
    }

    /**
     * Respond to the HTTP API plugin.
     */
    private function respond(): void
    {
        $this->status = 7;

        http_response_code($this->getHttpCode());

        if ($this->getHttpCode() == 200)
            echo(json_encode([
                "reply" => $this->getReply(),
                "at_sender" => $this->getAtSender(),
                "block" => $this->getBlock()
            ]));
    }
}
