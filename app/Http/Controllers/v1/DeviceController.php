<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;


class DeviceController extends Controller
{
    public function all(){
        $data = Carbon::now();
    
        return gettype($data);
    }
}
