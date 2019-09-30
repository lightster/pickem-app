<?php

namespace Lidsys\Football\Service;

use The\Db;

class FantasyStandingService
{
    private $db;
    private $schedule;

    private $teams = null;



    public function __construct(Db $db, ScheduleService $schedule)
    {
        $this->db       = $db;
        $this->schedule = $schedule;
    }



    public function getFantasyStandingsForYear($year)
    {
        $player_stats = array();

        $schedule_service = $this->schedule;
        $seasons          = $schedule_service->getSeasons();
        $season_id        = $seasons[$year]['season_id'];

        $db    = $this->db;
        $query = $db->query(
            <<<'SQL'
                SELECT
                    p.user_id AS player_id,
                    g.week_id,
                    SUM(
                        CASE WHEN
                            (fp.team_id = g.home_team_id AND g.home_score >= g.away_score)
                            OR (fp.team_id = g.away_team_id AND g.away_score >= g.home_score)
                        THEN win_weight
                        ELSE 0
                        END
                    ) AS points,
                    SUM(
                        CASE WHEN
                            g.home_score IS NULL OR g.away_score IS NULL
                        THEN 0
                        ELSE win_weight
                        END
                    ) AS potential_points
                FROM users AS p
                LEFT JOIN picks AS fp ON p.user_id = fp.user_id
                LEFT JOIN games AS g ON fp.game_id = g.game_id
                LEFT JOIN weeks AS w ON g.week_id = w.week_id
                WHERE w.season_id = $1
                GROUP BY g.week_id, p.user_id
            SQL,
            [$season_id]
        );
        while ($player = $query->fetchRow()) {
            $week_number = $schedule_service->getWeekNumberForWeekId($player['week_id']);
            $player_stats[$week_number][$player['player_id']] = $player;
        }

        return $player_stats;
    }
}
