<?php

namespace Lidsys\User\Service;

use Exception;

use The\Db;

class AuthenticatorService
{
    private $db;
    private $auth_config;

    public function __construct(Db $db, array $auth_config)
    {
        $this->db    = $db;
        $this->auth_config = $auth_config;
    }

    public function getUserForUsername($username)
    {
        return $this->findUserWithWhereClause('WHERE username = $1', [$username]);
    }

    public function getUserForUsernameAndPassword($username, $password)
    {
        return $this->findUserWithWhereClause(
            'WHERE username = $1 AND password = md5(concat($2::varchar, security_hash))',
            [$username, md5($password)]
        );
    }

    public function getUserForUserIdAndPassword($user_id, $password)
    {
        return $this->findUserWithWhereClause(
            'WHERE user_id = $1 AND password = md5(concat($2::varchar, security_hash))',
            [$user_id, md5($password)]
        );
    }

    public function getUserForUserId($user_id)
    {
        return $this->findUserWithWhereClause(
            'WHERE user_id = $1',
            [$user_id]
        );
    }

    public function getUserForEmail($email)
    {
        return $this->findUserWithWhereClause(
            'WHERE email = $1',
            [$email]
        );
    }

    public function updatePasswordForUser($user_id, $password)
    {
        $db = $this->db;
        $db->query(
            <<<'SQL'
            UPDATE users
            SET password = md5(concat($1::varchar, security_hash)),
                password_changed_at = NOW()
            WHERE user_id = $2
            SQL,
            [md5($password), $user_id]
        );

        return true;
    }

    public function findUsersActiveSince($last_active)
    {
        $sql = <<<SQL
        {$this->getUserFindSql()}
        WHERE last_active_at >= $1
            AND email IS NOT NULL
        SQL;
        $query = $this->db->query($sql, [$last_active]);

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
        $public_params = [
            'username'  => $username,
            'timestamp' => time(),
        ];

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

        $private_params = [
            'username'    => $params['username'],
            'timestamp'   => $params['timestamp'],
            'private-key' => $this->auth_config['remember-me']['private-key'],
        ];

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
            $this->getUserFindSql() . " {$where_sql}",
            $params
        );
        return $query->fetchRow();
    }

    private function getUserFindSql()
    {
        return <<<'SQL'
        SELECT
            user_id,
            username,
            email,
            password_changed_at,
            user_id AS player_id,
            display_name AS name,
            display_color AS background_color
        FROM users
        SQL;
    }
}
