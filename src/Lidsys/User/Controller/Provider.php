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

use Lstr\Silex\Template\Exception\TemplateNotFound;
use Lstr\Silex\Controller\JsonRequestMiddlewareService;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lstr.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->post('/login/', function (Request $request, Application $app) {
            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUsernameAndPassword(
                    $request->get('username'),
                    $request->get('password')
                );

            if ($authenticated_user) {
                $app['session']->set(
                    'user_id',
                    $authenticated_user['user_id']
                );
            } else {
                $app['session']->remove('user_id');
            }

            return $app->json(array(
                'authenticated_user' => $authenticated_user,
            ));
        });

        $controllers->post('/authenticated-user/', function (Request $request, Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user = false;

            if ($user_id) {
                $authenticated_user =
                    $app['lidsys.user.authenticator']->getUserForUserId(
                        $user_id
                    );
            }

            return $app->json(array(
                'authenticated_user' => $authenticated_user,
            ));
        });

        $controllers->before(new JsonRequestMiddlewareService());

        return $controllers;
    }
}
