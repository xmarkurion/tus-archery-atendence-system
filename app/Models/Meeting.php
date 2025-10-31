<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    // allow mass assignment
    protected $guarded = [];

    protected $fillable = [
        'sessions_attended',
        'start_time',
        'end_time',
        'info',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
