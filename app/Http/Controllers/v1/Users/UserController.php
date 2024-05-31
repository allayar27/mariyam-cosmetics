<?php

namespace App\Http\Controllers\v1\Users;

use App\Models\v1\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\User\UserAddImageRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\v1\User\UserAddRequest;
use App\Http\Requests\v1\User\UserUpdateRequest;
use App\Http\Resources\v1\User\UserImagesResource;
use App\Models\v1\Schedule;
use GuzzleHttp\Psr7\Response;

class UserController extends Controller
{
    public function add(UserAddRequest $request){
        $data = $request->validated();
        $user = User::create([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
            'position_id' => $data['position_id'],
            'phone' => $data['phone'],
        ]);
        Schedule::create([
            'user_id' => $user->id,
            'time_in' => $data['time_in'],
            'time_out' => $data['time_out']
        ]);
        if ($request->hasFile('images')) {
            $path = 'users/' . $user->id . '/images/';
            Storage::makeDirectory('public/' . $path);
        
            foreach ($request->file('images') as $image) {
                $image_name = time() . "-" . Str::random(10) . "." . $image->getClientOriginalExtension();
                $image->move(storage_path('app/public/' . $path), $image_name);
        
                $user->images()->create([
                    'name' => $image_name,
                    'path' => $path 
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully created'
        ], 201);
    }
       
    public function all(){
        $users = User::latest()->get();
        return response()->json([
            'data' => UserImagesResource::collection($users)
        ]);
    }

    public function update($id,UserUpdateRequest $request){
        $user = User::findOrFail($id);
        if($user){
            $user->update([
                'name' => $request->input('name',$user->name),
                'branch_id' => $request->input('branch_id',$user->branch_id),
                'position_id' => $request->input('position_id',$user->position_id),
                'phone' => $request->input('phone',$user->phone),
            ]);
            $user->schedule()->update([
                'time_in' => $request->input('time_in',$user->schedule->time_in),
                'time_out' => $request->input('time_out',$user->schedule->time_out),
            ]);
            if ($request->hasFile('images')) {
                $path = 'users/' . $user->id . '/images/';
                foreach ($request->file('images') as $image) {
                    $image_name = time() . "-" . Str::random(10) . "." . $image->getClientOriginalExtension();
                    $image->move(storage_path('app/public/' . $path), $image_name);
                    $user->images()->create([
                        'name' => $image_name,
                        'path' => $path 
                    ]);
                }
            }
            return response()->json([
                'success' => true,
            ]);
            
        }
    }

    public function add_image($id,UserAddImageRequest $request){
        $user =  User::findOrFail($id);
        $data = $request->validated();
        $path = 'users/' . $user->id . '/images/';
        $user->images()->create([
            'name' => $data['image'],
            'path' => $path
        ]);
    }
}
