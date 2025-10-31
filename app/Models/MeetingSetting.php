<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSetting extends Model
{
    protected $table = 'meeting_settings';

    protected $casts = [
        'selected_days' => 'array',
        'enabled' => 'boolean',
    ];

    protected $fillable = [
        'selected_days',
        'enabled',
    ];

    public static function singleton()
    {
        return static::first();
    }
}
