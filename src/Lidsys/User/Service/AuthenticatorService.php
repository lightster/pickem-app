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



    public function verifyUsernameAndPassword($username, $password)
    {
        $authenticated = false;

        $pdo   = $this->app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT userId
                FROM user
                WHERE username = :username
                    AND password = md5(concat(:password, securityHash))
            "
        );
        $query->execute(array(
            'username' => $username,
            'password' => md5($password),
        ));
        while ($row = $query->fetch()) {
            if ($row['userId']) {
                $authenticated = true;
            }
        }

        return $authenticated;
    }
}
