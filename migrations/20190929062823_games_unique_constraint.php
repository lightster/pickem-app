<?php

use function The\db;

// Add a unique constraint on team IDs and week ID

if ($rollback === true) {
    db()->query("DROP INDEX games_week_id_team_ids_uniq;");
    return true;
}

$sql = <<<SQL
CREATE UNIQUE INDEX games_week_id_team_ids_uniq ON games (week_id, away_team_id, home_team_id);
SQL;

db()->query($sql);
