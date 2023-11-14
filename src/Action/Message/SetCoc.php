<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\CheckRuleException\LostException;

/**
 * Class SetCoc
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
class SetCoc extends MessageAction
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
            $this->setReply("setCocCurrentRule", [
                "规则名称" => $rule->getString("name"),
                "规则描述" => $rule->getString("description"),
                "规则介绍" => $rule->getString("introduction")
            ]);
        } else {
            // Change rule
            $this->chatSettings->set("cocCheckRule", $ruleId);

            $this->setReply("setCocRuleSet", [
                "规则名称" => $rule->getString("name"),
                "规则描述" => $rule->getString("description"),
                "规则介绍" => $rule->getString("introduction")
            ]);
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^(\d+)?$/", $this->order, $matches)) {
            $ruleId = -1;
        } else {
            $ruleId = isset($matches[1]) ? (int) $matches[1] : null;
        }

        /**
         * @var int|null $ruleId COC check rule ID.
         */
        return [$ruleId];
    }

    /**
     * Check the range.
     *
     * @param int|null $ruleId COC check rule ID.
     *
     * @return bool Validity.
     */
    protected function checkRange(?int $ruleId): bool
    {
        if (!is_null($ruleId) && $ruleId < 0) {
            $this->setReply("setCocRuleIdError");

            return false;
        }

        return true;
    }
}
