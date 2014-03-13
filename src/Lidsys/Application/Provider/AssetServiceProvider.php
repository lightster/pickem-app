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

use Lstr\Assetrinc\AssetService;
use Lstr\Assetrinc\ResponseAdapter\Symfony as SymfonyResponseAdapter;

use Silex\Application;
use Silex\ServiceProviderInterface;

class AssetServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['lidsys.asset.path']       = new ArrayObject();

        $app['lidsys.asset'] = $app->share(function ($app) {
            $options = array_replace(
                array(
                    'debug' => $app['config']['debug'],
                ),
                $app['config']['lidsys.asset.options']
            );
            return new AssetService(
                $app['lidsys.asset.path'],
                $app['config']['assetrinc.url_prefix'],
                $options
            );
        });
        $app['lidsys.asset.responder'] = $app->share(function ($app) {
            return new SymfonyResponseAdapter($app['lidsys.asset']);
        });
    }

    public function boot(Application $app)
    {
    }
}
