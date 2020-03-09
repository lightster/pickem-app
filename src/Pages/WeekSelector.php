<?php

namespace App\Pages;

use function The\db;
use function The\url_for;

class WeekSelector
{
    private $page;
    private $season;
    private $week;

    public function __construct($page, $season, $week)
    {
        $this->page = $page;
        $this->season = $season;
        $this->week = $week;
    }

    public function presentSeasons()
    {
        $years = db()->query(
            <<<'SQL'
                SELECT year
                FROM seasons
                ORDER BY year DESC
                SQL
        )->fetchCol();

        $selected_year = $this->season ?: current($years);
        foreach ($years as $year) {
            yield [
                'season'      => $year,
                'is_selected' => "{$year}" === "{$selected_year}",
                'url'         => url_for(
                    $this->page,
                    array_filter(['season' => $year, 'week' => $this->week])
                ),
            ];
        }
    }

    public function presentWeeks($year)
    {
        $week_count = db()->query(
            <<<'SQL'
                SELECT COUNT(*)
                FROM weeks
                WHERE season_id = (SELECT season_id FROM seasons WHERE year = $1)
                SQL,
            [$year]
        )->fetchOne();

        $selected_week = min($this->week ?: $week_count, $week_count);
        for ($week = $week_count; $week >= 1; $week--) {
            yield [
                'week'        => $week,
                'is_selected' => "{$week}" === "{$selected_week}",
                'url'         => url_for($this->page, ['season' => $year, 'week' => $week]),
            ];
        }
    }

    public function getSelected(array $list)
    {
        foreach ($list as $item) {
            if ($item['is_selected']) {
                return $item;
            }
        }
    }
}
