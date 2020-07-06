<?php

namespace TemplateEngine\Renderer;

use Model\Statistics;
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
        $this->viewPath = $GLOBALS['WWW_ROOT_DISK'] . 'theme/responsive/twigs';
        $this->templatePath = $GLOBALS['WWW_ROOT_DISK'] . 'theme/responsive/templates';
    }

    public function render(string $twigFile, array $variables): void
    {
        $twig = $this->initTwig($variables);
        $this->initVariables($variables);
        $variables['FORUM_TITLE'] = $GLOBALS['FORUM_TITLE'];
        $variables['layout__file'] = 'default.twig';
        $variables = array_merge($variables, $this->buildGlobalCopy());
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

    protected function initVariables(array &$variables)
    {
        $variables['fud_real_user'] = __fud_real_user__;
        $variables['BLOG_ENABLED'] = $GLOBALS['FUD_OPT_4'] & 16;
        $variables['PAGES_ENABLED'] = $GLOBALS['FUD_OPT_4'] & 8;
        $variables['CALENDAR_ENABLED'] = $GLOBALS['FUD_OPT_3']  & 134217728;
        $variables['SEARCH_ENABLED'] = $GLOBALS['FUD_OPT_1'] & 16777216;
        $variables['PRIVATE_MESSAGES_ENABLED'] = $GLOBALS['FUD_OPT_1'] & 1024;
        $variables['TREE_VIEW_ENABLED'] = $GLOBALS['FUD_OPT_2'] & 512;
        $variables['SYNDICATION_ENABLED'] = $GLOBALS['FUD_OPT_2'] & 1048576;
        $variables['PDF_ENABLED'] = (($GLOBALS['FUD_OPT_2'] & 270532608) == 270532608);
        $variables['SHOW_MEMBERS'] = ($GLOBALS['FUD_OPT_1'] & 8388608 || (_uid && $GLOBALS['FUD_OPT_1'] & 4194304) || $variables['usr']->users_opt & 1048576);
        $variables['SHOW_REGISTER'] = $GLOBALS['FUD_OPT_1'] & 2;
        $variables['IS_MANAGER'] = $variables['usr']->users_opt & 268435456;
        $variables['IS_GROUP_LEADER'] = $variables['usr']->group_leader_list;
        $variables['SQ'] = $GLOBALS['sq'];
        $variables['user_alias'] = $variables['usr']->alias;
        $variables['statistics'] = (new Statistics($variables))->generateStatistics();
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
