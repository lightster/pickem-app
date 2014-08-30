<?php
$dbParams = array(
    'hostname'  => '',
    'username'  => '',
    'password'  => '',
    'database'  => '',
);
return array(
    'db.config' => array(
        'dsn'       => 'mysql:dbname='.$dbParams['database'].';host='.$dbParams['hostname'],
        'database'  => $dbParams['database'],
        'username'  => $dbParams['username'],
        'password'  => $dbParams['password'],
        'hostname'  => $dbParams['hostname'],
    ),
);
