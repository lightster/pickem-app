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

use DOMDocument;
use DOMXPath;
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
                FROM nflSeason AS season
                WHERE EXISTS (
                    SELECT 1
                    FROM nflWeek AS week
                    JOIN nflGame AS game USING (weekId)
                    WHERE season.seasonId = week.seasonId
                )
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
                    year,
                    COUNT(DISTINCT game.gameId) AS game_count
                FROM nflWeek AS week
                JOIN nflSeason AS season USING (seasonId)
                JOIN nflGame AS game USING (weekId)
                WHERE year = :year
                GROUP BY weekId
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

    public function updateScores()
    {
        $html = file_get_contents(
            'http://www.nfl.com/liveupdate/scorestrip/ss.xml?random=' . microtime(true)
            //'http://www.nfl.com/liveupdate/scorestrip/postseason/ss.xml?random=' . microtime(true)
        );
        $dom            = new DOMDocument();
        libxml_use_internal_errors(true);

        $dom->loadXML($html);

        $xpath  = new DOMXPath($dom);

        $query  = '//ss/gms/g';
        $games  = $xpath->query($query);

        foreach ($games as $game) {
            $game_date = $game->getAttribute('eid');
            $date_sql  = substr($game_date, 0, 4)
                . '-' . substr($game_date, 4, 2)
                . '-' . substr($game_date, 6, 2);
            $time      = $game->getAttribute('q');

            $home_score     = $game->getAttribute('hs');
            $home_abbr      = $game->getAttribute('h');
            $away_score     = $game->getAttribute('vs');
            $away_abbr      = $game->getAttribute('v');

            if ($time === 'F' || $time === 'FO') {
                $this->db->query(
                    "
                        UPDATE nflGame AS game
                        JOIN nflWeek AS week USING (weekId)
                        JOIN nflTeam AS away
                            ON game.awayId = away.teamId
                        JOIN nflTeam AS home
                            ON game.homeId = home.teamId
                        SET awayScore = :away_score,
                            homeScore = :home_score
                        WHERE away.abbreviation = :away_team
                            AND home.abbreviation = :home_team
                            AND :game_date BETWEEN week.weekStart AND week.weekEnd
                    ",
                    array(
                        'away_score' => $away_score,
                        'home_score' => $home_score,
                        'away_team'  => $away_abbr,
                        'home_team'  => $home_abbr,
                        'game_date'  => $date_sql,
                    )
                );
            }
        }
    }
}
