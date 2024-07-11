<?php

namespace App\Http\Controllers\v1\Users;

use App\Models\v1\User;
use App\Models\v1\Image;
use App\Models\v1\Schedule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\v1\User\UserAddRequest;
use App\Http\Requests\v1\User\UserUpdateRequest;
use App\Http\Requests\v1\User\UserAddImageRequest;
use App\Http\Resources\v1\User\UserImagesResource;

class UserController extends Controller
{
    public function add(UserAddRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
            'position_id' => $data['position_id'],
            'phone' => $data['phone'],
            'schedule_id' => $data['schedule_id'],
        ]);
        $this->uploadImages($user, $request);
        return response()->json([
            'success' => true,
            'message' => 'Successfully created'
        ], 201);
    }

    public function all()
    {
        $users = User::latest()->get();
        return response()->json([
            'total' => $users->count(),
            'data' => UserImagesResource::collection($users)
        ]);
    }

    public function update($id, UserUpdateRequest $request)
    {
        $user = User::findOrFail($id);
        if ($user) {
            $data = $request->validated();
            $user->update($data);
            $this->uploadImages($user, $request);
            return response()->json([
                'uccess' => true,
            ]);
        }
    }

    public function add_image($id, UserAddImageRequest $request)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();
        $path = 'users/' . $user->id . '/images/';
        $user->images()->create([
            'name' => $data['image'],
            'path' => $path
        ]);
        return response()->json([
            'success' => true,
        ], 201);
        
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        if ($user) {
            $user->delete();
            return response()->json([
                'success' => true,
            ]);
        }
    }

    protected function uploadImages($user, $request)
    {
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
    }

    public function delete_image($id)
    {
        $image = Image::findOrFail($id);
        if ($image) {
            $image_path = 'public/' . $image->path . $image->name;
            if (Storage::exists($image_path)) {
                Storage::delete($image_path);
                $image->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            }
        }
        return response()->json([
            'success' => false,
            'message' => 'Image not found'
        ], 404);
    }
}
