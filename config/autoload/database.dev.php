<?php
$dbParams = [
    'hostname'  => 'mariadb',
    'username'  => 'lidsys',
    'password'  => 'lidsys',
    'database'  => 'dev_lidsys',
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
