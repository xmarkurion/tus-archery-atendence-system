<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSetting extends Model
{
    protected $table = 'meeting_settings';

    protected $casts = [
        'selected_days' => 'array',
        'enabled' => 'boolean',
        'default_start_time' => 'string',
        'default_duration' => 'integer',
    ];

    protected $fillable = [
        'selected_days',
        'enabled',
        'default_start_time',
        'default_duration',
    ];

    public static function singleton()
    {
        return static::first();
    }
}
