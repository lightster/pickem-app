<?php

namespace Lidsys\Application\Service;

use Lidsys\Application\View\ViewTransformer;

use Lstr\Silex\Database\DatabaseService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['config'] = $app->share(function ($app) {
            return $app['lstr.config']->load([
                __DIR__ . '/../../../../config/autoload/*.global.php',
                __DIR__ . '/../../../../config/autoload/*.local.php',
            ]);
        });

        $app['db'] = $app->share(function ($app) {
            return new DatabaseService($app, $app['config']['db.config']);
        });

        $app['mailer'] = $app->share(function ($app) {
            $mailer_config  = $app['config']['mailer'];
            $app_config     = $app['config']['app'];
            $commish_config = $app_config['commissioner'];

            $overrides = [];
            if ($app['debug']) {
                $overrides['to'] = $mailer_config['recipient_override'];
            }

            $options = [
                'substitutions' => [
                    '{{BASE_URL}}' => $app_config['base_url'],
                ],
                'defaults' => [
                    'from' => "{$commish_config['name']} <{$commish_config['email']}>",
                ],
                'overrides' => $overrides,
            ];

            if (!empty($mailer_config['api_endpoint'])) {
                $options['api_endpoint'] = $mailer_config['api_endpoint'];
            }
            if (array_key_exists('api_ssl', $mailer_config)) {
                $options['api_ssl'] = $mailer_config['api_ssl'];
            }

            return new MailerService($mailer_config['key'], $mailer_config['domain'], $options);
        });

        $app['view.transformer'] =$app->share(function ($app) {
            return new ViewTransformer();
        });
    }

    public function boot(Application $app)
    {
    }
}
