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

class SeasonTransformation implements TransformationInterface
{
    public function transform($season)
    {
        unset($season['season_id']);
        return $season;
    }
}
