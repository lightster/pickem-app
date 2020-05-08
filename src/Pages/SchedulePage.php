<?php

namespace App\Pages;

use Exception;
use function The\db;

class SchedulePage extends BasePage
{
    use WeekSelectorTrait;

    protected function invoke()
    {
        $this->makeWeekSelector();

        $this->set('title', 'Schedule');
        $this->render('schedule.phtml', [
            'games' => $this->getGamesForWeek(
                $this->getSelectedSeason(),
                $this->getSelectedWeek(),
            ),
        ]);
    }

    public function getGamesForWeek($year, $week_number)
    {
        $weeks = $this->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception("Week {$year}.{$week_number} not found");
        }

        $selected_week  = $weeks[$week_number];

        $games = [];

        $query = db()->query(
            <<<'SQL'
                SELECT
                    game_id,
                    game_time,
                    JSON_BUILD_OBJECT(
                        'team_id',      away_team_id,
                        'score',        away_score,
                        'abbreviation', away.abbreviation,
                        'location',     away.location,
                        'mascot',       away.mascot
                    ) AS away,
                    JSON_BUILD_OBJECT(
                        'team_id',      home_team_id,
                        'score',        home_score,
                        'abbreviation', home.abbreviation,
                        'location',     home.location,
                        'mascot',       home.mascot
                    ) AS home,
                    (away_score IS NOT NULL OR home_score IS NOT NULL) AS is_final
                FROM games
                JOIN teams AS away ON away.team_id = games.away_team_id
                JOIN teams AS home ON home.team_id = games.home_team_id
                WHERE game_time BETWEEN $1::timestamptz AND $2::timestamptz
                ORDER BY game_time, game_id
                SQL,
            [$selected_week['start_date'], $selected_week['end_date']]
        );
        while ($row = $query->fetchRow()) {
            $games[$row['game_time']][] = [
                'away'     => json_decode($row['away'], true),
                'home'     => json_decode($row['home'], true),
                'is_final' => $row['is_final'] === 't',
            ];
        }

        return $games;
    }

    public function getWeeksForYear($year)
    {
        $weeks       = [];
        $week_number = 0;

        $query = db()->query(
            <<<'SQL'
                SELECT
                    week_id,
                    start_at::date AS start_date,
                    end_at::date AS end_date
                FROM weeks
                WHERE season_id = (SELECT season_id FROM seasons WHERE year = $1)
                ORDER BY start_at
                SQL,
            [$year]
        );
        while ($week = $query->fetchRow()) {
            ++$week_number;

            $week['week_number'] = $week_number;
            $weeks[$week_number] = $week;
        }

        return $weeks;
    }
}
