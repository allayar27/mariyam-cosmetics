<?php

namespace App\Http\Controllers\v1\Auth;

use App\Models\v1\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Employee\EmployeeAddRequest;
use App\Http\Resources\v1\Employee\EmployeeGetResource;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function login(Request $request)
    {
        $user = Employee::where('email', $request->email)->first();
        if (!$user or !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid password or email",
            ], 403);
        }
        $token = $user->createToken('employee')->plainTextToken;
        return response()->json([
            'success' => true,
            'token' => $token
        ]);
    }

    public function add(EmployeeAddRequest $request){
        $data = $request->validated();
        if(auth()->user()->role == 'superadmin'){
            $data['role'] ='admin';
        }elseif(auth()->user()->role == 'admin'){
            $data['role'] ='moderator';
        }

        Employee::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' =>  Hash::make($data['password']),
            'role' => $data['role'],
        ]);
        return response()->json([
           'success' => true,
           'message' => 'Employee added successfully'
        ]);
    }

    public  function getme(){
        $user = auth()->user();
        return response()->json([
           'success' => true,
            'data' => new EmployeeGetResource($user)
        ]);
    }

}
