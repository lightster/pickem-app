<?php

require_once __DIR__ . '/vendor/autoload.php';

use function The\option;
use function The\service;
use The\Db;

option('root_dir', __DIR__);

option('db', service(function () {
    return new Db(getenv('DATABASE_URL'));
}));
