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

use DateTime;
use DateTimeZone;

class GameTransformation implements TransformationInterface
{
    private $timezone;

    public function __construct()
    {
        $this->timezone = new DateTimeZone('UTC');
    }

    public function transform($game)
    {
        $start_time = new DateTime($game['start_time'], $this->timezone);
        $game['start_time'] = $start_time->format('c');
        return $game;
    }
}
