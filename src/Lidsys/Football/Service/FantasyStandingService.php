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

class FantasyStandingService
{
    private $db;
    private $schedule;

    private $teams = null;



    public function __construct(DatabaseService $db, ScheduleService $schedule)
    {
        $this->db       = $db;
        $this->schedule = $schedule;
    }



    public function getFantasyStandingsForYear($year)
    {
        $player_stats = array();

        $seasons   = $this->schedule->getSeasons();
        $season_id = $seasons[$year]['season_id'];

        $db    = $this->db;
        $query = $db->query(
            "
                SELECT
                    p.playerId AS player_id,
                    w.weekId AS week_id,
                    SUM(
                        IF(
                            (fp.teamId=g.homeId AND g.homeScore>=g.awayScore)
                            OR (fp.teamId=g.awayId AND g.awayScore>=g.homeScore),
                            winWeight,
                            0
                        )
                    ) AS points,
                    COUNT(winWeight) AS potential_points
                FROM player AS p
                LEFT JOIN nflFantPick AS fp ON p.playerId = fp.playerId
                LEFT JOIN nflGame AS g ON fp.gameId=g.gameId
                LEFT JOIN nflWeek AS w ON g.weekId=w.weekId
                WHERE w.seasonId = :season_id
                GROUP BY g.weekId, p.playerId
            ",
            array(
                'season_id' => $season_id,
            )
        );
        while ($player = $query->fetch()) {
            $player_stats[$player['week_id']][$player['player_id']] = $player;
        }

        return $player_stats;
    }
}
