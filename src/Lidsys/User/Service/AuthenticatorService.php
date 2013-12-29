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

use Pdo;

use Silex\Application;

class AuthenticatorService
{
    private $app;



    public function __construct(Application $app)
    {
        $this->app    = $app;
    }



    public function getUserForUsernameAndPassword($username, $password)
    {
        $authenticated_user = false;

        $pdo   = $this->app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT
                    u.userId AS user_id,
                    u.username,
                    u.timeZone AS time_zone
                FROM user AS u
                WHERE u.username = :username
                    AND u.password = md5(concat(:password, u.securityHash))
            "
        );
        $query->execute(array(
            'username' => $username,
            'password' => md5($password),
        ));
        while ($row = $query->fetch()) {
            if ($row['username'] === $username) {
                $authenticated_user = $row;
            }
        }

        return $authenticated_user;
    }
}