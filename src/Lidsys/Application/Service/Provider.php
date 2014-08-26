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

use Lstr\Silex\Database\DatabaseService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['config'] = $app->share(function ($app) {
            return $app['lstr.config']->load(array(
                __DIR__ . '/../../../../config/autoload/*.global.php',
                __DIR__ . '/../../../../config/autoload/*.local.php',
            ));
        });

        $app['db'] = $app->share(function ($app) {
            return new DatabaseService($app, $app['config']['db.config']);
        });

        $app['mailer'] = $app->share(function ($app) {
            $mailer_config  = $app['config']['mailer'];
            $app_config     = $app['config']['app'];
            $commish_config = $app_config['commissioner'];
            return new MailerService(
                $mailer_config['key'],
                $mailer_config['domain'],
                array(
                    'substitutions' => array(
                        '{{BASE_URL}}' => $app_config['base_url'],
                    ),
                    'defaults' => array(
                        'from' => "{$commish_config['name']} <{$commish_config['email']}>",
                    ),
                )
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
