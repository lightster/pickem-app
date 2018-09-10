<?php

namespace Lidsys\Football\Service;

use DateTime;
use DOMDocument;
use DOMXPath;
use Exception;

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

        $sql = <<<SQL
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
SQL;

        $db    = $this->db;
        $query = $db->query($sql);
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

        $sql = <<<SQL
SELECT
    weekId AS week_id,
    seasonId AS season_id,
    weekStart AS start_date,
    weekEnd AS end_date,
    winWeight AS win_weight,
    year,
    COUNT(DISTINCT game.gameId) AS game_count,
    SUM(IF(
        COALESCE(game.awayScore, game.homeScore) IS NOT NULL,
        1,
        0
    )) AS games_played
FROM nflWeek AS week
JOIN nflSeason AS season USING (seasonId)
JOIN nflGame AS game USING (weekId)
WHERE year = :year
GROUP BY weekId
ORDER BY weekStart
SQL;

        $db    = $this->db;
        $query = $db->query(
            $sql,
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

    public function getWeekForDate($date)
    {
        $sql = <<<SQL
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
WHERE DATE(:date) BETWEEN weekStart AND weekEnd
GROUP BY week_id
LIMIT 1
SQL;

        $db    = $this->db;
        $query = $db->query(
            $sql,
            array(
                'date' => $date,
            )
        );
        $week = $query->fetch();

        if ($week) {
            $week['week_number'] = $this->getWeekNumberForWeekId($week['week_id']);
        }

        return $week;
    }



    private function getYearForWeekId($week_id)
    {
        $sql = <<<SQL
SELECT year
FROM nflWeek AS week
JOIN nflSeason AS season USING (seasonId)
WHERE weekId = :week_id
SQL;

        $db    = $this->db;
        $query = $db->query(
            $sql,
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

        $sql = <<<SQL
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
SQL;

        $db    = $this->db;
        $query = $db->query(
            $sql,
            array(
                'start_date' => $week['start_date'],
                'end_date'   => $week['end_date'],
            )
        );
        while ($game = $query->fetch()) {
            if ($game['away_score'] || $game['home_score']) {
                $game['away_score'] = intval($game['away_score']);
                $game['home_score'] = intval($game['home_score']);
            }
            $games[] = $game;
        }

        return $games;
    }

    public function updateScores()
    {
        $now = new DateTime();
        $week = $this->getWeekForDate($now->format('c'));
        if (!$week) {
            return;
        }
        $week_number = $this->getWeekNumberForWeekId($week['week_id']);

        $score_url = 'https://feeds.nfl.com/feeds-rs/scores.json';
        $json = file_get_contents($score_url);
        $data = json_decode($json, true);

        $sql = <<<SQL
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
SQL;

        foreach ($data['gameScores'] as $game) {
            $game_schedule = $game['gameSchedule'];
            $score = $game['score'];

            $time = $score['phase'];
            if ($time !== 'FINAL' && $time !== 'FINAL_OVERTIME') {
                continue;
            }

            $date = DateTime::createFromFormat('m/d/Y', $game_schedule['gameDate']);
            $date_sql = $date->format('Y-m-d');

            $home_score     = $score['homeTeamScore']['pointTotal'];
            $home_abbr      = $game_schedule['homeTeam']['abbr'];
            $away_score     = $score['visitorTeamScore']['pointTotal'];
            $away_abbr      = $game_schedule['visitorTeam']['abbr'];

            $this->db->query(
                $sql,
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
