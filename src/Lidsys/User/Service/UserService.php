<?php

namespace Lidsys\User\Service;

use The\Db;
use The\DbExpr;

class UserService
{
    private $auth;
    private $db;



    public function __construct(
        AuthenticatorService $auth,
        Db $db
    ) {
        $this->auth  = $auth;
        $this->db    = $db;
    }

    public function createUser($user_info)
    {
        $db = $this->db;

        $errors = [];

        if (strlen($user_info['first_name']) < 3) {
            $errors['first_name'] = 'Please enter at least 3 characters of your first name.';
        }
        if (strlen($user_info['last_name']) > 1) {
            $errors['last_name'] = 'Please enter only the first letter of your last name.';
        } elseif (strlen($user_info['last_name']) < 1) {
            $errors['last_name'] = 'Please enter the first letter of your last name.';
        }

        if (!preg_match('#\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}\b#', $user_info['email'])) {
            $errors['email'] = 'The email address you entered is invalid.';
        } else {
            $email_exists = $db->exists(
                <<<'SQL'
                SELECT 1
                FROM users
                WHERE email = $1
                SQL,
                [$user_info['email']]
            );
            if ($email_exists) {
                $errors['email'] = 'The email address you entered is already in use.';
            }
        }

        if (count($errors)) {
            return ['error' => $errors];
        }

        $user_row = $db->insert(
            'users',
            [
                'username'      => $user_info['email'],
                'email'         => $user_info['email'],
                'password'      => md5(microtime()),
                'security_hash' => substr(base64_encode(md5(microtime())), 0, 4),
                'display_name'  => $user_info['first_name'] . ' ' . $user_info['last_name'],
                'display_color' => substr(md5(microtime()), 0, 6),
            ]
        );

        return $this->auth->getUserForUserId($user_row['user_id']);
    }

    public function updateUserColor($user_id, $color)
    {
        $db = $this->db;
        $db->update(
            'users',
            ['display_color' => $color],
            'user_id = $1',
            [$user_id]
        );

        return true;
    }

    public function updateLastActive($user_id)
    {
        $db = $this->db;
        $db->update(
            'users',
            ['last_active_at' => new DbExpr('NOW()')],
            'user_id = $1',
            [$user_id]
        );
    }
}
