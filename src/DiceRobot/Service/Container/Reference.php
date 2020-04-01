<?php
namespace DiceRobot\Service\Container;

use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\IOService;

class Reference
{
    protected static string $referenceDir;
    protected static array $referenceMapping;

    protected array $reference;

    /**
     * Constructor.
     *
     * @param string $refKey Reference key
     *
     * @throws FileLostException
     * @throws JSONDecodeException
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
     * @throws FileLostException
     * @throws JSONDecodeException
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
