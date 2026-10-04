<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = ['reservation_id', 'name', 'last_name', 'phone'];
}
