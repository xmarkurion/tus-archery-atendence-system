<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reg extends Model
{
    // Allow mass assignment for name and number
    protected $fillable = ['name', 'number', 'sessions_attended'];

    public function meeting()
    {
        return $this->belongsToMany(Meeting::class, 'meeting_registrations', 'reg_id', 'meeting_id')->withTimestamps();
    }
}
