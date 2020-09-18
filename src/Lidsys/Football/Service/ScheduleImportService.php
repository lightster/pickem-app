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
        $this->stageSeasonGamesFromThirdParty($year);
        $this->createSeasonIfItDoesNotExist($year);
        $this->createWeeksIfTheyDoNotExist($year);
        $this->importStagedGames();
    }

    private function stageSeasonGamesFromThirdParty($year)
    {
        $games = array();
        for ($week = 1; $week <= 18; $week++) {
            $week_games = $this->stageWeekGamesFromThirdParty($year, $week);
            $games = $games + $week_games;
        }

        return $games;
    }

    private function stageWeekGamesFromThirdParty($year, $week)
    {
        $week_name = ($week >= 18 ? 'POST' : "REG{$week}");
        $opts = [
            'Name' => 'Schedules',
            'Module' => [
                'seasonFromUrl'          => $year,
                'SeasonType'             => $week_name,
                'WeekFromUrl'            => 1,
                'PreSeasonPlacement'     => 0,
                'RegularSeasonPlacement' => 0,
                'PostSeasonPlacement'    => 0,
                'TimeZoneID'             => 'America/Los_Angeles'
            ],
        ];
        $url = "https://www.nfl.com/api/lazy/load?json=" . json_encode($opts);

        $html = file_get_contents($url);
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->formatOutput  = true;
        $dom->preserveWhiteSpace = false;
        $dom->loadHTML($html);

        $xpath = new DOMXPath($dom);
        $game_day_elements = $xpath->query('//section[contains(@class, "nfl-o-matchup-group")]');

        $games = [];
        foreach ($game_day_elements as $game_day_element) {
            $this->stageFromGameDayElement($xpath, $game_day_element);
        }

        return $games;
    }

    private function stageFromGameDayElement(DOMXPath $xpath, DOMElement $game_day_element)
    {
        $date_element = $xpath->query(
            './/h2[contains(@class, "d3-o-section-title")]',
            $game_day_element
        );
        if (!$date_element->length) {
            throw new Exception("date element 'h2.class=d3-o-section-title' missing");
        }

        $game_elements = $xpath->query(
            './/div[contains(@class,"nfl-c-matchup-strip--pre-game")]',
            $game_day_element
        );
        if (!$game_elements->length) {
            throw new Exception("game element 'div.class=nfl-c-matchup-strip' missing");
        }

        for ($i = 0; $i < $game_elements->length; $i++) {
             $this->stageFromGameElement(
                $xpath,
                ['date' => trim($date_element->item(0)->textContent)],
                $game_elements->item($i)
            );
        }
    }

    private function stageFromGameElement(DOMXPath $xpath, array $week_data, DOMElement $game_element)
    {
        $time_element = $xpath->query(
            './/p[contains(@class,"nfl-c-matchup-strip__date-info")]/span',
            $game_element
        );
        if ($time_element->length != 2) {
            throw new Exception("required two time elements 'div.class=nfl-c-matchup-strip__date-info span' missing");
        }
        $time = trim($time_element->item(0)->textContent);
        $tz = trim($time_element->item(1)->textContent);

        $team_abbr_elements = $xpath->query(
            './/span[contains(@class,"nfl-c-matchup-strip__team-abbreviation")]',
            $game_element
        );
        if ($team_abbr_elements->length != 2) {
            throw new Exception(
                "required two team elements 'span.class=nfl-c-matchup-strip__team-abbreviation' missing"
            );
        }

        $this->stageGame([
            'away_team' => trim($team_abbr_elements->item(0)->textContent),
            'home_team' => trim($team_abbr_elements->item(1)->textContent),
            'game_time' => date('c', strtotime("{$week_data['date']} {$time} {$tz}")),
        ]);
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

    private function stageGame(array $game)
    {
        $this->db->insert(
            'game_import',
            [
                'away_team' => $game['away_team'],
                'home_team' => $game['home_team'],
                'game_time' => $game['game_time'],
            ]
        );
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
