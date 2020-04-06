<?php
namespace DiceRobot;

/**
 * The route collector.
 */
abstract class RouteCollector extends Parser
{
    private array $currentGroup = [];
    protected array $eventHandler = [
        "message" => [],
        "meta_event" => [],
        "notice" => [],
        "request" => []
    ];

    /**
     * Set routes to the group of the specific event type.
     *
     * @param string $eventType The event type
     * @param callable $callable Closure
     */
    public function group(string $eventType, callable $callable): void
    {
        $this->currentGroup = $this->eventHandler[$eventType];
        $callable($this);
        $this->eventHandler[$eventType] = $this->currentGroup;
    }

    /**
     * Add a route matched by the pattern to the group.
     *
     * @param string $pattern
     * @param string $action
     */
    public function add(string $pattern, string $action): void
    {
        $this->currentGroup[$pattern] = $action;
    }

    /**
     * Add a route matched by the comparer.
     *
     * @param array $group1 Comparision group 1
     * @param array $group2 Comparision group 2
     * @param string $action Action class name
     */
    public function addComparer(array $group1, array $group2, string $action): void
    {
        $this->currentGroup[] = ["group1" => $group1, "group2" => $group2, "action" => $action];
    }
}
