<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\Service;

use Pdo;

use Lstr\Silex\Database\DatabaseService;

class FantasyPickService
{
    private $db;
    private $schedule;



    public function __construct(
        DatabaseService $db,
        ScheduleService $schedule
    ) {
        $this->db       = $db;
        $this->schedule = $schedule;
    }



    public function getPicksForWeek($year, $week_number)
    {
        $weeks = $this->schedule->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception\WeekNotFound($year, $week_number);
        }

        $week = $weeks[$week_number];

        $picks = array();

        $db    = $this->db;
        $query = $db->query(
            "
                SELECT
                    playerId AS player_id,
                    gameId AS game_id,
                    teamId AS team_id
                FROM nflFantPick pick
                JOIN nflGame game USING (gameId)
                WHERE weekId = :week_id
                    AND gameTime < :now
            ",
            array(
                'week_id' => $week['week_id'],
                'now'     => gmdate('Y-m-d H:i:s'),
            )
        );
        while ($pick = $query->fetch()) {
            $picks[$pick['game_id']][$pick['player_id']] = $pick;
        }

        return $picks;
    }

    public function savePicks($user_id, array $picks)
    {
        $game_ids     = array_keys($picks);
        $game_ids_sql = implode(',', array_map('intval', $game_ids));

        $valid_game_ids = array();

        $db    = $this->db;

        $query = $db->query(
            "
                SELECT
                    playerId AS player_id
                FROM player_user
                WHERE userId = :user_id
            ",
            array(
                'user_id' => $user_id,
            )
        );
        $player    = $query->fetch();
        $player_id = $player['player_id'];

        $query = $db->query(
            "
                SELECT
                    gameId AS game_id
                FROM nflGame game
                WHERE gameId IN ({$game_ids_sql})
                    AND gameTime < :now
            ",
            array(
                'now'      => gmdate('Y-m-d H:i:s'),
            )
        );
        while ($game = $query->fetch()) {
            $valid_game_ids[] = $game['game_id'];
        }
        $db->query(
            "
                DELETE FROM nflFantPick
                WHERE gameId IN ($game_ids_sql)
                    AND playerId = :player_id
            ",
            array(
                'player_id' => $player_id,
            )
        );

        $row_num    = 0;
        $value_sets = array();
        $values     = array(
            'player_id' => $player_id,
        );
        foreach ($valid_game_ids as $valid_game_id) {
            $value_sets[] = "
                (
                    :player_id,
                    :game_id_{$row_num},
                    :team_id_{$row_num}
                )
            ";
            $values["game_id_{$row_num}"] = $valid_game_id;
            $values["team_id_{$row_num}"] = $picks[$valid_game_id];
            ++$row_num;
        }

        if (count($value_sets)) {
            $values_sql = implode(",\n", $value_sets);
            $db->query(
                "
                    INSERT INTO nflFantPick
                    (
                        playerId,
                        gameId,
                        teamId
                    )
                    VALUES
                    {$values_sql}
                ",
                $values
            );
        }
    }
}
