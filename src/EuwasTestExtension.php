<?php

namespace Bolt\Extension\Euwas\EuwasTestExt;

use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;

/**
 * ExtensionName extension class.
 *
 * @author Your Name <you@example.com>
 */
class EuwasTestExtension extends SimpleExtension
{

    /**
     * {@inheritdoc}
     */
    protected function registerFrontendControllers()
    {
        $config = $this->getConfig();

        return [
            '/members' => new Controller\LoginController($config)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return ['templates'];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'my_twig_function' => 'myTwigFunction',
        ];
    }

    /**
     * The callback function when {{ my_twig_function() }} is used in a template.
     *
     * @return string
     */
    public function myTwigFunction()
    {
        $context = [
            'something' => mt_rand(),
        ];

        $html = $this->renderTemplate('extension.twig', $context);

        return new \Twig_Markup($html, 'UTF-8');
    }

    protected function registerMenuEntries()
    {
        $menu = new MenuEntry('koala-menu', 'koala');
        $menu->setLabel('Koala Catcher')
            ->setIcon('fa:leaf')
            ->setPermission('settings')
        ;

        return [
            $menu,
        ];
    }
}
