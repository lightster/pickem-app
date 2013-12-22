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

use Lidsys\Silex\Service\Exception\TemplateNotFound;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lidsys.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->get('/{type}/schedule/{year}/{week}', function ($type, $year, $week) use ($app) {
            if ('nfl' !== $type) {
                return $app->json(array(
                    'errors' => array(
                        'type' => "Unrecognized type: '{$type}'",
                    ),
                ), 400);
            }

            $pdo   = $app['db']->getPdo();
            $query = $pdo->prepare(
                "
                    SELECT 
                        gameId AS id,
                        gameTime AS game_time,
                        awayId AS away_team_id,
                        homeId AS home_team_id,
                        awayScore AS away_score,
                        homeScore AS home_score
                    FROM nflGame
                    WHERE gameTime BETWEEN DATE(NOW() - INTERVAL 3 DAY) AND DATE(NOW() + INTERVAL 3 DAY)
                    ORDER BY gameTime, homeId, awayId
                "
            );
            $query->execute(array(
            ));
            $games = array();
            while ($row = $query->fetch()) {
                $games[] = $row;
            }

            return $app->json(array(
                'games' => $games,
            ));
        });

        return $controllers;
    }
}
