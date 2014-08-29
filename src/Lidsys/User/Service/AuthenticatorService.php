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

    public function getUserForUsername($username)
    {
        return $this->findUserWithWhereClause(
            "
                WHERE u.username = :username
            ",
            array(
                'username' => $username,
            )
        );
    }

    public function getUserForUsernameAndPassword($username, $password)
    {
        return $this->findUserWithWhereClause(
            "
                WHERE u.username = :username
                    AND u.password = md5(concat(:password, u.securityHash))
            ",
            array(
                'username' => $username,
                'password' => md5($password),
            )
        );
    }

    public function getUserForUserIdAndPassword($user_id, $password)
    {
        return $this->findUserWithWhereClause(
            "
                WHERE u.userId = :user_id
                    AND u.password = md5(concat(:password, u.securityHash))
            ",
            array(
                'user_id' => $user_id,
                'password' => md5($password),
            )
        );
    }

    public function getUserForUserId($user_id)
    {
        return $this->findUserWithWhereClause(
            "
                WHERE u.userId = :user_id
            ",
            array(
                'user_id' => $user_id,
            )
        );
    }

    public function getUserForEmail($email)
    {
        return $this->findUserWithWhereClause(
            "
                WHERE u.email = :email
            ",
            array(
                'email' => $email,
            )
        );
    }

    public function updatePasswordForUser($user_id, $password)
    {
        $db = $this->app['db'];
        $db->query(
            "
                UPDATE user
                SET password = md5(concat(:password, securityHash)),
                    passwordDate = NOW()
                WHERE userId = :user_id
            ",
            array(
                'user_id'  => $user_id,
                'password' => md5($password),
            )
        );

        return true;
    }

    public function resetPasswordForUsername($username)
    {
        $characters = '0123456789'
            . 'abcdefghijklmnopqrstuvwxyz'
            . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            . '!@#$%^&*';
        $character_count = strlen($characters);

        $new_password = '';
        for ($i = 0; $i < 14; $i++) {
            $new_password .= $characters[mt_rand(0, $character_count - 1)];
        }

        $db = $this->app['db'];
        $db->query(
            "
                UPDATE user
                SET password = md5(concat(:password, securityHash))
                WHERE username = :username
            ",
            array(
                'username'  => $username,
                'password' => md5($new_password),
            )
        );

        return $new_password;
    }

    private function findUserWithWhereClause($where_sql, array $params)
    {
        $query = $this->app['db']->query(
            $this->getUserFindSql() . $where_sql,
            $params
        );
        return $query->fetch();
    }

    private function getUserFindSql()
    {
        return <<<'SQL'
SELECT
    u.userId AS user_id,
    u.username,
    u.email,
    u.timeZone AS time_zone,
    u.passwordDate AS password_date,
    p.playerId As player_id,
    p.name AS name,
    p.bgcolor AS background_color
FROM user AS u
JOIN player_user AS pu
    ON pu.userId = u.userId
JOIN player AS p
    ON p.playerId = pu.playerId
SQL
        ;
    }
}
