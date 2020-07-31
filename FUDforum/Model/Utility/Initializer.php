<?php

namespace Model\Utility;

use Model\Utility\Configuration\Globals;
use Model\Utility\Configuration\Options;

/**
 * Class Initializer
 *
 * Container for the objects that will be used to initialize FUDForum
 *
 * @package Model\Utility
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
