<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'time_in',
        'time_out',
    ];

    protected $casts = [
        'time_in' =>'datetime:h:i:s',
        'time_out' =>'datetime:h:i:s',
        'created_at' => 'datetime', 
        'updated_at' => 'datetime',
    ];

}
