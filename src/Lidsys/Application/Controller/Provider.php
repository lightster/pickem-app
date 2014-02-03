<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Application\Controller;

use Lstr\Silex\Service\Exception\TemplateNotFound;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lstr.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->get('/template/{controller}/{template}', function ($controller, $template) use ($app) {
            try {
                return $app['lstr.template']->render("{$controller}/{$template}");
            } catch (TemplateNotFound $ex) {
                return new Response($ex->getMessage(), 404);
            }
        });
        $controllers->get('/asset/{type}/{name}', function ($type, $name) use ($app) {
            $pipeline = new \Sprockets\Pipeline(array(
                'CACHE_DIRECTORY' => __DIR__ . '/../../../../cache/',
                'template' => array(
                    'directories' => array(
                        'assets/bundle/',
                        'assets/',
                    ),
                ),
            ));
            return $pipeline($type, $name);
        });

        $controllers->get('/', function () use ($app) {
            return $app['lstr.template']->render('index/index.html');
        });

        return $controllers;
    }
}
