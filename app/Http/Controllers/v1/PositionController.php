<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Position\PositionAddRequest;
use App\Http\Requests\v1\Position\PositionUpdateRequest;
use App\Http\Resources\v1\Position\PositionsResource;
use App\Models\v1\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function add(PositionAddRequest $request){
        $data = $request->validated();
        Position::create([
            'name' => $data['name'],
        ]);
        return response()->json([
            'success' => true,
        ],201);
    }

    public function update(Position $position,PositionUpdateRequest $request){
        if($position){
            $position->update([
                'name' => $request->name
            ]);
            return response()->json([
                'success' => true,
            ]);
        }
    }
    public function delete(Position $position){
        if($position){
            if($position->name =='unknown'){
                return response()->json([
                   'success' => false,
                   'message' => 'You can not delete this position'
                ],400);
            }
            $position_id = Position::where('name','unknown')->first()->id;
            $users = $position->users;
            $users->each(function($user) use ($position_id) {
                $user->position_id = $position_id;
                $user->save();
            });
            $position->delete();
            return response()->json([
               'success' => true,
            ]);
        }
    }

    public function all_positions(){
        $positions = Position::latest()->get();
        return response()->json([
            'success' => true,
            'data' => PositionsResource::collection($positions)
        ]);
    }

}
