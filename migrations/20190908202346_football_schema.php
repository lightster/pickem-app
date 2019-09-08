<?php

use function The\db;

// Add football pickem tables

if ($rollback === true) {
    db()->query(<<<SQL
    DROP TABLE picks;
    DROP TABLE games;
    DROP TABLE weeks;
    DROP TABLE teams;
    DROP TABLE seasons;
    SQL);

    return true;
}

$sql = <<<SQL
CREATE TABLE seasons (
    season_id serial PRIMARY KEY,
    year smallint NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE teams (
    team_id serial PRIMARY KEY,
    location varchar NOT NULL,
    mascot varchar NOT NULL,
    abbreviation varchar NOT NULL,
    conference varchar NOT NULL,
    division varchar NOT NULL,
    primary_font_color varchar NOT NULL,
    primary_background_color varchar NOT NULL,
    secondary_background_color varchar NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE weeks (
    week_id serial PRIMARY KEY,
    season_id int NOT NULL REFERENCES seasons,
    start_at timestamptz NOT NULL,
    end_at timestamptz NOT NULL,
    win_weight smallint NOT NULL DEFAULT 1,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE games (
    game_id serial PRIMARY KEY,
    week_id int NOT NULL REFERENCES weeks,
    away_team_id int NOT NULL REFERENCES teams (team_id),
    home_team_id int NOT NULL REFERENCES teams (team_id),
    game_time timestamptz NOT NULL,
    away_score smallint,
    home_score smallint,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE picks (
    pick_id serial PRIMARY KEY,
    user_id int NOT NULL REFERENCES users,
    game_id int NOT NULL REFERENCES games,
    team_id int NOT NULL REFERENCES teams,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    UNIQUE (user_id, game_id)
);
SQL;

db()->query($sql);
