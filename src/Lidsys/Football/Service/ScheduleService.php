<?php

namespace Lidsys\Football\Service;

use DateTime;
use Exception;

use The\Db;
use The\DbExpr;

class ScheduleService
{
    private $db;

    private $seasons                   = null;
    private $weeks                     = array();
    private $week_numbers_for_week_ids = array();



    public function __construct(Db $db)
    {
        $this->db    = $db;
    }



    public function getSeasons()
    {
        if (isset($this->seasons)) {
            return $this->seasons;
        }

        $seasons       = array();

        $sql = <<<'SQL'
        SELECT
            season_id,
            year
        FROM seasons
        WHERE EXISTS (
            SELECT 1
            FROM weeks
            JOIN games USING (week_id)
            WHERE seasons.season_id = weeks.season_id
            LIMIT 1
        )
        ORDER BY year
        SQL;

        $db    = $this->db;
        $query = $db->query($sql);
        while ($season = $query->fetchRow()) {
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

        $sql = <<<'SQL'
        SELECT
            week_id,
            season_id,
            start_at::date AS start_date,
            end_at::date AS end_date,
            start_at,
            end_at,
            win_weight,
            year,
            COUNT(DISTINCT games.game_id) AS game_count,
            SUM(CASE
                WHEN
                    COALESCE(games.away_score, games.home_score) IS NOT NULL
                THEN 1
                ELSE 0
                END
            ) AS games_played
        FROM weeks
        JOIN seasons USING (season_id)
        JOIN games USING (week_id)
        WHERE year = $1
        GROUP BY weeks.week_id, seasons.season_id
        ORDER BY start_at
        SQL;

        $db    = $this->db;
        $query = $db->query($sql, [$year]);
        while ($week = $query->fetchRow()) {
            ++$week_number;

            $week['week_number'] = $week_number;
            $weeks[$week_number] = $week;
        }

        $this->weeks[$year] = $weeks;

        return $this->weeks[$year];
    }

    public function getWeekForDate($date)
    {
        $sql = <<<'SQL'
        SELECT
            week_id,
            season_id,
            start_at::date AS start_date,
            end_at::date AS end_date,
            win_weight,
            year,
            COUNT(DISTINCT games.game_id) AS game_count
        FROM weeks
        JOIN seasons USING (season_id)
        JOIN games USING (week_id)
        WHERE $1::date BETWEEN start_at AND end_at
        GROUP BY weeks.week_id, seasons.season_id
        LIMIT 1
        SQL;

        $db    = $this->db;
        $query = $db->query($sql, [$date]);
        $week = $query->fetchRow();

        if ($week) {
            $week['week_number'] = $this->getWeekNumberForWeekId($week['week_id']);
        }

        return $week;
    }



    private function getYearForWeekId($week_id)
    {
        $sql = <<<'SQL'
        SELECT year
        FROM weeks
        JOIN seasons USING (season_id)
        WHERE week_id = $1
        SQL;

        $db    = $this->db;
        $query = $db->query($sql, [$week_id]);
        while ($week = $query->fetchRow()) {
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

        $sql = <<<'SQL'
        SELECT
            game_id,
            game_time AS start_time,
            away_team_id,
            home_team_id,
            away_score,
            home_score
        FROM games
        WHERE game_time BETWEEN $1::timestamptz AND $2::timestamptz
        ORDER BY game_time, game_id
        SQL;

        $db    = $this->db;
        $query = $db->query($sql, [$week['start_at'], $week['end_at']]);
        while ($game = $query->fetchRow()) {
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

        $score_url = 'http://scraper/scores';
        $json = file_get_contents($score_url);
        $games = json_decode($json, true);

        $sql = <<<'SQL'
        SELECT game_id
        FROM games
        JOIN weeks USING (week_id)
        JOIN teams AS away_teams ON games.away_team_id = away_teams.team_id
        JOIN teams AS home_teams ON games.home_team_id = home_teams.team_id
        WHERE away_teams.abbreviation = $1
            AND home_teams.abbreviation = $2
            AND $3::date BETWEEN weeks.start_at AND weeks.end_at
        SQL;

        foreach ($games as $game) {
            $time = $game['phase'];
            if ($time !== 'FINAL' && $time !== 'FINAL_OVERTIME') {
                continue;
            }

            $date = new DateTime($game['gameTime']);
            $date_sql = $date->format('Y-m-d');

            $home_score     = $game['homePointsTotal'];
            $home_abbr      = $game['homeTeam']['abbreviation'];
            $away_score     = $game['visitorPointsTotal'];
            $away_abbr      = $game['visitorTeam']['abbreviation'];

            $game_id = $this->db->fetchOne($sql, [$away_abbr, $home_abbr, $date_sql]);

            $this->db->update(
                'games',
                [
                    'away_score' => $away_score,
                    'home_score' => $home_score,
                    'updated_at' => new DbExpr('NOW()'),
                ],
                'game_id = $1',
                [$game_id]
            );
        }
    }
}
