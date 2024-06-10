<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class);
    }
}
