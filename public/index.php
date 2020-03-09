<?php

$top_dir = trim(substr($_SERVER['REQUEST_URI'], 0, 5), '/');
if ($top_dir !== 'app' && $top_dir !== 'api') {
    require_once __DIR__ . '/../bootstrap.php';

    \The\App::run(\The\WebContext::init());
    return;
}

$app = require_once __DIR__ . '/../silex-bootstrap.php';

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

    if (strpos($request->getPathInfo(), '/app/asset/') === 0) {
        return;
    }

    session_start([
        'lifetime' => strtotime("2 hours") - time(),
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'name'     => 'The',
    ]);

    $user_id = $_SESSION['user_id'];

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
