<?php

namespace FUDEngine\Lifecycle;

use FUDEngine\Renderer\Renderer;

interface ResponseInterface
{
    /**
     * @param string $template The name of the template
     */
    public function setTemplate(string $template): void;

    /**
     * @return string The name of the template
     */
    public function getTemplate(): string;

    /**
     * @param Renderer $renderer The renderer that will be displaying the content
     */
    public function setRenderer(Renderer $renderer): void;

    /**
     * @return Renderer Reference to the renderer that will be generating the output
     */
    public function getRenderer(): Renderer;

    /**
     * @param string $theme The internal name of the theme
     */
    public function setTheme(string $theme): void;

    /**
     * @returns string The internal name of the theme, or default if no theme is set
     */
    public function getTheme(): string;

    /**
     * @param array $data Data that is the core purpose of the page, such as a report.
     */
    public function setData(array $data): void;

    /**
     * @return array The data array that has been set
     */
    public function getData(): array;

    /**
     * @return array The array of values set for output
     */
    public function getValues(): array;
}
