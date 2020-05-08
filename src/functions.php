<?php

namespace App;

function team_style(string $abbreviation, string $modifier = 'primary')
{
    $abbreviation = strtolower($abbreviation);

    return "team team-color--{$modifier} team--{$abbreviation}";
}

function score_style(array $game, array $team)
{
    if (!$game['is_final']) {
        return 'score-box';
    }

    $opposing = $game['away'];
    if ($team['team_id'] === $opposing['team_id']) {
        $opposing = $game['home'];
    }

    $modifier = $team['score'] >= $opposing['score'] ? 'winning-team' : 'losing-team';

    return "score-box score-box--{$modifier}";
}
