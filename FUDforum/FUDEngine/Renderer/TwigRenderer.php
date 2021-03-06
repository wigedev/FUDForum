<?php

namespace FUDEngine\Renderer;

use FUDEngine\Statistics;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class TwigRenderer extends Renderer
{
    /** @var Environment */
    protected $twig;
    /** @var FilesystemLoader */
    protected $twigLoader;
    protected $viewPath = '';
    protected $templatePath = '';

    public function __construct()
    {
        $templateName = F()->response->getTemplate();
        $this->viewPath = F()->globals->DATA_DIR . 'Theme/'.$templateName.'/view';
        $this->templatePath = F()->globals->DATA_DIR . 'Theme/'.$templateName.'/template';
    }

    public function render(): void
    {
        $twigFile = F()->response->getTemplate();
        $twig = $this->initTwig();
        $this->initVariables();
        $this->variables['FORUM_TITLE'] = F()->globals->FORUM_TITLE;
        $this->variables['layout__file'] = 'default.twig';
        $this->variables = array_merge($this->variables, $this->buildGlobalCopy());
        echo $twig->render($twigFile . '.twig', $this->variables);
    }

    protected function initTwig(): Environment
    {
        $this->twigLoader = new FilesystemLoader();
        $this->twig = new Environment($this->twigLoader);
        $this->twig->addGlobal('renderer', $this);
        $this->twig->addExtension(new StringLoaderExtension());
        $this->twigLoader->addPath($this->templatePath);
        $this->twigLoader->addPath($this->viewPath); //TODO: Add namespacing
        $this->parseFilters();
        return $this->twig;
    }

    protected function initVariables()
    {
        $this->variables = array_merge($this->variables, F()->response->getValues());
        $this->variables['_GET'] = $_GET; // TODO: Remove this
        $this->variables['_POST'] = $_POST; // TODO: Remove this
        $this->variables['options'] = F()->options;
        $this->variables['globals'] = F()->globals;
        $this->variables['fud_real_user'] = __fud_real_user__;
        //TODO: Use options instead of globals
        $this->variables['SHOW_MEMBERS'] = ($GLOBALS['FUD_OPT_1'] & 8388608 || (_uid && $GLOBALS['FUD_OPT_1'] & 4194304) || $this->variables['usr']->users_opt & 1048576);
        $this->variables['IS_MANAGER'] = $this->variables['usr']->users_opt & 268435456;
        $this->variables['IS_GROUP_LEADER'] = $this->variables['usr']->group_leader_list;
        $this->variables['MSG_SHOW_SIGNATURE'] = $this->variables['usr']->users_opt & 2048;
        $this->variables['MSG_NOTIFY_POSTER'] = $this->variables['usr']->users_opt & 2;
        $this->variables['SQ'] = F()->globals->sq;
        $this->variables['user_alias'] = $this->variables['usr']->alias;
        $this->variables['statistics'] = (new Statistics($this->variables))->generateStatistics();
    }

    protected function parseFilters()
    {
        $filter_class_name = TwigFilter::class;
        if (isset($this->variables['twigFilters']) && is_array($this->variables['twigFilters'])) {
            foreach ($this->variables['twigFilters'] as $filter) {
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
