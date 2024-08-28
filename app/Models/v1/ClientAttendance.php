<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAttendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function clients()
    {
        return $this->belongsTo(Clients::class);
    }
}
