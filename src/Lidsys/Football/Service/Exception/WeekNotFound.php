<?php

namespace Lidsys\Football\Service\Exception;

use Exception;

class WeekNotFound extends Exception
{
    public function __construct($year, $week_number)
    {
        parent::__construct(
            "Could not find week #{$week_number} in {$year} year."
        );
    }
}
