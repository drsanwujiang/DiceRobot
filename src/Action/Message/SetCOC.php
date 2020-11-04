<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\CheckRuleException\LostException;
use DiceRobot\Util\Convertor;

/**
 * Class SetCOC
 *
 * Show description of current rule, or set default COC check rule.
 *
 * @order setcoc
 *
 *      Sample: .setcoc
 *              .setcoc 1
 *
 * @package DiceRobot\Action\Message
 */
class SetCOC extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws LostException
     */
    public function __invoke(): void
    {
        list($ruleId) = $this->parseOrder();

        if (!$this->checkRange($ruleId)) {
            return;
        }

        $rule = $this->resource->getCheckRule(
            is_null($ruleId) ? $this->chatSettings->getInt("cocCheckRule") : $ruleId
        );

        if (is_null($ruleId)) {
            // Show rule details
            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.setCocCurrentRule"),
                    [
                        "规则名称" => $rule->getString("name"),
                        "规则描述" => $rule->getString("description"),
                        "规则介绍" => $rule->getString("introduction")
                    ]
                );
        } else {
            // Change rule
            $this->chatSettings->set("cocCheckRule", $ruleId);

            $this->reply =
                Convertor::toCustomString(
                    $this->config->getString("reply.setCocRuleChanged"),
                    [
                        "规则名称" => $rule->getString("name"),
                        "规则描述" => $rule->getString("description"),
                        "规则介绍" => $rule->getString("introduction")
                    ]
                );
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^([0-9]*)?$/", $this->order, $matches)) {
            return [-1];
        }

        /** @var int|null $ruleId */
        $ruleId = empty($matches[1]) ? null : (int) $matches[1];

        return [$ruleId];
    }

    /**
     * Check the range.
     *
     * @param int|null $ruleId Rule ID
     *
     * @return bool Validity
     */
    protected function checkRange(?int $ruleId): bool
    {
        if (!is_null($ruleId) && $ruleId < 0) {
            $this->reply = $this->config->getString("reply.setCocRuleIdError");

            return false;
        }

        return true;
    }
}
