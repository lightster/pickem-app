<?php

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
