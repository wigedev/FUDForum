<?php

namespace FUDEngine\Lifecycle;

use FUDEngine\Renderer\Renderer;

/**
 * Class Response
 *
 * Wrapper for the response as it is being assembled.
 *
 * @package FUDEngine\Lifecycle
 */
class Response implements ResponseInterface
{
    /** @var Renderer */
    protected $renderer;
    /** @var array */
    protected $data;
    /** @var array */
    protected $members;
    /** @var string */
    protected $theme;

    public function __construct()
    {

    }

    public function setRenderer(Renderer $renderer): void
    {
        $this->renderer = $renderer;
    }

    public function getRenderer(): Renderer
    {
        return $this->renderer;
    }

    public function __get(string $name)
    {
        if (isset($this->members[$name])) {
            return $this->members[$name];
        }
        return null;
    }

    public function __set(string $name, $value)
    {
        $this->members[$name] = $value;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getValues(): array
    {
        return $this->members;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
