<?php

namespace App\Pages;

use function The\db;
use function The\url_for;

trait WeekSelectorTrait
{
    private $selected_season;
    private $selected_week;

    public function makeWeekSelector()
    {
        $week_selector = new WeekSelector(
            self::class,
            $this->getUrlParam('season'),
            $this->getUrlParam('week')
        );

        $seasons = iterator_to_array($week_selector->presentSeasons());
        $selected_season = $week_selector->getSelected($seasons)['season'];
        $weeks = iterator_to_array($week_selector->presentWeeks($selected_season));
        $selected_week = $week_selector->getSelected($weeks)['week'];

        $this->set('_week_selector', [
            'seasons'         => $seasons,
            'weeks'           => $weeks,
            'selected_season' => $selected_season,
            'selected_week'   => $selected_week,
        ]);

        $this->selected_season = $selected_season;
        $this->selected_week = $selected_week;
    }

    protected function getSelectedSeason()
    {
        return $this->selected_season;
    }

    protected function getSelectedWeek()
    {
        return $this->selected_week;
    }
}
