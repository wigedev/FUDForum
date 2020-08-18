<?php

namespace FUDEngine\Lifecycle;

use FUDEngine\Renderer\Renderer;

interface ResponseInterface
{
    public function setTemplate(string $template): void;

    /**
     * @param Renderer $renderer The renderer that will be displaying the content
     */
    public function setRenderer(Renderer $renderer): void;

    /**
     * @return Renderer Reference to the renderer that will be generating the output
     */
    public function getRenderer(): Renderer;

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
