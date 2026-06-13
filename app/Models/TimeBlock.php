<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeBlock extends Model
{
    protected $fillable = ['starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
