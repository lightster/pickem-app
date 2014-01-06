<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\Controller;

use DateTime;
use DateTimeZone;

use Lidsys\Silex\Service\Exception\TemplateNotFound;
use Lidsys\Silex\Service\JsonRequestMiddlewareService;

use Lidsys\Football\Service\Provider as FootballServiceProvider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lidsys.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->get('/seasons', function () use ($app) {
            $seasons = $app['lidsys.football.schedule']->getSeasons();

            array_walk(
                $seasons,
                function (array & $season) {
                    unset($season['season_id']);
                }
            );

            return $app->json(array(
                'seasons' => $seasons,
            ));
        });

        $controllers->get('/weeks/{year}', function ($year) use ($app) {
            $weeks = $app['lidsys.football.schedule']->getWeeksForYear($year);

            array_walk(
                $weeks,
                function (array & $week) {
                    unset($week['week_id']);
                }
            );

            return $app->json(array(
                'weeks' => $weeks,
            ));
        });

        $controllers->get('/schedule/{year}/{week}', function ($year, $week) use ($app) {
            $games = $app['lidsys.football.schedule']->getGamesForWeek($year, $week);

            $timezone = new DateTimeZone('UTC');

            array_walk(
                $games,
                function (array & $game) use ($timezone) {
                    $start_time = new DateTime($game['start_time'], $timezone);
                    $game['start_time'] = $start_time->format('c');
                }
            );

            return $app->json(array(
                'games' => $games,
            ));
        });

        $controllers->get('/teams', function () use ($app) {
            $teams = $app['lidsys.football.team']->getTeams();

            return $app->json(array(
                'teams' => $teams,
            ));
        });

        $controllers->before(new JsonRequestMiddlewareService());
        $controllers->before(function () use ($app) {
            $app->register(new FootballServiceProvider());
        });

        return $controllers;
    }
}
