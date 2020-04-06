<?php
namespace DiceRobot\Service\Container;

use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\IOService;

/**
 * The reference.
 */
class Reference
{
    protected static string $referenceDir;
    protected static array $referenceMapping;

    protected array $reference;

    /**
     * The constructor.
     *
     * @param string $refKey Reference key
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws ReferenceUndefinedException
     */
    public function __construct(string $refKey)
    {
        $this->load($refKey);
    }

    /**
     * Set the dir of reference files.
     *
     * @param string $dir The dir
     */
    public static function setDir(string $dir): void
    {
        self::$referenceDir = $dir;
    }

    /**
     * Set the key-file mapping of reference files.
     *
     * @param array $mapping The key-file mapping
     */
    public static function setMapping(array $mapping): void
    {
        self::$referenceMapping = $mapping;
    }

    /**
     * Load reference file.
     *
     * @param string $refKey
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws ReferenceUndefinedException
     */
    private function load(string $refKey): void
    {
        if (!isset(self::$referenceMapping[$refKey]))
            throw new ReferenceUndefinedException();

        $this->reference = IOService::getFile(self::$referenceDir . self::$referenceMapping[$refKey]);
    }

    /**
     * Get the value in the reference.
     *
     * @param string $key Key
     *
     * @return mixed|null Value
     */
    public function get(string $key)
    {
        return $this->reference[$key] ?? NULL;
    }

    /**
     * Get reference as string (template only).
     *
     * @return string Reference
     */
    public function getString(): string
    {
        return join("\n", $this->reference);
    }
}
