<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\User\Controller;

use Lidsys\Silex\Service\Exception\TemplateNotFound;
use Lidsys\Silex\Service\JsonRequestMiddlewareService;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lidsys.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->post('/login/', function (Request $request) use ($app) {
            $authenticated = false;

            $pdo   = $app['db']->getPdo();
            $query = $pdo->prepare("
                SELECT userId
                FROM user
                WHERE username = :username
                    AND password = md5(concat(:password, securityHash))
            ");
            $query->execute(array(
                'username' => $request->get('username'),
                'password' => md5($request->get('password')),
            ));
            while ($row = $query->fetch()) {
                if ($row['userId']) {
                    $authenticated = true;
                }
            }

            return $app->json(array(
                'authenticated' => $authenticated,
            ));
        });

        $controllers->before(new JsonRequestMiddlewareService());

        return $controllers;
    }
}
