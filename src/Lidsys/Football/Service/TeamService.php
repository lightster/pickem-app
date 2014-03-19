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

use Pdo;

use Silex\Application;

class TeamService
{
    private $app;

    private $teams = null;



    public function __construct(Application $app)
    {
        $this->app    = $app;
    }



    public function getTeams()
    {
        if (isset($this->teams)) {
            return $this->teams;
        }

        $this->teams = array();

        $db    = $this->app['db'];
        $query = $db->query(
            "
                SELECT
                    teamId AS team_id,
                    location,
                    mascot,
                    abbreviation,
                    conference,
                    division,
                    fontColor AS font_color,
                    background AS background_color,
                    borderColor AS border_color
                FROM nflTeam
            "
        );
        while ($team = $query->fetch()) {
            $this->teams[$team['team_id']] = $team;
        }

        return $this->teams;
    }



    public function getStandingsForWeek($year, $week_number)
    {
        $weeks = $this->app['lidsys.football.schedule']->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception\WeekNotFound($year, $week_number);
        }

        $first_week     = $weeks[1];
        $selected_week  = $weeks[$week_number];

        $team_standings = array();

        $db    = $this->app['db'];
        $query = $db->query(
            "
                SELECT
                    t.teamId AS team_id,
                    SUM(
                        IF(
                            g.awayScore IS NOT NULL
                            AND g.homeScore IS NOT NULL
                            AND (
                                (t.teamId = g.awayId AND g.awayScore > g.homeScore)
                                OR (t.teamId = g.homeId AND g.homeScore > g.awayScore)
                            ),
                            1,
                            0
                        )
                    ) AS win_count,
                    SUM(
                        IF(
                            g.awayScore IS NOT NULL
                            AND g.homeScore IS NOT NULL
                            AND (
                                (t.teamId = g.awayId AND g.awayScore < g.homeScore)
                                OR (t.teamId = g.homeId AND g.homeScore < g.awayScore)
                            ),
                            1,
                            0
                        )
                    ) AS loss_count,
                    SUM(
                        IF(
                            g.awayScore IS NOT NULL
                            AND g.homeScore IS NOT NULL
                            AND (
                                (t.teamId = g.awayId AND g.awayScore = g.homeScore)
                                OR (t.teamId = g.homeId AND g.homeScore = g.awayScore)
                            ),
                            1,
                            0
                        )
                    ) AS tie_count
                FROM nflTeam AS t
                JOIN nflGame AS g
                    ON t.teamId = g.awayId
                    OR t.teamId = g.homeId
                WHERE DATE(gameTime) BETWEEN :start_date AND :end_date
                GROUP BY t.teamId
                ORDER BY win_count DESC, tie_count DESC, loss_count DESC
            ",
            array(
                'start_date' => $first_week['start_date'],
                'end_date'   => $selected_week['end_date'],
            )
        );
        while ($team_standing = $query->fetch()) {
            $team_standings[] = $team_standing;
        }

        return $team_standings;
    }
}
