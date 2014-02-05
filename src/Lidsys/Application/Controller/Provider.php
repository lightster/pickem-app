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

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CoffeeScriptFilter;
use Assetic\FilterManager;
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
            $assets = array(
                'js' => array(
                    'application' => array(
                        'public/assets/moment/moment-with-langs.js',
                        'public/assets/foundation/js/vendor/jquery.js',
                        'public/assets/foundation/js/foundation/foundation.js',
                        'public/assets/angular/angular.js',
                        'public/assets/angular/angular-route.js',
                        'public/assets/lidsys/app-models.coffee',
                        'public/assets/lidsys/football-models.coffee',
                        'public/assets/lidsys/football.js',
                        'public/assets/lidsys/nav.js',
                        'public/assets/lidsys/app.js',
                    ),
                ),
            );

            $filters = array(
                'coffee' => new CoffeeScriptFilter('/Users/lightster/node_modules/coffee-script/bin/coffee'),
            );

            $filters_by_ext = array(
                'coffee' => array(
                    $filters['coffee'],
                ),
            );

            $asset_list = array();
            foreach ($assets[$type][$name] as $asset) {
                list($file, $ext) = explode('.', $asset, 2);

                $filters = array();
                if (array_key_exists($ext, $filters_by_ext)) {
                    $filters = $filters_by_ext[$ext];
                }

                $asset_list[] = new FileAsset(
                    __DIR__ . '/../../../../' . $asset,
                    $filters
                );
            }

            $collection = new AssetCollection($asset_list);

            $content_types = array(
                'js'  => 'text/javascript',
                'css' => 'text/css',
            );

            $content = $collection->dump();
            return new Response($content, 200, array(
                'Content-Type' => $content_types[$type],
            ));
        });

        $controllers->get('/', function () use ($app) {
            return $app['lstr.template']->render('index/index.html');
        });

        return $controllers;
    }
}
