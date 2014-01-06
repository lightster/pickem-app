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

use Pdo;

use Silex\Application;

class TeamService
{
    private $app;

    private $teams = null;



    public function __construct(Application $app)
    {
        $this->app    = $app;
    }



    public function getTeams()
    {
        if (isset($this->teams)) {
            return $this->teams;
        }

        $this->teams = array();

        $pdo   = $this->app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT
                    teamId AS team_id,
                    location,
                    mascot,
                    abbreviation,
                    conference,
                    division,
                    fontColor AS font_color,
                    background AS background_color,
                    borderColor AS border_color
                FROM nflTeam
            "
        );
        $query->execute(array(
        ));
        while ($team = $query->fetch()) {
            $this->teams[$team['team_id']] = $team;
        }

        return $this->teams;
    }
}
