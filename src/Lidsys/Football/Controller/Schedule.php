<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Schedule
{
    public function __construct(Application $app)
    {
        var_dump($app);
    }



    public function get(Request $request)
    {
        $pdo   = $app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT userId
                FROM user
                WHERE username = :username
                    AND password = md5(concat(:password, securityHash))
            "
        );
        $query->execute(array(
            'username' => $request->get('username'),
            'password' => md5($request->get('password')),
        ));
        while ($row = $query->fetch()) {
            if ($row['userId']) {
                $authenticated = true;
            }
        }
    }
}
