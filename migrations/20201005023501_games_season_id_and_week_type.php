<?php

use function The\db;

if ($rollback === true) {
    $sql = <<<SQL
    DROP INDEX games_week_type_teams_uniq;
    
    ALTER TABLE games
        DROP COLUMN season_id,
        DROP COLUMN week_type;
    SQL;

    db()->query($sql);
    return true;
}

$sql = <<<SQL
ALTER TABLE games
    ADD COLUMN season_id int,
    ADD COLUMN week_type week_type;

UPDATE games
SET season_id = w.season_id, week_type = w.week_type 
FROM weeks w
WHERE games.week_id = w.week_id;
    
ALTER TABLE games
    ALTER COLUMN season_id SET NOT NULL,
    ALTER COLUMN week_type SET NOT NULL;
    
CREATE UNIQUE INDEX games_week_type_teams_uniq ON games (season_id, week_type, away_team_id, home_team_id);
SQL;

db()->query($sql);
