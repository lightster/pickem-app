<?php

use function The\db;

if ($rollback === true) {
    $sql = <<<SQL
    ALTER TABLE weeks DROP COLUMN week_type;
        
    DROP TYPE week_type;
    SQL;

    db()->query($sql);
    return true;
}

$sql = <<<SQL
CREATE TYPE week_type AS ENUM ('regular', 'wildcard', 'division', 'conference', 'final');

ALTER TABLE weeks ADD COLUMN week_type week_type;
    
UPDATE weeks SET week_type = CASE win_weight
        WHEN 1 THEN 'regular'
        WHEN 2 THEN 'wildcard'
        WHEN 4 THEN 'division'
        WHEN 8 THEN 'conference'
        WHEN 16 THEN 'final'
    END::week_type;
    
ALTER TABLE weeks ALTER COLUMN week_type SET NOT NULL;
SQL;

db()->query($sql);
