<?php

namespace FUDEngine;

use Exception;
use FUDEngine\Lifecycle\Request;
use FUDEngine\Lifecycle\Response;
use FUDEngine\Utility\Configuration\Settings;
use FUDEngine\Utility\Configuration\Options;

/**
 * Class FUDForum
 *
 * Static root class, globally accessible.
 *
 * @package FUDEngine
 *
 * @property-read Request  request Object containing information about the request
 * @property-read Response response Object containing information about the response
 * @property-read Options  options Object containg FUD_OPT array values
 * @property-read Settings globals Object containing global values
 */
class FUDForum
{
    /** @var FUDForum The FUDForum instance */
    protected static $_instance;

    /** @var Options Object containing the options arrays */
    protected $options;

    /** @var Settings Object wrapping global configuration options */
    protected $globals;

    public static function i(): FUDForum
    {
        return FUDForum::$_instance;
    }

    public static function init(Request $request, Response $response, Settings $globals, Options $options): FUDForum
    {
        if (static::$_instance !== null) {
            return static::$_instance;
        }
        static::$_instance = new FUDForum($request, $response, $globals, $options);
        return static::$_instance;
    }

    private function __construct(Request $request, Response $response, Settings $globals, Options $options)
    {
        $this->request = $request;
        $this->response = $response;
        $this->globals = $globals;
        $this->options = $options;
    }

    public function __get(string $name)
    {
        switch($name) {
            case 'options':
                return $this->options;
            case 'globals':
                return $this->globals;
            case 'renderer':
                return $this->response->getRenderer();
            default:
                return null;
        }
    }

    public function setOptions(Options $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function setGlobals(Settings $globals): self
    {
        $this->globals = $globals;
        return $this;
    }
}
