<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name'
    ];

    public function  users(){
        return $this->hasMany(User::class);
    }
    
}
