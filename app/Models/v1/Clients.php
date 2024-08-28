<?php

namespace App\Models\v1;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    use HasFactory;

    protected $table = 'clients';
    protected $fillable = ['gender', 'age'];

    public function attendances() {
        return $this->hasMany(ClientAttendance::class);
    }
}
