<?php

namespace Lidsys\Football\Service;

use The\Db;

class FantasyPlayerService
{
    private $db;



    public function __construct(Db $db)
    {
        $this->db    = $db;
    }



    public function getPlayersForYear($year, $user_id)
    {
        $players = array();

        $db    = $this->db;
        $query = $db->query(
            <<<'SQL'
            SELECT
                user_id AS player_id,
                display_name AS name,
                display_color AS background_color
            FROM users
            WHERE user_id = $1
                OR EXISTS (
                    SELECT 1
                    FROM picks
                    JOIN games USING (game_id)
                    JOIN weeks USING (week_id)
                    JOIN seasons USING (season_id)
                    WHERE year = $2
                        AND users.user_id = picks.user_id
                )
            SQL,
            [$user_id, $year]
        );
        while ($player = $query->fetchRow()) {
            $names = explode(" ", $player['name']);
            foreach ($names as $name_i => $name_part) {
                if ($name_i > 0) {
                    $name_part = substr($name_part, 0, 1);
                }
                $names[$name_i] = $name_part;
            }

            $perceived_luminance = (
                  0.299 * hexdec(substr($player['background_color'], 0, 2))
                + 0.587 * hexdec(substr($player['background_color'], 2, 2))
                + 0.114 * hexdec(substr($player['background_color'], 4, 2))
            ) / 255;

            $player['name'] = implode(" ", $names);
            $player['text_color'] = (
                $perceived_luminance >= 0.5
                ? '000000'
                : 'ffffff'
            );

            $players[$player['player_id']] = $player;
        }

        return $players;
    }
}
