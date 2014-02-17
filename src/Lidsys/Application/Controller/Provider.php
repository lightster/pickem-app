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
use Assetic\Filter\UglifyCssFilter;
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
                __DIR__ . '/assets',
            ));

            $js_files = $manifest_parser->getJsFiles($name);
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
                    $name => $js_files,
                ),
                'css' => array(
                    'application' => array(
                        'foundation/css/foundation.css',
                        'lidsys/app.css',
                    ),
                ),
            );

            $binaries = $app['config']['assetrinc.binaries'];

            $filters = array(
                'coffee' => new CoffeeScriptFilter($binaries['coffee']),
                'uglifyJs' => new UglifyJs2Filter($binaries['uglifyJs']),
                'cssUrls' => new CssRewriteFilter(),
                'uglifyCss' => new UglifyCssFilter($binaries['uglifyCss']),
            );

            $filter_names_by_ext = array(
                'coffee' => array(
                    'coffee',
                ),
                'js' => array(
                    '?uglifyJs',
                ),
                'css' => array(
                    'cssUrls',
                    '?uglifyCss',
                ),
            );

            $filters_by_ext = array();
            foreach ($filter_names_by_ext as $ext => $filter_names) {
                foreach ($filter_names as $filter_name) {
                    $filter = null;
                    if (substr($filter_name, 0, 1) === '?') {
                        if (!$app['debug']) {
                            $filter = $filters[substr($filter_name, 1)];
                        }
                    } else {
                        $filter = $filters[$filter_name];
                    }

                    if ($filter) {
                        $filters_by_ext[$ext][$filter_name] = $filter;
                    }
                }
            }

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
        })->assert('name', '.*');


        $controllers->get('/', function () use ($app) {
            return $app['lstr.template']->render('index/index.phtml');
        });

        return $controllers;
    }
}
