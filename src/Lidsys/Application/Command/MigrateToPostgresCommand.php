<?php

namespace Lidsys\Application\Command;

use Lstr\Silex\App\AppAwareInterface;
use Lstr\Silex\App\AppAwareTrait;
use Lstr\Silex\Database\DatabaseService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use The\Db;
use The\DbException;
use The\DbExpr;

class MigrateToPostgresCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('migrate-to-postgres')
            ->setDescription('Migrate data to from MariaDB to Postgres')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();

        $maria = $app['db'];
        $pg = \The\db();

        $output->write("Importing users... ");
        $this->importUsers($maria, $pg);
        $output->writeln("done");

        $output->write("Importing seasons... ");
        $this->importSeasons($maria, $pg);
        $output->writeln("done");

        $output->write("Importing teams... ");
        $this->importTeams($maria, $pg);
        $output->writeln("done");

        $output->write("Importing weeks... ");
        $this->importWeeks($maria, $pg);
        $output->writeln("done");

        $output->write("Importing games... ");
        $this->importGames($maria, $pg);
        $output->writeln("done");

        $output->write("Importing picks... ");
        $this->importPicks($maria, $pg);
        $output->writeln("done");

        $output->writeln("All done");
    }

    private function importUsers(DatabaseService $maria, Db $pg)
    {
        $query = $maria->query(
            <<<SQL
            SELECT
                userId AS user_id,
                username,
                password,
                passwordDate AS password_changed_at,
                securityHash AS security_hash,
                email,
                joinDate AS created_at,
                lastActive AS last_active_at,
                name AS display_name,
                bgcolor AS display_color
            FROM user
            JOIN player_user USING (userId)
            JOIN player USING (playerId)
            SQL
        );
        while ($row = $query->fetch()) {
            $row['password_changed_at'] = $this->cleanDate($row['password_changed_at']);
            $row['created_at'] = $this->cleanDate($row['created_at']) ?: $row['password_changed_at'];

            // prevent 7e9894 from being treated like a number
            $display_color = $pg->quote("#{$row['display_color']}");
            $row['display_color'] = new DbExpr("TRIM(LEADING '#' FROM {$display_color})");

            if ($pg->exists('SELECT 1 FROM users WHERE user_id = $1', [$row['user_id']])) {
                $row['updated_at'] = new DbExpr('NOW()');
                $pg->update('users', $row, 'user_id = $1', [$row['user_id']]);
            } else {
                $pg->insert('users', $row);
            }
        }
    }

    private function importSeasons(DatabaseService $maria, Db $pg)
    {
        $query = $maria->query(
            <<<SQL
            SELECT
                seasonId AS season_id,
                year
            FROM nflSeason
            ORDER BY year
            SQL
        );
        while ($row = $query->fetch()) {
            if ($pg->exists('SELECT 1 FROM seasons WHERE season_id = $1', [$row['season_id']])) {
                $row['updated_at'] = new DbExpr('NOW()');
                $pg->update('seasons', $row, 'season_id = $1', [$row['season_id']]);
            } else {
                $pg->insert('seasons', $row);
            }
        }
    }

    private function importTeams(DatabaseService $maria, Db $pg)
    {
        $query = $maria->query(
            <<<SQL
            SELECT
                teamId AS team_id,
                location,
                mascot,
                abbreviation,
                conference,
                division,
                fontColor AS primary_font_color,
                background AS primary_background_color,
                borderColor AS secondary_background_color
            FROM nflTeam
            SQL
        );
        while ($row = $query->fetch()) {
            try {
                if ($pg->exists('SELECT 1 FROM teams WHERE team_id = $1', [$row['team_id']])) {
                    $row['updated_at'] = new DbExpr('NOW()');
                    $pg->update('teams', $row, 'team_id = $1', [$row['team_id']]);
                } else {
                    $pg->insert('teams', $row);
                }
            } catch (DbException $e) {
                echo "{$e->getLastError()}\n";
                throw $e;
            }
        }
    }

    private function importWeeks(DatabaseService $maria, Db $pg)
    {
        $query = $maria->query(
            <<<SQL
            SELECT
                weekId AS week_id,
                seasonId AS season_id,
                weekStart AS start_at,
                weekEnd AS end_at,
                winWeight as win_weight
            FROM nflWeek
            WHERE seasonId != 0
            SQL
        );
        while ($row = $query->fetch()) {
            try {
                if ($pg->exists('SELECT 1 FROM weeks WHERE week_id = $1', [$row['week_id']])) {
                    $row['updated_at'] = new DbExpr('NOW()');
                    $pg->update('weeks', $row, 'week_id = $1', [$row['week_id']]);
                } else {
                    $pg->insert('weeks', $row);
                }
            } catch (DbException $e) {
                echo "{$e->getLastError()}\n";
                throw $e;
            }
        }
    }

    private function importGames(DatabaseService $maria, Db $pg)
    {
        $query = $maria->query(
            <<<SQL
            SELECT
                gameId AS game_id,
                weekId AS week_id,
                awayId AS away_team_id,
                homeId AS home_team_id,
                gameTime AS game_time
            FROM nflGame
            JOIN nflWeek USING (weekId)
            WHERE seasonId != 0
            SQL
        );
        while ($row = $query->fetch()) {
            try {
                if ($pg->exists('SELECT 1 FROM games WHERE game_id = $1', [$row['game_id']])) {
                    $row['updated_at'] = new DbExpr('NOW()');
                    $pg->update('games', $row, 'game_id = $1', [$row['game_id']]);
                } else {
                    $pg->insert('games', $row);
                }
            } catch (DbException $e) {
                echo "{$e->getLastError()}\n";
                throw $e;
            }
        }
    }

    private function importPicks(DatabaseService $maria, Db $pg)
    {
        $query = $maria->query(
            <<<SQL
            SELECT
                userId AS user_id,
                gameId AS game_id,
                teamId AS team_id
            FROM nflFantPick
            JOIN nflGame USING (gameId)
            JOIN nflWeek USING (weekId)
            JOIN player_user USING (playerId)
            WHERE seasonId != 0
            SQL
        );
        while ($row = $query->fetch()) {
            try {
                $pick_exists = $pg->exists(
                    'SELECT 1 FROM picks WHERE user_id = $1 AND game_id = $2',
                    [$row['user_id'], $row['game_id']]
                );
                if ($pick_exists) {
                    $row['updated_at'] = new DbExpr('NOW()');
                    $pg->update(
                        'picks',
                        $row,
                        'user_id = $1 AND game_id = $2',
                        [$row['user_id'], $row['game_id']]
                    );
                } else {
                    $pg->insert('picks', $row);
                }
            } catch (DbException $e) {
                echo "{$e->getLastError()}\n";
                throw $e;
            }
        }
    }

    private function cleanDate($date)
    {
        if ($date === '0000-00-00 00:00:00') {
            return null;
        }

        return $date;
    }
}
