<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringBlock extends Model
{
    protected $fillable = ['day_of_week', 'start_time', 'end_time', 'reason'];
}
