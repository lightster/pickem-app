<?php

namespace App;

function team_style(string $abbreviation, string $modifier = 'primary')
{
    $abbreviation = strtolower($abbreviation);

    return "team-color--{$modifier} team--{$abbreviation}";
}
