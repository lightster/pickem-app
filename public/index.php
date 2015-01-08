<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

$app = require_once __DIR__ . '/../bootstrap.php';

use Lidsys\Application\Controller\Provider as AppControllerProvider;
use Lidsys\Football\Controller\Provider as FootballControllerProvider;
use Lidsys\User\Controller\Provider as UserControllerProvider;

use Symfony\Component\HttpFoundation\Request;

$app->before(function (Request $request) use ($app) {
    if (strpos($request->getPathInfo(), '/app/build-number') === 0) {
        return;
    }

    $user_id = $app['session']->get('user_id');

    $authenticated_user = false;

    if ($user_id) {
        $authenticated_user =
            $app['lidsys.user.authenticator']->getUserForUserId(
                $user_id
            );
    }

    if ($authenticated_user) {
        $app['lidsys.user']->updateLastActive($authenticated_user['user_id']);
    }
});

$app->mount('/api/v1.0/football', new FootballControllerProvider());
$app->mount('/app/user', new UserControllerProvider());
$app->mount('/app', new AppControllerProvider());

$app->get('/', function () use ($app) {
    return $app->redirect('/app/');
});

$app->run();
