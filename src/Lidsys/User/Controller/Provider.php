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

        $controllers->post('/password/', function (Request $request, Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user = false;

            if (!$request->get('currentPassword')) {
                $error = 'Your current password is required.';
            } elseif (!$request->get('newPassword')) {
                $error = 'A new password is required.';
            } elseif ($user_id) {
                $authenticated_user =
                    $app['lidsys.user.authenticator']->getUserForUserIdAndPassword(
                        $user_id,
                        $request->get('currentPassword')
                    );

                if (!$authenticated_user) {
                    $error = 'The current password you entered could not be verified.';
                } else {
                    $error = 'This feature has not yet been implemented.';
                }
            } else {
                $error = 'The user you are logged in as could not be determined.';
            }

            return $app->json(array(
                'error' => $error,
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

        $controllers->post('/logout/', function (Request $request, Application $app) {
            $app['session']->remove('user_id');

            return $app->json(array(
                'logged_out' => true,
            ));
        });

        $controllers->before(new JsonRequestMiddlewareService());

        return $controllers;
    }
}
