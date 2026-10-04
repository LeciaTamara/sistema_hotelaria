<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Daily extends Model
{
    protected $fillable = ['reservation_id', 'date', 'value'];
}
