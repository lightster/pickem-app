<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Application\Provider;

use ArrayObject;

use Lidsys\Application\Service\AssetService;

use Silex\Application;
use Silex\ServiceProviderInterface;

class AssetServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['lidsys.asset.path']       = new ArrayObject();
        $app['lidsys.asset.renderer']   = array(
            'css' => function ($path) {
                return "<link rel=\"stylesheet\" href=\"{$path}\" />";
            },
            'js' => function ($path) {
                return "<script type=\"text/javascript\" src=\"{$path}\"></script>";
            },
        );
        $app['lidsys.asset.options']    = array();

        $app['lidsys.asset'] = $app->share(function ($app) {
            $options = array_replace(
                array(
                    'debug'                => $app['config']['debug'],
                    'assetrinc.url_prefix' => $app['config']['assetrinc.url_prefix'],
                    'assetrinc.binaries'   => $app['config']['assetrinc.binaries'],
                ),
                $app['lidsys.asset.options']
            );
            return new AssetService(
                $app['lidsys.asset.path'],
                $app['lidsys.asset.renderer'],
                $options
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
