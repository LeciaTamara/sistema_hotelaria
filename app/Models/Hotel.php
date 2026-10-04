<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = ['id', 'name'];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
