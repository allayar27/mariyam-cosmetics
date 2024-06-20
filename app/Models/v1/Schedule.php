<?php

namespace App\Models\v1;

use Carbon\Carbon;
use App\Models\Weekly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];
    public function days():HasMany
    {
        return $this->hasMany(Weekly::class);
    }

    public function users():HasMany
    {
        return $this->hasMany(User::class);
    }

    public function time_in($day){
        $day = Carbon::parse($day)->format('l');
        return $this->days()->where('day', $day)->first()->time_in;
    }

    public function time_out($day){
        $day = request('day') ?? Carbon::now();
        $day = Carbon::parse($day)->format('l');
        return $this->days()->where('day', $day)->first()->time_out;
    }
}
