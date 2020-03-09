<?php

namespace App\Models;

use The\Model;

class User extends Model
{
    protected static $table_name  = 'users';
    protected static $primary_key = 'user_id';

    protected $data = [
        'user_id'             => Model::DEFAULT,
        'username'            => null,
        'password'            => null,
        'password_changed_at' => null,
        'security_hash'       => null,
        'email'               => null,
        'display_name'        => null,
        'display_color'       => null,
        'last_active_at'      => null,
        'created_at'          => Model::DEFAULT,
        'updated_at'          => Model::DEFAULT,
    ];
}
