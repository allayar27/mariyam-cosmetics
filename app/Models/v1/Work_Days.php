<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Work_Days extends Model
{
    use HasFactory;

    protected $fillable = ['work_day', 'branch_id', 'type','total_workers', 'workers_count', 'late_workers'];

    
    protected $casts = [
        'created_at' => 'datetime:d/m/Y h:i:s', 
        'updated_at' => 'datetime:d/m/Y h:i:s',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'day', 'work_day');
    }

}
