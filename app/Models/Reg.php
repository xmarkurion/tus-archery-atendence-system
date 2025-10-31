<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reg extends Model
{
    // Allow mass assignment for name and number
    protected $fillable = ['name', 'number'];

}
