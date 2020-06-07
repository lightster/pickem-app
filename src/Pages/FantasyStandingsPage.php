<?php

namespace App\Pages;

use Exception;
use function The\db;

class FantasyStandingsPage extends BasePage
{
    use WeekSelectorTrait;

    protected function invoke()
    {
        $this->makeWeekSelector();

        $weeks = $this->getWeeksForYear(
            $this->getSelectedSeason(),
            $this->getSelectedWeek()
        );
        $fantasy_standings = $this->getFantasyStandings(
            $weeks,
            $this->getSelectedSeason(),
            $this->getSelectedWeek(),
        );
        $week_stats = $this->getWeekStats($weeks, $fantasy_standings);

        $this->set('title', 'Fantasy standings');
        $this->render('fantasy_standings.phtml', [
            'weeks'          => $weeks,
            'user_standings' => $this->getUserStandings($fantasy_standings, []),
            'week_stats'     => $week_stats,
            'season_stats'   => $this->getSeasonStats($week_stats),
        ]);
    }

    private function getUserStandings(array $fantasy_standings, array $week_stats)
    {
        $users = $this->getUsers(array_keys($fantasy_standings));

        $weeks_template = [];
        foreach ($week_stats as $week_number => $week_stat) {
            $weeks_template[$week_number] = [];
        }

        $user_standings = [];
        foreach ($users as $user) {
            $user_stats = $fantasy_standings[$user['user_id']];
            $total_points = array_sum(array_column($user_stats, 'points'));
            $total_potential = array_sum(array_column($user_stats, 'potential'));

            $user_standings[] = [
                'display_name'     => $user['display_name'],
                'display_color'    => $user['display_color'],
                'short_name'       => $user['short_name'],
                'weeks'            => $fantasy_standings[$user['user_id']],
                'total_points'     => $total_points,
                'total_potential'  => $total_potential,
                'total_percent'    => number_format(100 * $total_points / $total_potential, 1),
                'weighted_percent' => number_format(100 * $total_points / $total_potential, 1),
                'weeks_won'        => 0,
            ];
        }

        usort($user_standings, function ($a, $b) {
            if ($a['total_points'] !== $b['total_points']) {
                return $b['total_points'] <=> $a['total_points'];
            }

            if ($a['weeks_won'] !== $b['weeks_won']) {
                return $b['weeks_won'] <=> $a['weeks_won'];
            }

            return $a['display_name'] <=> $a['display_name'];
        });

        $last_points = 0;
        $rank_ties = 1;
        $rank = 0;
        foreach ($user_standings as &$user_standing) {
            if ($last_points !== $user_standing['total_points']) {
                $rank += $rank_ties;
                $rank_ties = 1;
            } else {
                ++$rank_ties;
            }
            $last_points = $user_standing['total_points'];

            $user_standing['rank'] = $rank;
        }

        return $user_standings;
    }

    private function getUsers($user_ids)
    {
        if (empty($user_ids)) {
            return [];
        }

        $user_id_sql = db()->implodeInts($user_ids);

        $query = db()->query(
            <<<SQL
                SELECT
                    user_id,
                    display_name,
                    display_color
                FROM users
                WHERE user_id IN ({$user_id_sql})
            SQL
        );
        while ($row = $query->fetchRow()) {
            $names = explode(' ', $row['display_name']);
            $row['short_name'] = substr($names[0], 0, 1)
                . substr($names[0], -1, 1)
                . substr($names[1] ?? '', 0, 1);

            $users[$row['user_id']] = $row;
        }

        return $users;
    }

