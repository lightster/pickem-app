<?php

namespace App\Models;

use The\Model;

class Team extends Model
{
    protected static $table_name  = 'teams';
    protected static $primary_key = 'team_id';

    protected $data = [
        'team_id'                    => Model::DEFAULT,
        'location'                   => null,
        'mascot'                     => null,
        'abbreviation'               => null,
        'conference'                 => null,
        'division'                   => null,
        'primary_font_color'         => null,
        'primary_background_color'   => null,
        'secondary_background_color' => null,
        'created_at'                 => Model::DEFAULT,
        'updated_at'                 => Model::DEFAULT,
    ];
}
