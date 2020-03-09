<?php

namespace App\Pages;

use App\Models\User;
use function The\url_for;

abstract class BasePage extends FwPage
{
    protected function before()
    {
        $this->handleTurbolinksRedirects();

        $this->set('_nav', $this->presentNavLinks([
            '/app/#/football/my-picks/:season/:week'          => 'My Picks',
            '/app/#/football/league-picks/:season/:week'      => 'League Picks',
            '/app/#/football/fantasy-standings/:season/:week' => 'Fantasy Standings',
            '/app/#/football/schedule/:season/:week'          => 'Schedule',
            'App\Pages\TeamStandingsPage'                     => 'Team Standings',
        ]));
        $this->setLayout('default_layout.phtml');
        if ($this->isLoggedIn()) {
            $this->set('user', [
                'username' => $this->getUser()->getData('username'),
            ]);
        }
    }

    protected function getUser()
    {
        return User::find($this->getSessionParam('user_id'));
    }

    protected function isLoggedIn()
    {
        return (bool) $this->getSessionParam('user_id');
    }

    private function presentNavLinks(array $nav_links)
    {
        foreach ($nav_links as $page => $description) {
            $url = $this->weekUrlFor($page);
            $class = ($page === self::class ? 'active' : '');

            yield [
                'url'         => $url,
                'class'       => $class,
                'description' => $description,
            ];
        }
    }

    private function weekUrlFor($page)
    {
        $season = $this->getUrlParam('season');
        $week = $this->getUrlParam('week');

        if (!class_exists($page)) {
            return rtrim(strtr($page, [':season' => $season, ':week' => $week]), '/');
        }

        $params = [];
        if ($season) {
            $params['season'] = $season;
        }
        if ($week) {
            $params['week'] = $week;
        }

        return url_for($page, $params);
    }
}
