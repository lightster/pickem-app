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

class ScheduleService
{
    private $app;

    private $weeks = array();



    public function __construct(Application $app)
    {
        $this->app    = $app;
    }



    public function getSeasons()
    {
        if (isset($this->years)) {
            return $this->years;
        }

        $years       = array();

        $pdo   = $this->app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT
                    seasonId AS season_id,
                    year AS year
                FROM nflSeason
                ORDER BY year
            "
        );
        $query->execute(array(
        ));
        while ($year = $query->fetch()) {
            $years[] = $year;
        }

        $this->years = $years;

        return $this->years;
    }



    public function getWeeksForYear($year)
    {
        if (isset($this->weeks[$year])) {
            return $this->weeks[$year];
        }

        $weeks       = array();
        $week_number = 0;

        $pdo   = $this->app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT
                    weekId AS week_id,
                    seasonId AS season_id,
                    weekStart AS start_date,
                    weekEnd AS end_date,
                    winWeight AS win_weight,
                    year
                FROM nflWeek AS week
                JOIN nflSeason AS season USING (seasonId)
                WHERE year = :year
                ORDER BY weekStart
            "
        );
        $query->execute(array(
            'year' => $year,
        ));
        while ($week = $query->fetch()) {
            ++$week_number;

            $week['week_number'] = $week_number;
            $weeks[$week_number] = $week;
        }

        $this->weeks[$year] = $weeks;

        return $this->weeks[$year];
    }



    public function getGamesForWeek($year, $week_number)
    {
        $weeks = $this->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception\WeekNotFound($year, $week_number);
        }

        $week  = $weeks[$week_number];

        $games = array();

        $pdo   = $this->app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT
                    gameId AS game_id,
                    gameTime AS start_time,
                    awayId AS away_team_id,
                    homeId AS home_team_id,
                    awayScore AS away_score,
                    homeScore AS home_score
                FROM nflGame
                WHERE gameTime BETWEEN :start_date AND :end_date
                ORDER BY gameTime, gameId
            "
        );
        $query->execute(array(
            'start_date' => $week['start_date'],
            'end_date'   => $week['end_date'],
        ));
        while ($game = $query->fetch()) {
            $games[] = $game;
        }

        return $games;
    }
}
