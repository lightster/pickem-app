<?php

namespace App\Pages;

use Exception;
use function The\db;
use function The\url_for;

class TeamStandingsPage extends BasePage
{
    use WeekSelectorTrait;

    protected function invoke()
    {
        $this->makeWeekSelector();

        $this->set('title', 'Team Standings');
        $this->render('team_standings.phtml', [
            'standings' => $this->getStandingsForWeek(
                $this->getSelectedSeason(),
                $this->getSelectedWeek(),
            ),
        ]);
    }

    public function getStandingsForWeek($year, $week_number)
    {
        $weeks = $this->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception("Week {$year}.{$week_number} not found");
        }

        $first_week     = $weeks[1];
        $selected_week  = $weeks[$week_number];

        $team_standings = [];

        $query = db()->query(
            <<<'SQL'
                WITH team_scores AS (
                    SELECT
                        away_team_id AS team_id,
                        away_score > home_score AS win,
                        away_score = home_score AS tie,
                        away_score < home_score AS loss
                    FROM games
                    WHERE game_time BETWEEN $1 AND $2
                        AND away_score IS NOT NULL
                        AND home_score IS NOT NULL

                    UNION ALL

                    SELECT
                        home_team_id AS team_id,
                        home_score > away_score AS win,
                        home_score = away_score AS tie,
                        home_score < away_score AS loss
                    FROM games
                    WHERE game_time BETWEEN $1 AND $2
                        AND away_score IS NOT NULL
                        AND home_score IS NOT NULL
                )
                SELECT
                    team_id,
                    conference,
                    division,
                    abbreviation,
                    location,
                    mascot,
                    SUM(win::int) AS win_count,
                    SUM(loss::int) AS loss_count,
                    SUM(tie::int) AS tie_count
                FROM teams
                JOIN team_scores USING (team_id)
                GROUP BY team_id
                ORDER BY win_count DESC, tie_count DESC, loss_count DESC
                SQL,
            [$first_week['start_date'], $selected_week['end_date']]
        );
        while ($team = $query->fetchRow()) {
            $team_standings[$team['conference']][$team['division']][] = $team;
        }

        return $team_standings;
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
