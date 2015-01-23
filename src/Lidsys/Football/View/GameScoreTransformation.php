<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\View;

use Lidsys\Application\View\TransformationInterface;

class GameScoreTransformation implements TransformationInterface
{
    public function transform($game)
    {
        unset(
            $game['start_time'],
            $game['away_team_id'],
            $game['home_team_id']
        );
        return $game;
    }
}
