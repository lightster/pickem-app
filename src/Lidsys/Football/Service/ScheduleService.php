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

use Lstr\Silex\Database\DatabaseService;

class ScheduleService
{
    private $db;

    private $seasons                   = null;
    private $weeks                     = array();
    private $week_numbers_for_week_ids = array();



    public function __construct(DatabaseService $db)
    {
        $this->db    = $db;
    }



    public function getSeasons()
    {
        if (isset($this->seasons)) {
            return $this->seasons;
        }

        $seasons       = array();

        $db    = $this->db;
        $query = $db->query(
            "
                SELECT
                    seasonId AS season_id,
                    year AS year
                FROM nflSeason
                ORDER BY year
            "
        );
        while ($season = $query->fetch()) {
            $seasons[$season['year']] = $season;
        }

        $this->seasons = $seasons;

        return $this->seasons;
    }



    public function getWeeksForYear($year)
    {
        if (isset($this->weeks[$year])) {
            return $this->weeks[$year];
        }

        $weeks       = array();
        $week_number = 0;

        $db    = $this->db;
        $query = $db->query(
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
            ",
            array(
                'year' => $year,
            )
        );
        while ($week = $query->fetch()) {
            ++$week_number;

            $week['week_number'] = $week_number;
            $weeks[$week_number] = $week;
        }

        $this->weeks[$year] = $weeks;

        return $this->weeks[$year];
    }



    private function getYearForWeekId($week_id)
    {
        $db    = $this->db;
        $query = $db->query(
            "
                SELECT year
                FROM nflWeek AS week
                JOIN nflSeason AS season USING (seasonId)
                WHERE weekId = :week_id
            ",
            array(
                'week_id' => $week_id,
            )
        );
        while ($week = $query->fetch()) {
            return $week['year'];
        }

        throw new Exception("Could not determine year for week_id '{$week_id}'.");
    }



    public function getWeekNumberForWeekId($week_id)
    {
        if (isset($this->week_numbers_for_week_ids[$week_id])) {
            return $this->week_numbers_for_week_ids[$week_id];
        }

        $year  = $this->getYearForWeekId($week_id);
        $weeks = $this->getWeeksForYear($year);

        foreach ($weeks as $week_number => $week) {
            $this->week_numbers_for_week_ids[$week['week_id']] = $week_number;
        }

        return $this->week_numbers_for_week_ids[$week_id];
    }



    public function getGamesForWeek($year, $week_number)
    {
        $weeks = $this->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception\WeekNotFound($year, $week_number);
        }

        $week  = $weeks[$week_number];

        $games = array();

        $db    = $this->db;
        $query = $db->query(
            "
                SELECT
                    gameId AS game_id,
                    gameTime AS start_time,
                    awayId AS away_team_id,
                    homeId AS home_team_id,
                    awayScore AS away_score,
                    homeScore AS home_score
                FROM nflGame
                WHERE DATE(gameTime) BETWEEN :start_date AND :end_date
                ORDER BY gameTime, gameId
            ",
            array(
                'start_date' => $week['start_date'],
                'end_date'   => $week['end_date'],
            )
        );
        while ($game = $query->fetch()) {
            $games[] = $game;
        }

        return $games;
    }
}
