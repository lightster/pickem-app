<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\User\Service;

use Lstr\Silex\Database\DatabaseService;

use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['lidsys.user.authenticator'] = $app->share(function ($app) {
            return new AuthenticatorService($app);
        });
        $app['lidsys.user.auth-reset'] = $app->share(function ($app) {
            return new AuthenticationResetService(
                $app['lidsys.user.authenticator'],
                $app['db'],
                $app['mailer'],
                $app['config']['auth']
            );
        });
        $app['lidsys.user'] = $app->share(function ($app) {
            return new UserService($app['db']);
        });
    }



    public function boot(Application $app)
    {
    }
}
