<?php

namespace Model\Utility\Configuration;

/**
 * Class Globals
 *
 * Wrapper for the old global configuration options arrays.
 *
 * @package Model\Utility\Configuration
 */
class Globals
{
    protected $GLOBALS;

    public function __construct(array $GLOBALS)
    {
        $this->GLOBALS = $GLOBALS;
    }

    public function __get(string $name)
    {
        if (isset($this->GLOBALS[$name])) {
            return $this->GLOBALS[$name];
        }
        throw new ConfigurationException('The specified global option is not set.');
    }
}
