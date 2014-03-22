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

use Lstr\Silex\Template\Exception\TemplateNotFound;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lstr.asset.path']['app']  = __DIR__ . '/assets';
        $app['lstr.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->get('/template/{controller}/{template}', function ($controller, $template, Application $app) {
            try {
                return $app['lstr.template']->render("{$controller}/{$template}");
            } catch (TemplateNotFound $ex) {
                return new Response($ex->getMessage(), 404);
            }
        });
        $controllers->get('/asset/{version}/{name}', function ($version, $name, Application $app, Request $request) {
            return $app['lstr.asset.responder']->getResponse(
                $name,
                array(
                    'request' => $request,
                )
            );
        })->assert('name', '.*');


        $controllers->get('/', function (Application $app) {
            return $app['lstr.template']->render('index/index.phtml');
        });

        return $controllers;
    }
}
