<?php

namespace Lidsys\Football\Service;

use The\Db;
use The\DbExpr;

class FantasyPickService
{
    private $db;
    private $schedule;



    public function __construct(
        Db $db,
        ScheduleService $schedule
    ) {
        $this->db       = $db;
        $this->schedule = $schedule;
    }



    public function getPicksForWeek($year, $week_number, $user_id)
    {
        $weeks = $this->schedule->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception\WeekNotFound($year, $week_number);
        }

        $week = $weeks[$week_number];

        $picks = array();

        $db    = $this->db;
        $query = $db->query(
            <<<'SQL'
            SELECT
                user_id AS player_id,
                game_id,
                team_id
            FROM picks
            JOIN games USING (game_id)
            WHERE week_id = $1
                 AND (game_time < $2 OR user_id = $3)
            SQL,
            [$week['week_id'], new DbExpr('NOW()'), $user_id]
        );
        while ($pick = $query->fetchRow()) {
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
            <<<SQL
            SELECT game_id
            FROM games
            WHERE game_id IN ({$game_ids_sql})
                AND game_time >= $1
            SQL,
            [new DbExpr('NOW()')]
        );
        while ($game = $query->fetchRow()) {
            $valid_game_ids[] = $game['game_id'];
        }

        if (!count($valid_game_ids)) {
            return;
        }

        foreach ($valid_game_ids as $game_id) {
            $updated_row = $db->update(
                'picks',
                [
                    'team_id'    => $picks[$game_id],
                    'updated_at' => new DbExpr('NOW()'),
                ],
                'user_id = $1 AND game_id = $2',
                [$user_id, $game_id]
            );

            if (!$updated_row) {
                $db->insert(
                    'picks',
                    [
                        'user_id' => $user_id,
                        'game_id' => $game_id,
                        'team_id' => $picks[$game_id],
                    ]
                );
            }
        }

        $valid_game_ids_sql = implode(', ', $valid_game_ids);

        $saved_picks = array();
        $query = $db->query(
            <<<SQL
            SELECT
                user_id AS player_id,
                game_id,
                team_id
            FROM picks
            WHERE game_id IN ($valid_game_ids_sql)
                AND user_id = $1
            SQL,
            [$user_id]
        );
        while ($saved_pick = $query->fetchRow()) {
            $saved_picks[] = $saved_pick;
        }

        return $saved_picks;
    }
}
