<?php

use function The\db;

// Add users table

if ($rollback === true) {
    db()->query("DROP TABLE users;");
    return true;
}

$sql = <<<SQL
CREATE TABLE users (
    user_id serial PRIMARY KEY,
    username varchar NOT NULL UNIQUE,
    password varchar NOT NULL,
    password_changed_at timestamp with time zone,
    security_hash varchar NOT NULL,
    email varchar UNIQUE,
    display_name varchar NOT NULL,
    display_color varchar NOT NULL,
    last_active_at timestamptz,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);
SQL;

db()->query($sql);
