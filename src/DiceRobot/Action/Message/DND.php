<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\ExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Generate character card of adventure.
 */
final class DND extends Action
{
    const DND_GENERATE_RULE = "4D6K3";

    /**
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws InformativeException
     * @throws OrderErrorException
     * @throws ReferenceUndefinedException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.dnd[\s]*/i", "", $this->message);
        $this->checkOrder($order);

        $generateTime = $order == "" ? 1 : (int) $order;

        if ($generateTime < 1 || $generateTime > Customization::getSetting("maxCharacterCardGenerateCount"))
            throw new InformativeException("DNDGenerateCardCountOverstep",
                Customization::getSetting("maxCharacterCardGenerateCount"));

        $this->reply = Customization::getReply("DNDGenerateCardHeading") . "\n";

        $this->generate($generateTime);

        $this->reply = trim($this->reply);
        $this->atSender = true;
    }

    /**
     * Check the validity of the order.
     *
     * @param string $order The order
     *
     * @throws OrderErrorException
     */
    private function checkOrder(string $order): void
    {
        if (!is_numeric($order) && $order != "")
            throw new OrderErrorException;
    }

    /**
     * Generate character card.
     *
     * @param int $time Generate time
     *
     * @throws ExpressionErrorException
     * @throws DiceNumberOverstepException
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws ReferenceUndefinedException
     * @throws SurfaceNumberOverstepException
     */
    private function generate(int $time): void
    {
        $template = new Reference("DNDCharacterCardTemplate");

        for ($i = 1; $i <= $time; $i++)
        {
            $rollResult = [];

            for ($j = 0; $j < 6; $j++)
            {
                $dice = new Dice(self::DND_GENERATE_RULE);
                $rollResult[$j] = $dice->rollResult;
            }

            $this->reply .= Customization::getString($template["DNDAttributesTemplate"],
                    reset($rollResult), next($rollResult), next($rollResult),
                    next($rollResult), next($rollResult), next($rollResult),
                    array_sum($rollResult)) . "\n";
        }
    }
}
