<?php

namespace App\Models\v1;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'position_id',
        'branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];


    public function images(){
        return $this->morphMany(Image::class,'imageable');
    }

    public function schedule(){
        return $this->hasOne(Schedule::class);
    }
    public function branch(){
        return $this->belongsTo(Branch::class);
     }

     public function attendance(){
        return $this->hasMany(Attendance::class);
     }
     public function position(){
        return $this->belongsTo(Position::class);
     }
    public static function getUsersByDateAndBranch($day, $id)
    {
        $day = $day ? Carbon::parse($day)->addDay() : Carbon::now();
        $usersQuery = $id ? Branch::findOrFail($id)->users()->withTrashed() : static::query()->withTrashed();
        $usersQuery->where('created_at', '<=', $day)
                   ->where(function ($query) use ($day) {
                       $query->whereNull('deleted_at')
                             ->orWhere('deleted_at', '>', $day);
                   });
        return $usersQuery;
    }
}
