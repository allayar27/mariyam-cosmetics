<?php

namespace App\Models\v1;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

     protected $casts = [
        'created_at' => 'datetime:d/m/Y h:i:s', 
        'updated_at' => 'datetime:d/m/Y h:i:s',
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

    //  public function getImageUrl()
    //  {
    //      // Rasm yo'li va nomini saqlash usuli, buni loyihangiz talablariga moslashtiring
    //      if ($this->image_path && $this->image_name) {
    //          return url("storage/" . $this->image_path . $this->image_name);
    //      }
 
    //     //  // Agar rasm mavjud bo'lmasa, default rasm URL qaytarish
    //     //  return url("storage/default.jpg");
    //  }
}
