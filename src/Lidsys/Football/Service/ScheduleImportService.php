<?php

namespace Lidsys\Football\Service;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

use The\Db;

class ScheduleImportService
{
    private $db;
    private $schedule;

    public function __construct(Db $db, ScheduleService $schedule)
    {
        $this->db = $db;
        $this->schedule = $schedule;
    }

    public function importThirdPartySchedule($year)
    {
        $this->createStagingTemporaryTable();
        $this->stageRetrievedGames($this->retrieveSeasonGamesFromThirdParty($year));
        $this->createSeasonIfItDoesNotExist($year);
        $this->createWeeksIfTheyDoNotExist($year);
        $this->importStagedGames();
    }

    private function retrieveSeasonGamesFromThirdParty($year)
    {
        $games = array();
        for ($week = 1; $week <= 18; $week++) {
            $week_games = $this->retrieveWeekGamesFromThirdParty($year, $week);
            $games = $games + $week_games;
        }

        return $games;
    }

    private function retrieveWeekGamesFromThirdParty($year, $week)
    {
        $week_name = ($week >= 18 ? 'POST' : "REG{$week}");
        $html = file_get_contents("http://www.nfl.com/schedules/{$year}/{$week_name}");
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->formatOutput  = true;
        $dom->preserveWhiteSpace = false;
        $dom->loadHTML($html);

        $xpath = new DOMXPath($dom);
        $game_li_elements = $xpath->query('//li');

        $games = [];
        foreach ($game_li_elements as $game_li) {
            if ($this->isGameElementSpecial($game_li)) {
                continue;
            }

            $known_data = $this->extractKnownDataFromGameElement($xpath, $game_li);

            if (!$known_data['game_id']) {
                throw new Exception(
                    "Game element matched but did not contain a 'data-gameid' value."
                );
            } elseif (empty($known_data['time_of_day'])) {
                continue;
            }

            $games[$known_data['game_id']] = array(
                'away_team' => $known_data['away_team'],
                'home_team' => $known_data['home_team'],
                'game_time' => gmdate(
                    'Y-m-d H:i:s',
                    $this->determineGameTimestamp($known_data)
                ),
            );
        }

        return $games;
    }

    private function isGameElementSpecial(DOMElement $game_li)
    {
        $li_class_obj = $game_li->attributes->getNamedItem('class');
        $li_class = ($li_class_obj ? $li_class_obj->nodeValue : null);

        return !$li_class
            || strpos($li_class, 'next-game') !== false
            || strpos($li_class, 'schedules-list-matchup') === false;
    }

    private function extractKnownDataFromGameElement(DOMXPath $xpath, DOMElement $game_li)
    {
        $game_data = $xpath->query(
            './/div[contains(@class,"schedules-list-content")]',
            $game_li
        );

        $known_data = array(
            'game_id'   => null,
            'away_team' => null,
            'home_team' => null,
            'time'      => null,
        );

        if ($game_data->length && ($data = $game_data->item(0))) {
            $attrs = $data->attributes;
            $known_data['game_id'] = $attrs->getNamedItem('data-gameid')->nodeValue;
            $known_data['away_team'] = $attrs->getNamedItem('data-away-abbr')->nodeValue;
            $known_data['home_team'] = $attrs->getNamedItem('data-home-abbr')->nodeValue;
        }

        $time_data = $xpath->query(
            './/span[@class="time"]',
            $game_li
        );
        if ($time_data->length && ($time_div = $time_data->item(0))) {
            $known_data['time'] = $time_div->textContent;
        }

        $time_of_day = $xpath->query(
            './/span[@class="suff"]/span',
            $game_li
        );
        if ($time_of_day->length && ($time_of_day_div = $time_of_day->item(0))) {
            $known_data['time_of_day'] = trim($time_of_day_div->textContent);
        }

        return $known_data;
    }

    private function determineGameTimestamp(array $known_data)
    {
        if (!preg_match('#^(20[0-9]{2})([0-9]{2})([0-9]{2})#', $known_data['game_id'], $date_parts)) {
            throw new Exception(
                "Game date could not be parsed from game ID: {$game_id}"
            );
        }

        $date = array(
            'year'  => $date_parts[1],
            'month' => $date_parts[2],
            'day'   => $date_parts[3],
        );

        $raw_time = explode(':', $known_data['time']);
        $time = array(
            'hour'   => intval($raw_time[0]) + ('PM' === $known_data['time_of_day'] && $raw_time[0] != 12 ? 12 : 0),
            'minute' => intval($raw_time[1]),
        );

        $timezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');
        $timestamp  = mktime(
            $time['hour'],
            $time['minute'],
            0,
            $date['month'],
            $date['day'],
            $date['year']
        );
        date_default_timezone_set($timezone);

        return $timestamp;
    }

