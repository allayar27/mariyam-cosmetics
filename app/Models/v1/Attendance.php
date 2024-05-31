<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'time',
        'device_id',
        'type',
        'score',
        'date',
        'branch_id',
    ];

    protected $casts = [
        'time' => 'datetime:h:i:s',
        'date' => 'datetime:d/m/Y H:i:s',
        'created_at' => 'datetime:d/m/Y h:i:s',
        'updated_at' => 'datetime:d/m/Y h:i:s',
    ];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(Work_Days::class, 'day', 'work_day');
    }
    public function device(){
        return $this->belongsTo(Device::class);
    }
}
