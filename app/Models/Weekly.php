<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weekly extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'time_in',
        'time_out',
        'day',
        'is_work_day',
    ];
}
