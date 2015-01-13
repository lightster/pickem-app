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

use Lstr\Silex\Template\Exception\TemplateNotFound;
use Lstr\Silex\Controller\JsonRequestMiddlewareService;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lstr.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->get('/reminder', function (Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUserId($user_id);

            if ('lightster' === $authenticated_user['username']) {
                $now = new DateTime();
                $count = $app['lidsys.football.notification']
                    ->sendReminderEmailForDate($now);

                if ($count) {
                    return $app->json("done {$count}");
                }
            }

            return $app->json('nada');
        });

        $controllers->get('/notification', function (Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUserId($user_id);

            if ('lightster' === $authenticated_user['username']) {
                $count = 0;
                $user_results = $app['lidsys.user.authenticator']->findUsersActiveSince('2013-09-01');
                while ($user = $user_results->fetch()) {
                    $app['lidsys.football.notification']->sendWelcomeEmail($user);
                    ++$count;
                }
                return $app->json("done {$count}");
            }

            return $app->json('nada');
        });

        $controllers->get('/seasons', function (Application $app) {
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

        $controllers->get('/weeks/{year}', function ($year, Application $app) {
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

        $controllers->get('/schedule/{year}/{week}', function ($year, $week, Application $app) {
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

        $controllers->get('/scores/{year}/{week}', function ($year, $week, Application $app) {
            $games = $app['lidsys.football.schedule']->getGamesForWeek($year, $week);

            $timezone = new DateTimeZone('UTC');

            $formatter = function (array & $game) use ($timezone) {
                unset(
                    $game['start_time'],
                    $game['away_team_id'],
                    $game['home_team_id']
                );
            };
            array_walk(
                $games,
                $formatter
            );

            return $app->json(array(
                'games' => $games,
            ));
        });

        $controllers->get('/scores/{year}', function ($year, Application $app) {
            $sched_service = $app['lidsys.football.schedule'];
            $weeks         = $sched_service->getWeeksForYear($year);

            $timezone = new DateTimeZone('UTC');

            $formatter = function (array & $game) use ($timezone) {
                unset(
                    $game['start_time'],
                    $game['away_team_id'],
                    $game['home_team_id']
                );
            };

            $all_games = array();
            foreach ($weeks as $week_num => $week) {
                $all_games[$week_num] = $sched_service->getGamesForWeek(
                    $year,
                    $week_num
                );
                array_walk(
                    $all_games[$week_num],
                    $formatter
                );
            }

            return $app->json(array(
                'games' => $all_games,
            ));
        });

        $controllers->get('/update-scores/', function (Application $app) {
            $sched_service = $app['lidsys.football.schedule'];
            $sched_service->updateScores();

            return $app->json(array(
                'success' => 'done',
            ));
        });

        $controllers->get('/teams', function (Application $app) {
            $teams = $app['lidsys.football.team']->getTeams();

            return $app->json(array(
                'teams' => $teams,
            ));
        });

        $controllers->get('/team-standings/{year}/{week}', function ($year, $week, Application $app) {
            $team_standings = $app['lidsys.football.team']->getStandingsForWeek($year, $week);

            return $app->json(array(
                'team_standings' => $team_standings,
            ));
        });

        $controllers->get('/fantasy-picks/{year}/{week}', function ($year, $week, Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUserId($user_id);

            $player_id = $authenticated_user
                ? $authenticated_user['player_id']
                : null;

            $picks = $app['lidsys.football.fantasy-pick']->getPicksForWeek(
                $year,
                $week,
                $player_id
            );

            return $app->json(array(
                'fantasy_picks' => $picks,
            ));
        });

        $controllers->post('/fantasy-picks/', function (Request $request, Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUserId($user_id);

            if (!$authenticated_user) {
                return $app->json(
                    array(
                        'error' => 'You are logged out. Please sign in and try again.',
                    ),
                    403
                );
            }

            $picks = $request->get('fantasy_picks');

            $saved_picks = $app['lidsys.football.fantasy-pick']->savePicks($user_id, $picks);

            return $app->json(array(
                'saved_picks' => $saved_picks,
            ));
        });

        $controllers->get('/fantasy-players/{year}', function ($year, Application $app) {
            $user_id = $app['session']->get('user_id');

            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUserId($user_id);

            $player_id = $authenticated_user
                ? $authenticated_user['player_id']
                : null;

            $players = $app['lidsys.football.fantasy-player']->getPlayersForYear(
                $year,
                $player_id
            );

            return $app->json(array(
                'fantasy_players' => $players,
            ));
        });

        $controllers->get('/fantasy-standings/{year}', function ($year, Application $app) {
            $players = $app['lidsys.football.fantasy-standings']->getFantasyStandingsForYear($year);

            return $app->json(array(
                'fantasy_standings' => $players,
            ));
        });

        $controllers->before(new JsonRequestMiddlewareService());

        return $controllers;
    }
}
