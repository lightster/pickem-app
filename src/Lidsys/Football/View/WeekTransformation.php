<?php

namespace Lidsys\Football\View;

use Lidsys\Application\View\TransformationInterface;

class WeekTransformation implements TransformationInterface
{
    public function transform($week)
    {
        unset($week['week_id']);
        return $week;
    }
}
