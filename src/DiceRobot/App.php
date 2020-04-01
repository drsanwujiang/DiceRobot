<?php
namespace DiceRobot;

use DiceRobot\Action\Action;
use DiceRobot\Exception\InformativeException;

/**
 * DiceRobot application.
 */
final class App extends RouteCollector
{
    private object $eventData;

    /**
     * Constructor.
     *
     * @param object $eventData The event data
     */
    public function __construct(object $eventData)
    {
        parent::__construct($eventData);

        $this->eventData = $eventData;
    }

    /**
     * Run the application.
     */
    public function run()
    {
        $className = $this->match();

        if (is_null($className))
            $this->httpCode = 204;
        else
        {
            $action = new $className($this->eventData);
            $this->execute($action);
        }

        $this->respond();
    }

    /**
     * Match the order to the action.
     *
     * @return string|null Action class name
     */
    private function match(): ?string
    {
        if ($this->postType == "message")
        {
            foreach ($this->eventHandler[$this->postType] as $pattern => $className)
            {
                $actualPattern = "/$pattern/i";

                if (preg_match($actualPattern, $this->message))
                {
                    $actionName = $className;
                    break;
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
                    $actionName = $comparer["action"];
                    break;
                }
            }
        }

        return $actionName ?? NULL;
    }

    /**
     * Execute the action.
     *
     * @param Action $action The action object
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    private function execute(Action $action): void
    {
        if (!$action->checkActive())
        {
            $this->reply = $action->getReply();
            $this->httpCode = $action->getHttpCode();
            return;
        }

        try
        {
            $action();
            $this->reply = $action->getReply();
            $this->atSender = $action->getAtSender();
        }
        catch (InformativeException $e)
        {
            $this->reply = $e;
        }

        $this->block = $action->getBlock();
        $this->httpCode = $action->getHttpCode();
    }

    /**
     * Respond to the HTTP API plugin.
     */
    private function respond(): void
    {
        http_response_code($this->getHttpCode());

        if ($this->getHttpCode() == 200)
            echo(json_encode([
                "reply" => $this->getReply(),
                "at_sender" => $this->getAtSender(),
                "block" => $this->getBlock()
            ]));
    }
}