    private function getFantasyStandings($weeks, $year, $week_number)
    {
        if (!isset($weeks[$week_number])) {
            throw new Exception("Week {$year}.{$week_number} not found");
        }

        $first_week     = $weeks[1];
        $selected_week  = $weeks[$week_number];
        $week_numbers   = array_column($weeks, 'week_number', 'week_id');

        $fantasy_standings = [];

        $query = db()->query(
            <<<'SQL'
                WITH awarded_points AS (
                    SELECT
                        game_id,
                        away_team_id AS team_id,
                        week_id,
                        win_weight AS potential,
                        CASE
                            WHEN away_score >= home_score THEN win_weight
                        END AS awarded_points
                    FROM games
                    JOIN weeks USING (week_id)
                    WHERE season_id = (SELECT season_id FROM seasons WHERE year = $1)
                        AND game_time BETWEEN $2 AND $3

                    UNION ALL

                    SELECT
                        game_id,
                        home_team_id AS team_id,
                        week_id,
                        win_weight AS potential,
                        CASE
                            WHEN home_score >= away_score THEN win_weight
                        END AS awarded_points
                    FROM games
                    JOIN weeks USING (week_id)
                    WHERE season_id = (SELECT season_id FROM seasons WHERE year = $1)
                        AND game_time BETWEEN $2 AND $3
                )            

                SELECT
                    user_id,
                    week_id,
                    COALESCE(SUM(awarded_points), 0) AS points,
                    COALESCE(SUM(potential), 0) AS potential
                FROM users
                JOIN picks USING (user_id)
                JOIN awarded_points USING (game_id, team_id)
                GROUP BY user_id, week_id
                ORDER BY points
            SQL,
            [$year, $first_week['start_date'], $selected_week['end_date']]
        );
        while ($row = $query->fetchRow()) {
            $week_number = $week_numbers[$row['week_id']];

            if (!$row['potential']) {
                $row['percent'] = 'N/A';
            } else {
                $row['percent'] = number_format(
                    100 * $row['points'] / $row['potential'],
                    1
                );
            }

            $fantasy_standings[$row['user_id']][$week_number] = $row;
        }

        return $fantasy_standings;
    }

    private function getWeeksForYear($year, $selected_week_number)
    {
        $weeks       = [];
        $week_number = 0;

        $query = db()->query(
            <<<'SQL'
                SELECT
                    week_id,
                    start_at::date AS start_date,
                    end_at::date AS end_date,
                    (
                        SELECT
                            JSON_BUILD_OBJECT(
                                'games_scheduled', COUNT(*),
                                'games_played',    COUNT(away_score),
                                'win_weight',      win_weight
                            )
                        FROM games
                        WHERE games.week_id = weeks.week_id
                    ) AS details
                FROM weeks
                WHERE season_id = (SELECT season_id FROM seasons WHERE year = $1)
                ORDER BY start_at
                SQL,
            [$year]
        );
        while ($week = $query->fetchRow()) {
            ++$week_number;

            $week['week_number'] = $week_number;
            $week['is_selected'] = ($selected_week_number === $week_number);
            $week['details']     = json_decode($week['details'], true);
            $weeks[$week_number] = $week;
        }

        return $weeks;
    }

    private function getWeekStats(array $weeks, array $fantasy_standings)
    {
        $week_stats = [];

        foreach ($weeks as $week_number => ['details' => $details]) {
            $user_stats = array_column($fantasy_standings, $week_number);
            $user_aggr = array_reduce(
                array_column($user_stats, 'points'),
                function ($aggr, $points) {
                    return [
                        'min' => min($aggr['min'], $points),
                        'max' => max($aggr['max'], $points),
                    ];
                },
                ['min' => INF, 'max' => 0]
            );

            $week_stats[$week_number] = [
                'points_played'    => $details['games_played'] * $details['win_weight'],
                'points_scheduled' => $details['games_scheduled'] * $details['win_weight'],
                'max_points'       => $user_aggr['max'],
                'min_points'       => $user_aggr['min'],
            ];
        }

        return $week_stats;
    }

    private function getSeasonStats(array $week_stats)
    {
        $season_stats = [
            'points_played'    => array_sum(array_column($week_stats, 'points_played')),
            'points_scheduled' => array_sum(array_column($week_stats, 'points_scheduled')),
            'percent'          => 'N/A',
        ];

        if ($season_stats['points_scheduled']) {
            $season_stats['percent'] = number_format(
                100 * $season_stats['points_played'] / $season_stats['points_scheduled'],
                1
            );
        }

        return $season_stats;
    }
}
