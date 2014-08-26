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

        if (empty($user)) {
            return false;
        }

        $query_string = $this->createTokenQueryString($user['username']);

        $this->mailer->sendMessage(
            array(
                'to'      => "{$user['name']} <{$user['email']}>",
                'subject' => 'Lightdatasys Account Information',
                'text'    => <<<TEXT
Hi {$user['name']},

Your username for Lightdatasys is {$user['username']}.

If you forgot your password, you may reset your password by visiting

  {{BASE_URL}}/user/login-reset?{$query_string}

Have a wonderful day,

The Commissioner
Lightdatasys
http://lightdatasys.com
TEXT
                ,
                'html'    => <<<HTML
<p>Hi {$user['name']},</p>

<p>Your username for Lightdatasys is {$user['username']}.</p>

<p>
    If you forgot your password, you may
    <a href="{{BASE_URL}}/user/login-reset?{$query_string}">reset your password</a>.
</p>

<p>Have a wonderful day,</p>

<p>
    The Commissioner<br />
    <a href="http://lightdatasys.com">Lightdatasys</a>
</p>
HTML
                ,
            )
        );

        return true;
    }

    public function getUserFromTokenQueryString(array $params, $expiration)
    {
        if (time() > $params['timestamp'] + $expiration) {
            return false;
        }

        try {
            $this->validateTokenQueryString($params);
        } catch (Exception $exception) {
            return false;
        }

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

        $public_params['token'] = $this->generateToken($private_params);

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

        $correct_token = $this->generateToken($private_params);

        if ($correct_token !== $params['token']) {
            throw new Exception("The provided token is invalid.");
        }

        return true;
    }

    private function generateToken(array $params)
    {
        return substr(base64_encode(sha1(http_build_query($params))), 0, 12);
    }
}
