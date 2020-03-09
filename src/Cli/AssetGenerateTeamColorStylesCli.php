<?php

namespace App\Cli;

use App\Models\Team;
use The\Cli\Cli;
use function The\option;

class AssetGenerateTeamColorStylesCli extends Cli
{
    public function run(array $args)
    {
        echo "Building _team_colors.scss... ";

        $classes = [];
        foreach (Team::fetchAllWhere('true') as $team) {
            $abbr = strtolower($team->getData('abbreviation'));

            $classes[] = <<<SCSS
                .team-color--primary.team--{$abbr} {
                  color: {$team->getData('primary_font_color')};
                  background-color: {$team->getData('primary_background_color')};
                }
                SCSS;
            $classes[] = <<<SCSS
                .team-color--secondary.team--{$abbr} {
                  background-color: {$team->getData('secondary_background_color')};
                }
                SCSS;
        }

        $status = file_put_contents(
            option('root_dir') . '/assets/sass/_team_colors.scss',
            implode("\n", $classes)
        );

        echo "done\n";

        return $status === false ? 1 : 0;
    }
}
