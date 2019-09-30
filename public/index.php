<?php

$app = require_once __DIR__ . '/../bootstrap.php';

use Lidsys\Application\Controller\Provider as AppControllerProvider;
use Lidsys\Football\Controller\Provider as FootballControllerProvider;
use Lidsys\User\Controller\Provider as UserControllerProvider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use The\DbException;

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

$app->error(function (DbException $e, $code) {
    return new Response($e->getLastError());
});
$app->run();
