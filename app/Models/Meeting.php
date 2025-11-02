<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    // allow mass assignment
    protected $guarded = [];

    protected $fillable = [
        'sessions_attended',
        'start_time',
        'end_time',
        'info',
        'pin',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function regs()
    {
        return $this->belongsToMany(Reg::class, 'meeting_registrations', 'meeting_id', 'reg_id')->withTimestamps();
    }

    // convenience alias
    public function attendees()
    {
        return $this->regs();
    }
}
