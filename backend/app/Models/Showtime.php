<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Showtime extends Model
{
    protected $fillable = ['movie_id', 'starts_at', 'auditorium'];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
