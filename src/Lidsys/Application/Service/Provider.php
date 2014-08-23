<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Application\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['lidsys.app.mailer'] = $app->share(function ($app) {
            $config = $app['config']['mailer'];
            return new MailerService(
                $config['key'],
                $config['domain']
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
