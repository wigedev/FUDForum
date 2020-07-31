<?php

namespace FUDEngine\Utility;

use FUDEngine\Utility\Configuration\Globals;
use FUDEngine\Utility\Configuration\Options;

/**
 * Class Initializer
 *
 * Container for the objects that will be used to initialize FUDForum
 *
 * @package FUDEngine\Utility
 */
class Initializer
{
    /** @var Request */
    public $request;
    /** @var Response */
    public $response;
    /** @var Options */
    public $options;
    /** @var Globals */
    public $globals;
}
