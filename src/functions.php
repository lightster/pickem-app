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

function player_icon_style(string $background_color)
{
    $perceived_luminance = (
            0.299 * hexdec(substr($background_color, 0, 2))
            + 0.587 * hexdec(substr($background_color, 2, 2))
            + 0.114 * hexdec(substr($background_color, 4, 2))
        ) / 255;

    $text_color = ($perceived_luminance >= 0.5 ? '000000' : 'ffffff');

    return sprintf(
        'background-color: #%s; color: #%s;',
        $background_color,
        $text_color
    );
}

function fantasy_points_style( $week_stats, array $player_standing)
{
    $background_color = null;
    if ($week_stats['max_points'] === $week_stats['min_points']) {
        return '';
    }

    if (!isset($player_standing['points'])) {
        return '';
    }

    if ($week_stats['max_points'] === $player_standing['points']) {
        return 'background-color: #ffff99';
    }

    $points_diff = $player_standing['points'] - $week_stats['min_points'];
    $points_range = $week_stats['max_points'] - $week_stats['min_points'];
    $int_color = intval(192 * (1 - ($points_diff / $points_range)));

    return 'color: #' . str_repeat(dechex($int_color), 3);
}
