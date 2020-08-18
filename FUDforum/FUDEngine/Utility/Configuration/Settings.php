<?php

namespace FUDEngine\Utility\Configuration;

use FUDEngine\Exceptions\ConfigurationException;

/**
 * Class Settings
 *
 * Wrapper for the old global configuration options arrays.
 *
 * @package FUDEngine\Utility\Configuration
 */
class Settings
{
    protected $globals;

    public function __construct(array $globals)
    {
        $this->globals = $globals;
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws ConfigurationException if the setting is not found
     */
    public function __get(string $name)
    {
        if (isset($this->globals[$name])) {
            return $this->globals[$name];
        }
        throw new ConfigurationException('The specified setting is not found.');
    }
}
