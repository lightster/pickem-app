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

use Lstr\Silex\Database\DatabaseService;

class AuthenticatorService
{
    private $db;
    private $auth_config;

    public function __construct(DatabaseService $db, array $auth_config)
    {
        $this->db    = $db;
        $this->auth_config = $auth_config;
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
        $db = $this->db;
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

        $db = $this->db;
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

    public function findUsersActiveSince($last_active)
    {
        $sql = <<<SQL
{$this->getUserFindSql()}
WHERE lastActive >= :last_active
    AND email IS NOT NULL
SQL;
        $query = $this->db->query(
            $sql,
            array(
                'last_active' => $last_active,
            )
        );
        return $query;
    }

    public function getUserFromRememberMeTokenData(array $params)
    {
        try {
            $this->validateRememberMeTokenData($params);
        } catch (Exception $exception) {
            return false;
        }

        return $this->getUserForUsername($params['username']);
    }

    public function createRememberMeTokenData($username)
    {
        $public_params = array(
            'username'  => $username,
            'timestamp' => time(),
        );

        $private_params                = $public_params;
        $private_params['private-key'] = $this->auth_config['remember-me']['private-key'];

        ksort($private_params);

        $public_params['token'] = $this->generateToken($private_params);

        return $public_params;
    }

    private function validateRememberMeTokenData(array $params)
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
        return base64_encode(sha1(http_build_query($params)));
    }

    private function findUserWithWhereClause($where_sql, array $params)
    {
        $query = $this->db->query(
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
    u.passwordDate AS password_changed_at,
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
