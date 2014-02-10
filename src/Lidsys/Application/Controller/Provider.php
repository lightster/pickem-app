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
use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\UglifyJs2Filter;
use Assetic\FilterManager;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Sprocketeer\Parser as SprocketeerParser;
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
            $manifest_parser = new SprocketeerParser(array(
                __DIR__ . '/assets/bundle',
                __DIR__ . '/assets',
            ));

            $js_files = $manifest_parser->getJsFiles('application');
            array_walk(
                $js_files,
                function (& $asset) {
                    $asset = str_replace(
                        __DIR__ . '/assets/',
                        '',
                        $asset
                    );
                }
            );

            $assets = array(
                'js' => array(
                    'application' => $js_files,
                ),
                'css' => array(
                    'application' => array(
                        'foundation/css/foundation.css',
                        'lidsys/app.css',
                    ),
                ),
            );

            $filters = array(
                'coffee' => new CoffeeScriptFilter('/Users/lightster/node_modules/coffee-script/bin/coffee'),
                'uglifyJs' => new UglifyJs2Filter('/usr/local/share/npm/lib/node_modules/uglify-js/bin/uglifyjs'),
                'cssUrls' => new CssRewriteFilter(),
            );

            $filters_by_ext = array(
                'coffee' => array(
                    $filters['coffee'],
                ),
                'js' => array(
                    $filters['uglifyJs'],
                ),
                'css' => array(
                    $filters['cssUrls'],
                ),
            );

            $asset_list = array();
            foreach ($assets[$type][$name] as $asset) {
                $extensions = explode('.', $asset);

                $filters = array();
                foreach (array_reverse($extensions) as $ext) {
                    if (array_key_exists($ext, $filters_by_ext)) {
                        $filters = array_merge($filters, $filters_by_ext[$ext]);
                    }
                }

                $file_asset = new FileAsset(
                    __DIR__ . '/assets/' . $asset,
                    $filters,
                    dirname(__DIR__ . '/assets/' . $asset),
                    "/app/assets/{$asset}"
                );

                $asset_list[] = $file_asset;
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
