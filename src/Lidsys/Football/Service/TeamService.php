<?php

namespace Lidsys\Football\Service;

use The\Db;

class TeamService
{
    private $db;
    private $schedule;

    private $teams = null;



    public function __construct(Db $db, ScheduleService $schedule)
    {
        $this->db       = $db;
        $this->schedule = $schedule;
    }



    public function getTeams()
    {
        if (isset($this->teams)) {
            return $this->teams;
        }

        $this->teams = array();

        $db    = $this->db;
        $query = $db->query(
            <<<'SQL'
            SELECT
                team_id,
                location,
                mascot,
                abbreviation,
                conference,
                division,
                primary_font_color AS font_color,
                primary_background_color AS background_color,
                secondary_background_color AS border_color
            FROM teams
            SQL
        );
        while ($team = $query->fetchRow()) {
            $this->teams[$team['team_id']] = $team;
        }

        return $this->teams;
    }



    public function getStandingsForWeek($year, $week_number)
    {
        $weeks = $this->schedule->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception\WeekNotFound($year, $week_number);
        }

        $first_week     = $weeks[1];
        $selected_week  = $weeks[$week_number];

        $team_standings = array();

        $db    = $this->db;
        $query = $db->query(
            <<<'SQL'
            SELECT
                team_id,
                SUM(
                    CASE WHEN
                        g.away_score IS NOT NULL
                        AND g.home_score IS NOT NULL
                        AND (
                            (t.team_id = g.away_team_id AND g.away_score > g.home_score)
                            OR (t.team_id = g.home_team_id AND g.home_score > g.away_score)
                        )
                    THEN 1
                    ELSE 0
                    END
                ) AS win_count,
                SUM(
                    CASE WHEN
                        g.away_score IS NOT NULL
                        AND g.home_score IS NOT NULL
                        AND (
                            (t.team_id = g.away_team_id AND g.away_score < g.home_score)
                            OR (t.team_id = g.home_team_id AND g.home_score < g.away_score)
                        )
                    THEN 1
                    ELSE 0
                    END
                ) AS loss_count,
                SUM(
                    CASE WHEN
                        g.away_score IS NOT NULL
                        AND g.home_score IS NOT NULL
                        AND (
                            (t.team_id = g.away_team_id AND g.away_score = g.home_score)
                            OR (t.team_id = g.home_team_id AND g.home_score = g.away_score)
                        )
                    THEN 1
                    ELSE 0
                    END
                ) AS tie_count
            FROM teams AS t
            JOIN games AS g ON t.team_id = g.away_team_id OR t.team_id = g.home_team_id
            WHERE game_time BETWEEN $1 AND $2
            GROUP BY t.team_id
            ORDER BY win_count DESC, tie_count DESC, loss_count DESC
            SQL,
            [$first_week['start_at'], $selected_week['end_at']]
        );
        while ($team_standing = $query->fetchRow()) {
            $team_standings[] = $team_standing;
        }

        return $team_standings;
    }
}
