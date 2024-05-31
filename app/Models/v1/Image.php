<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;
    protected $guarded= ['id'];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y h:i:s', 
        'updated_at' => 'datetime:d/m/Y h:i:s',
    ];

    public function imageable()
    {
        return $this->morphTo();
    }
}
