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

use DateTime;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

use Lstr\Silex\Database\DatabaseService;

class ScheduleImportService
{
    private $db;
    private $schedule;

    public function __construct(DatabaseService $db, ScheduleService $schedule)
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
        for ($week = 1; $week <= 17; $week++) {
            $week_games = $this->retrieveWeekGamesFromThirdParty($year, $week);
            $games = $games + $week_games;
        }

        return $games;
    }

    private function retrieveWeekGamesFromThirdParty($year, $week)
    {
        $html = file_get_contents("http://www.nfl.com/schedules/{$year}/REG{$week}");
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
            'hour'   => intval($raw_time[0]) + 12,
            'minute' => intval($raw_time[1]),
        );

        $timestamp  = mktime(
            $time['hour'],
            $time['minute'],
            0,
            $date['month'],
            $date['day'],
            $date['year']
        );

        return $timestamp;
    }

    private function createStagingTemporaryTable()
    {
        $sql = <<<SQL
CREATE TEMPORARY TABLE `nflGameImport` (
  `awayTeam` varchar(3) NOT NULL,
  `homeTeam` varchar(3) NOT NULL,
  `gameTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
)
SQL;
        $this->db->query($sql);
    }

    private function stageRetrievedGames(array $games)
    {
        foreach ($games as $game) {
            $this->db->insert(
                'nflGameImport',
                array(
                    'awayTeam' => $game['away_team'],
                    'homeTeam' => $game['home_team'],
                    'gameTime' => $game['game_time'],
                )
            );
        }
    }

    private function createSeasonIfItDoesNotExist($year)
    {
        if ($this->doesSeasonExist($year)) {
            return;
        }

        $this->db->insert(
            'nflSeason',
            array(
                'year' => $year,
            )
        );
    }

    private function doesSeasonExist($year)
    {
        return (bool)$this->getSeasonIdForYear($year);
    }

    private function getSeasonIdForYear($year)
    {
        $sql = <<<SQL
SELECT seasonId
FROM nflSeason
WHERE year = :year
SQL;

        $result = $this->db->query($sql, array('year' => $year));
        $row = $result->fetch();

        return $row['seasonId'];
    }

    private function createWeeksIfTheyDoNotExist($year)
    {
        $season_id = $this->getSeasonIdForYear($year);

        foreach ($this->getStagedDates() as $date) {
            $start_timestamp = strtotime(
                'last Wednesday',
                strtotime($date)
            );
            $start_date = date('Y-m-d', $start_timestamp);

            if ($this->doesWeekExist($season_id, $start_date)) {
                continue;
            }

            $end_timestamp = strtotime(
                '+6 days',
                $start_timestamp
            );

            $this->db->insert(
                'nflWeek',
                array(
                    'seasonId'  => $season_id,
                    'weekStart' => $start_date,
                    'weekEnd'   => date('Y-m-d', $end_timestamp),
                    'winWeight' => 1,
                )
            );
        }
    }

    private function getStagedDates()
    {
        $dates = array();

        $sql = <<<SQL
SELECT DISTINCT DATE(gameTime) AS date
FROM nflGameImport
SQL;
        $result = $this->db->query($sql);
        while ($row = $result->fetch()) {
            $dates[$row['date']] = $row['date'];
        }

        return $dates;
    }

    private function doesWeekExist($season_id, $start_date)
    {
        $sql = <<<SQL
SELECT 1
FROM nflWeek
WHERE seasonId = :season_id
    AND weekStart = :start_date
SQL;

        $criteria = array(
            'season_id'  => $season_id,
            'start_date' => $start_date,
        );

        return (bool)$this->db->query($sql, $criteria)->fetch();
    }

    public function importStagedGames()
    {
        $sql = <<<SQL
INSERT IGNORE INTO nflGame
(
    `weekId`,
    `awayId`,
    `homeId`,
    `gameTime`
)
SELECT
    week.weekId,
    away.teamId,
    home.teamId,
    import.gameTime
FROM nflGameImport AS import
JOIN nflTeam AS away ON import.awayTeam = away.abbreviation
JOIN nflTeam AS home ON import.homeTeam = home.abbreviation
JOIN nflWeek AS week ON DATE(import.gameTime) BETWEEN week.weekStart AND week.weekEnd
SQL;
        $this->db->query($sql);
    }
}
