<?php

namespace FUDEngine\Renderer;

class Renderer
{
    protected $variables = [];
    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function __get($name)
    {
        return $this->variables[$name];
    }
}
