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

        $controllers->post('/login/help/', function (Request $request, Application $app) {
            $is_found = $app['lidsys.user.auth-reset']->sendResetEmail(
                $request->get('email')
            );

            $response = array();
            if ($is_found) {
                $response['success'] = 'Your account information has been emailed to you.';
            } else {
                $response['error']   = 'The email address you provided is not registered with Lightdatasys.';
            }

            return $app->json($response);
        });

        $controllers->get('/login/reset-info/', function (Request $request, Application $app) {
            $user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->query->all(),
                60 * 60 * 12 // 12 hours
            );

            return $app->json(array(
                'username' => $user['username'],
            ));
        });

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
                    $password_change = $app['lidsys.user.authenticator']->updatePasswordForUser(
                        $user_id,
                        $request->get('newPassword')
                    );
                    if ($password_change) {
                        return $app->json(array(
                            'success' => 'Your password has successfully been changed.',
                        ));
                    } else {
                        $error = 'An error occurred. Your password change was not saved.';
                    }
                }
            } else {
                $error = 'The user you are logged in as could not be determined.';
            }

            return $app->json(array(
                'error' => $error,
            ));
        });

        $controllers->post('/user-profile/color/', function (Request $request, Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUserId($user_id);

            if (!$authenticated_user) {
                return $app->json(array(
                    'error' => 'The user you are logged in as could not be determined.',
                ));
            }

            if ($app['lidsys.user']->updateUserColor($user_id, $request->get('background_color'))) {
                return $app->json(array(
                    'success' => 'Your new color has been saved.',
                ));
            } else {
                return $app->json(array(
                    'error' => 'An error occurred. Your color change was not saved.',
                ));
            }
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
