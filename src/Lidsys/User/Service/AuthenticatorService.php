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

        $db    = $this->app['db'];
        $query = $db->query(
            "
                SELECT
                    u.userId AS user_id,
                    u.username,
                    u.timeZone AS time_zone
                FROM user AS u
                WHERE u.username = :username
                    AND u.password = md5(concat(:password, u.securityHash))
            ",
            array(
                'username' => $username,
                'password' => md5($password),
            )
        );
        while ($row = $query->fetch()) {
            if ($row['username'] === $username) {
                $authenticated_user = $row;
            }
        }

        return $authenticated_user;
    }

    public function getUserForUserIdAndPassword($user_id, $password)
    {
        $authenticated_user = false;

        $db    = $this->app['db'];
        $query = $db->query(
            "
                SELECT
                    u.userId AS user_id,
                    u.username,
                    u.timeZone AS time_zone
                FROM user AS u
                WHERE u.userId = :user_id
                    AND u.password = md5(concat(:password, u.securityHash))
            ",
            array(
                'user_id' => $user_id,
                'password' => md5($password),
            )
        );
        while ($row = $query->fetch()) {
            if ($row['user_id'] === $user_id) {
                $authenticated_user = $row;
            }
        }

        return $authenticated_user;
    }

    public function getUserForUserId($user_id)
    {
        $authenticated_user = false;

        $db    = $this->app['db'];
        $query = $db->query(
            "
                SELECT
                    u.userId AS user_id,
                    u.username,
                    u.timeZone AS time_zone
                FROM user AS u
                WHERE u.userId = :user_id
            ",
            array(
                'user_id' => $user_id,
            )
        );
        while ($row = $query->fetch()) {
            if ($row['user_id'] === $user_id) {
                $authenticated_user = $row;
            }
        }

        return $authenticated_user;
    }

    public function updatePasswordForUser($user_id, $password)
    {
        $db = $this->app['db'];
        $db->query(
            "
                UPDATE user
                SET password = md5(concat(:password, securityHash))
                WHERE userId = :user_id
            ",
            array(
                'user_id'  => $user_id,
                'password' => md5($password),
            )
        );

        return true;
    }
}
