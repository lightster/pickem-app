<?php
$dbParams = [
    'hostname'  => '',
    'username'  => '',
    'password'  => '',
    'database'  => '',
];
return [
    'db.config' => [
        'dsn'       => 'mysql:dbname='.$dbParams['database'].';host='.$dbParams['hostname'],
        'database'  => $dbParams['database'],
        'username'  => $dbParams['username'],
        'password'  => $dbParams['password'],
        'hostname'  => $dbParams['hostname'],
    ],
];
