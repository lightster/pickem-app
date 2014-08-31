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

        $controllers->post('/login/reset-info/', function (Request $request, Application $app) {
            $user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->request->all(),
                60 * 60 * 24 // 24 hours
            );

            if ($user) {
                $user_info = array(
                    'username' => $user['username'],
                );
            } else {
                $user_info = array(
                    'error' => 'invalid_token',
                );
            }

            return $app->json($user_info);
        });

        $controllers->post('/login/reset-password/', function (Request $request, Application $app) {
            $authenticated_user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->get('authParams'),
                60 * 60 * 25 // 25 hours
            );

            if ($authenticated_user) {
                $password_change = $app['lidsys.user.authenticator']->updatePasswordForUser(
                    $authenticated_user['user_id'],
                    $request->get('newPassword')
                );
                if ($password_change) {
                    return $app->json(array(
                        'success' => 'Your password has successfully been changed.',
                    ));
                }
            }

            return $app->json(array(
                'error' => 'invalid_token',
            ));
        });

        $controllers->post('/login/', function (Request $request, Application $app) {
            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUsernameAndPassword(
                    $request->get('username'),
                    $request->get('password')
                );

            $response = array();

            $app['session']->remove('user_id');

            if ($authenticated_user) {
                if ($authenticated_user['password_changed_at']) {
                    $response['authenticated_user'] = $authenticated_user;
                    $app['session']->set(
                        'user_id',
                        $authenticated_user['user_id']
                    );
                } else {
                    $response['error'] = 'passwordless_account';
                }
            } else {
                $response['error'] = 'incorrect_credentials';
            }

            return $app->json($response);
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

        $controllers->post('/register/', function (Request $request, Application $app) {
            $new_user = $app['lidsys.user']->createUser(array(
                'email'      => $request->get('email'),
                'first_name' => $request->get('first_name'),
                'last_name'  => $request->get('last_name'),
            ));

            if (!empty($new_user['error'])) {
                return $app->json($new_user);
            }

            $is_found = $app['lidsys.user.auth-reset']->sendAccountSetupEmail(
                $new_user
            );

            $response = array();
            if ($is_found) {
                $response['success'] = array(
                    'form' => 'Your account verification email has been emailed to you.',
                );
            } else {
                $response['error']   = array(
                    'form' => 'There was an error creating your account. Please contact an administrator.',
                );
            }

            return $app->json($response);
        });

        $controllers->post('/register/token-verification/', function (Request $request, Application $app) {
            $user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->request->all(),
                60 * 60 * 24 * 7 // 7 days
            );

            if ($user && empty($user['password_changed_at'])) {
                $user_info = array(
                    'username' => $user['username'],
                );
            } else {
                $user_info = array(
                    'error' => 'invalid_token',
                );
            }

            return $app->json($user_info);
        });

        $controllers->post('/register/password/', function (Request $request, Application $app) {
            $user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->get('authParams'),
                60 * 60 * ((24 * 7) + 1) // 7 days plus an hour
            );

            if ($user && empty($user['password_changed_at'])) {
                $password_change = $app['lidsys.user.authenticator']->updatePasswordForUser(
                    $user['user_id'],
                    $request->get('newPassword')
                );
                if ($password_change) {
                    return $app->json(array(
                        'success' => 'Your password has successfully been saved.',
                    ));
                }
            }

            return $app->json(array(
                'error' => 'invalid_token',
            ));
        });

        $controllers->before(new JsonRequestMiddlewareService());

        return $controllers;
    }
}
