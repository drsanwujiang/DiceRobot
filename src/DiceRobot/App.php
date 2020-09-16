<?php
namespace DiceRobot;

use DiceRobot\Exception\InformativeException;

/**
 * DiceRobot application.
 */
final class App extends RouteCollector
{
    /** @var object The event data */
    private object $eventData;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->collect();

        parent::__construct($this->eventData);
    }

    /**
     * Collect the event data.
     */
    private function collect(): void
    {
        $this->eventData = json_decode(file_get_contents("php://input"));
    }

    /**
     * Run the application.
     */
    public function run(): void
    {
        if (!$this->filter() || is_null($className = $this->match()))
            $this->httpCode = 204;
        else
        {
            try
            {
                $action = new $className($this->eventData);
                $this->execute($action);
            }
            catch (InformativeException $e)
            {
                $this->reply = $e;
            }
        }

        $this->respond();
    }

    /**
     * Filter the order.
     *
     * @return bool The filter result
     */
    private function filter(): bool
    {
        if (preg_match("/^\s*[.。]([\s\S]+)/u", $this->message, $matches))
        {
            $this->message = "." . trim($matches[1]);
            return true;
        }

        return false;
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
        http_response_code($this->getHttpCode());

        if ($this->getHttpCode() == 200)
            echo(json_encode([
                "reply" => $this->getReply(),
                "at_sender" => $this->getAtSender(),
                "block" => $this->getBlock()
            ]));
    }
}
