<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected  $fillable = [
        'name',
        'location',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y h:i:s', 
        'updated_at' => 'datetime:d/m/Y h:i:s',
    ];

    public function  users(){
        return $this->hasMany(User::class);
    }

    // public function  attendances(){
    //     return $this->hasMany(Attendance::class);
    // }
     public function  work_days(){
        return $this->hasMany(Work_Days::class);
    }
}
