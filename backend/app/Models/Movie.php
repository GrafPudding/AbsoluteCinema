<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    protected $fillable = ['title', 'duration_minutes', 'description', 'poster_url'];

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }
}
