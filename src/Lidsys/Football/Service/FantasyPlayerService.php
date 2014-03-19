<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\Service;

use Silex\Application;

class FantasyPlayerService
{
    private $app;



    public function __construct(Application $app)
    {
        $this->app    = $app;
    }



    public function getPlayersForYear($year)
    {
        $players = array();

        $db    = $this->app['db'];
        $query = $db->query(
            "
                SELECT
                    playerId AS player_id,
                    name AS name,
                    bgcolor AS background_color
                FROM player
                JOIN nflFantPick pick USING (playerId)
                JOIN nflGame game USING (gameId)
                JOIN nflWeek week USING (weekId)
                JOIN nflSeason season USING (seasonId)
                WHERE year = :year
            ",
            array(
                'year' => $year,
            )
        );
        while ($player = $query->fetch()) {
            $names = explode(" ", $player['name']);
            foreach ($names as $name_i => $name_part) {
                if ($name_i > 0) {
                    $name_part = substr($name_part, 0, 1);
                }
                $names[$name_i] = $name_part;
            }
            $player['name'] = implode(" ", $names);

            $players[$player['player_id']] = $player;
        }

        return $players;
    }
}
