<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\User\Service;

use Exception;

use Lidsys\Application\Service\MailerService;

use Lstr\Silex\Database\DatabaseService;

class AuthenticationResetService
{
    private $auth;
    private $db;
    private $mailer;
    private $auth_config;

    public function __construct(
        AuthenticatorService $auth,
        DatabaseService $db,
        MailerService $mailer,
        array $auth_config
    ) {
        $this->auth        = $auth;
        $this->db          = $db;
        $this->mailer      = $mailer;
        $this->auth_config = $auth_config;
    }

    public function sendResetEmail($email)
    {
        $user = $this->auth->getUserForEmail($email);

        return $this->createTokenQueryString($user['username']);
    }

    public function getUserFromTokenQueryString(array $params, $expiration)
    {
        $this->validateTokenQueryString($params);

        return $this->auth->getUserForUsername($params['username']);
    }

    private function createTokenQueryString($username)
    {
        $public_params = array(
            'username'  => $username,
            'timestamp' => time(),
        );

        $private_params                = $public_params;
        $private_params['private-key'] = $this->auth_config['reset']['private-key'];

        ksort($private_params);

        $public_params['token'] = sha1(http_build_query($private_params));

        return http_build_query($public_params);
    }

    private function validateTokenQueryString(array $params)
    {
        if (!isset($params['username'])) {
            throw new Exception("Parameter 'username' is missing.");
        }
        if (!isset($params['timestamp'])) {
            throw new Exception("Parameter 'timestamp' is missing.");
        }
        if (!isset($params['token'])) {
            throw new Exception("Parameter 'token' is missing.");
        }

        $private_params = array(
            'username'    => $params['username'],
            'timestamp'   => $params['timestamp'],
            'private-key' => $this->auth_config['reset']['private-key'],
        );

        ksort($private_params);

        $correct_token = sha1(http_build_query($private_params));

        if ($correct_token !== $params['token']) {
            throw new Exception("The provided token is invalid.");
        }

        return true;
    }
}
