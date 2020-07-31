<?php

namespace Model;

use Exception;
use Model\Utility\Configuration\Globals;
use Model\Utility\Configuration\Options;

/**
 * Class FUDForum
 *
 * Static root class, globally accessible.
 *
 * @package Model
 *
 * @property-read Options options Object containg FUD_OPT array values
 * @property-read Globals globals Object containing global values
 * @property-read Request request Object containing information about the request
 * @property-read Response response Object containing information about the response
 */
class FUDForum
{
    /** @var FUDForum The FUDForum instance */
    protected static $_instance;

    /** @var Options Object containing the options arrays */
    protected $options;

    /** @var Globals Object wrapping global configuration options */
    protected $globals;

    public static function i() {
        return FUDForum::$_instance;
    }

    public function __construct()
    {

    }

    public function __get(string $name)
    {
        switch($name) {
            case 'options':
                return $this->options;
            case 'globals':
                return $this->globals;
            default:
                return null;
        }
    }

    public function setOptions(Options $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function setGlobals(Globals $globals): self
    {
        $this->globals = $globals;
        return $this;
    }
}

function F(): FUDForum
{
    try {
        return FUDForum::i();
    } catch (Exception $exception) {
        exit();
    }
}