    private function createStagingTemporaryTable()
    {
        $sql = <<<SQL
        CREATE TEMPORARY TABLE game_import (
            away_team VARCHAR NOT NULL,
            home_team VARCHAR NOT NULL,
            game_time TIMESTAMPTZ NOT NULL
        )
        SQL;

        $this->db->query($sql);
    }

    private function stageRetrievedGames(array $games)
    {
        foreach ($games as $game) {
            $this->db->insert(
                'game_import',
                [
                    'away_team' => $game['away_team'],
                    'home_team' => $game['home_team'],
                    'game_time' => $game['game_time'],
                ]
            );
        }
    }

    private function createSeasonIfItDoesNotExist($year)
    {
        if ($this->doesSeasonExist($year)) {
            return;
        }

        $this->db->insert('seasons', ['year' => $year]);
    }

    private function doesSeasonExist($year)
    {
        return (bool)$this->getSeasonIdForYear($year);
    }

    private function getSeasonIdForYear($year)
    {
        $sql = <<<SQL
        SELECT season_id
        FROM seasons
        WHERE year = $1
        SQL;

        $result = $this->db->query($sql, [$year]);

        return $result->fetchOne();
    }

    private function createWeeksIfTheyDoNotExist($year)
    {
        $season_id = $this->getSeasonIdForYear($year);

        $weeks_by_start = [];
        $week_number = 0;
        foreach ($this->getGameDates($year) as $date) {
            $start_timestamp = strtotime(
                'last Wednesday',
                strtotime($date)
            );
            $start_date = date('Y-m-d', $start_timestamp);

            if (!isset($weeks_by_start[$start_date])) {
                $weeks_by_start[$start_date] = $week_number;
                ++$week_number;
            }

            if ($this->doesWeekExist($season_id, $start_date)) {
                continue;
            }

            $end_timestamp = strtotime(
                '+6 days 23:59:59',
                $start_timestamp
            );

            $this->db->insert(
                'weeks',
                [
                    'season_id'  => $season_id,
                    'start_at'   => $start_date,
                    'end_at'     => date('c', $end_timestamp),
                    'win_weight' => ($week_number > 17 ? pow(2, $week_number - 17) : 1),
                ]
            );
        }
    }

    private function getGameDates($year)
    {
        $dates = array();

        $sql = <<<'SQL'
        SELECT DISTINCT game_time::date AS date
        FROM game_import
        JOIN teams AS away ON game_import.away_team = away.abbreviation
        JOIN teams AS home ON game_import.home_team = home.abbreviation

        UNION DISTINCT

        SELECT DISTINCT game_time::date AS date
        FROM games
        JOIN weeks USING (week_id)
        JOIN seasons USING (season_id)
        WHERE year = $1

        ORDER BY date
        SQL;
        $result = $this->db->query($sql, [$year]);
        while ($row = $result->fetchRow()) {
            $dates[$row['date']] = $row['date'];
        }

        return $dates;
    }

    private function doesWeekExist($season_id, $start_date)
    {
        $sql = <<<'SQL'
        SELECT 1
        FROM weeks
        WHERE season_id = $1
            AND start_at = $2
        SQL;

        return $this->db->exists($sql, [$season_id, $start_date]);
    }

    public function importStagedGames()
    {
        $sql = <<<'SQL'
        INSERT INTO games
        (
            week_id,
            away_team_id,
            home_team_id,
            game_time
        )
        SELECT
            weeks.week_id,
            away_team.team_id,
            home_team.team_id,
            game_import.game_time
        FROM game_import
        JOIN teams AS away_team ON game_import.away_team = away_team.abbreviation
        JOIN teams AS home_team ON game_import.home_team = home_team.abbreviation
        JOIN weeks ON game_import.game_time BETWEEN weeks.start_at AND weeks.end_at
        ON CONFLICT (week_id, away_team_id, home_team_id) DO UPDATE SET game_time = excluded.game_time
        SQL;
        $this->db->query($sql);
    }
}
