<?php

namespace FUDEngine\Renderer;

abstract class Renderer
{
    protected $variables = [];

    abstract public function render();
}
