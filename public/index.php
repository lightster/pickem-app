<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Lidsys\Application\Controller\Provider as AppControllerProvider;
use Lidsys\Football\Controller\Provider as FootballControllerProvider;
use Lidsys\User\Controller\Provider as UserControllerProvider;

use Lidsys\Silex\Provider\ConfigServiceProvider;
use Lidsys\Silex\Provider\TemplateServiceProvider;

use Silex\Application;

$app = new Application();

$app['debug'] = true;

$app->register(new ConfigServiceProvider());
$app->register(new TemplateServiceProvider());

$app['config'] = $app['lidsys.config']->load(array(
    __DIR__ . '/../config/autoload/*.global.php',
    __DIR__ . '/../config/autoload/*.local.php',
));

$app->mount('/api/football', new FootballControllerProvider());
$app->mount('/user', new UserControllerProvider());
$app->mount('/app', new AppControllerProvider());

$app->get('/', function () use ($app) {
    return $app->redirect('/app/');
});


$app->run();
