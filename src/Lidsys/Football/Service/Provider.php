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

use Lidsys\Football\View\GameTransformation;
use Lidsys\Football\View\GameScoreTransformation;
use Lidsys\Football\View\SeasonTransformation;
use Lidsys\Football\View\WeekTransformation;

use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['lidsys.football.fantasy-pick'] = $app->share(function ($app) {
            return new FantasyPickService(
                $app['db'],
                $app['lidsys.football.schedule']
            );
        });
        $app['lidsys.football.fantasy-player'] = $app->share(function ($app) {
            return new FantasyPlayerService(
                $app['db']
            );
        });
        $app['lidsys.football.fantasy-standings'] = $app->share(function ($app) {
            return new FantasyStandingService(
                $app['db'],
                $app['lidsys.football.schedule']
            );
        });
        $app['lidsys.football.schedule'] = $app->share(function ($app) {
            return new ScheduleService(
                $app['db']
            );
        });
        $app['lidsys.football.team'] = $app->share(function ($app) {
            return new TeamService(
                $app['db'],
                $app['lidsys.football.schedule']
            );
        });
        $app['lidsys.football.notification'] = $app->share(function ($app) {
            return new NotificationService(
                $app['lidsys.football.schedule'],
                $app['mailer'],
                $app['lidsys.user.authenticator']
            );
        });
        $app['lidsys.football.transformation.game'] = $app->share(function ($app) {
            return new GameTransformation();
        });
        $app['lidsys.football.transformation.game-score'] = $app->share(function ($app) {
            return new GameScoreTransformation();
        });
        $app['lidsys.football.transformation.season'] = $app->share(function ($app) {
            return new SeasonTransformation();
        });
        $app['lidsys.football.transformation.week'] = $app->share(function ($app) {
            return new WeekTransformation();
        });
    }



    public function boot(Application $app)
    {
    }
}
