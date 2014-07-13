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

use Silex\Application;

class UserService
{
    private $db;



    public function __construct(DatabaseService $db)
    {
        $this->db    = $db;
    }



    public function updateUserColor($user_id, $color)
    {
        $db = $this->db;
        $db->query(
            "
                UPDATE player
                SET bgcolor = :color
                WHERE playerId = (
                    SELECT playerId
                    FROM player_user
                    WHERE userId = :user_id
                )
            ",
            array(
                'color'   => $color,
                'user_id' => $user_id,
            )
        );

        return true;
    }
}
