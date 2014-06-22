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

use Lstr\Silex\Database\DatabaseService;

class FantasyPlayerService
{
    private $db;



    public function __construct(DatabaseService $db)
    {
        $this->db    = $db;
    }



    public function getPlayersForYear($year)
    {
        $players = array();

        $db    = $this->db;
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

            $perceived_luminance = 1 - (
                  0.299 * hexdec(substr($player['background_color'], 0, 2))
                + 0.587 * hexdec(substr($player['background_color'], 2, 2))
                + 0.114 * hexdec(substr($player['background_color'], 4, 2))
            ) / 255;

            $player['name'] = implode(" ", $names);
            $player['text_color'] = (
                $perceived_luminance < 0.5
                ? '000000'
                : 'ffffff'
            );

            $players[$player['player_id']] = $player;
        }

        return $players;
    }
}
