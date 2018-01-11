<?php

namespace Lidsys\User\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['lidsys.user.authenticator'] = $app->share(function ($app) {
            return new AuthenticatorService(
                $app['db'],
                $app['config']['auth']
            );
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
            return new UserService(
                $app['lidsys.user.authenticator'],
                $app['db']
            );
        });
    }



    public function boot(Application $app)
    {
    }
}
