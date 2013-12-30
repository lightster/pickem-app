<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

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
