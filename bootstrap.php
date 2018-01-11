<?php

require_once __DIR__ . '/vendor/autoload.php';

use Lidsys\Application\Application;

use Lstr\Silex\Asset\AssetServiceProvider;
use Lstr\Silex\Config\ConfigServiceProvider;
use Lstr\Silex\Database\DatabaseServiceProvider;
use Lstr\Silex\Template\TemplateServiceProvider;

use Lidsys\Application\Service\Provider as AppServiceProvider;
use Lidsys\Football\Service\Provider as FootballServiceProvider;
use Lidsys\User\Service\Provider as UserServiceProvider;

use Silex\Provider\SessionServiceProvider;

$app = new Application();
$app['route_class'] = 'Lidsys\Application\Route';

$app->register(new AppServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new ConfigServiceProvider());
$app->register(new DatabaseServiceProvider());
$app->register(new FootballServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new TemplateServiceProvider());
$app->register(new UserServiceProvider());

if (isset($app['config']['debug'])) {
    $app['debug'] = $app['config']['debug'];
}

return $app;
