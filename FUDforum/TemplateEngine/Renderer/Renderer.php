<?php

namespace TemplateEngine\Renderer;

use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class Renderer
{
    /** @var Environment */
    protected $twig;
    /** @var FilesystemLoader */
    protected $twigLoader;
    protected $viewPath = '';
    protected $templatePath = '';

    public function __construct()
    {
        $this->viewPath = $GLOBALS['WWW_ROOT_DISK'] . '/theme/responsive/twigs';
        $this->templatePath = $GLOBALS['WWW_ROOT_DISK'] . '/theme/responsive/templates';
    }

    public function render(string $twigFile, array $variables): void
    {
        $twig = $this->initTwig($variables);
        $variables['FORUM_TITLE'] = $GLOBALS['FORUM_TITLE'];
        $variables['layout__file'] = $GLOBALS['WWW_ROOT_DISK'] . '/theme/responsive/templates/default';
        $globalCopy = $this->buildGlobalCopy();
        $variables = array_merge($GLOBALS);
        echo $twig->render($twigFile . '.twig', $variables);
    }

    protected function initTwig(array $variables): Environment
    {
        $this->twigLoader = new FilesystemLoader();
        $this->twig = new Environment($this->twigLoader);
        $this->twig->addGlobal('renderer', $this);
        $this->twig->addExtension(new StringLoaderExtension());
        $this->twigLoader->addPath($this->templatePath);
        $this->twigLoader->addPath($this->viewPath); //TODO: Add namespacing
        $this->parseFilters($variables);
        return $this->twig;
    }

    protected function parseFilters(array $variables)
    {
        $filter_class_name = TwigFilter::class;
        if (isset($variables['twigFilters']) && is_array($variables['twigFilters'])) {
            foreach ($variables['twigFilters'] as $filter) {
                if ($filter instanceof $filter_class_name) {
                    $this->twig->addFilter($filter);
                    //var_dump('Added.');
                }
            }
        }
    }

    /**
     * Make a version of the globals that does not include sensitive information
     */
    protected function buildGlobalCopy(): array
    {
        $copy = [];
        foreach ($GLOBALS as $key=>$value) {
            if (!$this->startsWith($key, 'DBHOST') &&
                !$this->startsWith($key, 'FUD_SMTP')
            ) {
                $copy['GLOBAL_' . $key] = $value;
            }
        }
        return $copy;
    }

    private function startsWith(string $string, string $prefix): bool
    {
        $len = strlen($prefix);
        return (substr($string, 0, $len) === $prefix);
    }
}
