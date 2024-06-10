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

  

    public function  users(){
        return $this->hasMany(User::class);
    }
     public function  work_days(){
        return $this->hasMany(Work_Days::class);
    }
}
