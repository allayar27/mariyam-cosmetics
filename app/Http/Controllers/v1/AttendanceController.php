<?php

namespace App\Http\Controllers\v1;

use App\Models\v1\Device;
use App\Models\v1\Image;
use App\Models\v1\User;
use App\Models\v1\Attendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Attendance\AttendanceAddRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function add(AttendanceAddRequest $request)
    {
        DB::beginTransaction();
        try {
            $image = Image::findOrFail($request->image_id);
            $user = $image->imageable;
            if (!($user instanceof User)) {
                return response()->json([
                    'error' => 'Image is not associated with a valid user.'
                ], 400);
            }
            $device = Device::findOrFail($request->device_id);
            if ($user->branch->id != $device->branch->id) {
                return response()->json([
                    'error' => 'Invalid person'
                ], 400);
            }
            $validated = $request->validated();
            $time = Carbon::createFromFormat('H:i', $validated['time'])->format('H:i:s');
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'device_id' => $validated['device_id'],
                'time' => $time,
                'score' => $validated['score'],
            ]);
            foreach ($validated['images'] as $image) {
                $attendance->images()->create([
                    'name' => $image,
                    'path' => 'users/' . $user->id . '/attendances/'
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'An error occurred while recording attendance.',
                'details' => $e->getMessage(),
                'line' => $e->getLine(),
                'body'
            ], 500);
        }
    }

    // public function all($id)
    // {
    //     $user = User::findOrFail($id);
    //     $today = Carbon::today();
    //     $todayAttendances = Attendance::where('user_id', $user->id)
    //         ->whereDate('created_at', $today)
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     return response()->json($todayAttendances->first());
    // }


    // creating attendance with responsejobs

    // public function addWithResponseJobs(AttendanceAddRequest $request){
    //     DB::beginTransaction();
    //     try {
    //         $image = Image::findOrFail($request->image_id);
    //         $user = $image->imageable;
    //         if (!($user instanceof User)) {
    //             return response()->json([
    //                 'error' => 'Image is not associated with a valid user.'
    //             ], 400);
    //         }
    //         $device = Device::findOrFail($request->device_id);
    //         if ($user->branch->id != $device->branch->id) {
    //             return response()->json([
    //                 'error' => 'Invalid person'
    //             ], 400);
    //         }

    //         $validated = $request->validated();
    //         $time = Carbon::createFromFormat('H:i', $validated['time'])->format('H:i:s');

    //         $attendance = Attendance::create([
    //             'user_id' => $user->id,
    //             'device_id' => $validated['device_id'],
    //             'time' => $time,
    //             'score' => $validated['score'],
    //             'day' =>Carbon::today()->format('Y-m-d'),
    //             'type' =>'in',
    //             'branch_id' => $user->branch_id,
    //         ]);
    //         foreach ($validated['images'] as $image) {
    //             $attendance->images()->create([
    //                 'name' => $image,
    //                 'path' => 'users/' . $user->id . '/attendances/'
    //             ]);
    //         }

    //         DB::commit();
    //         AttendanceControl::dispatchAfterResponse($attendance);
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Attendance recorded successfully',
    //         ], 201);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'error' => 'An error occurred while recording attendance.',
    //             'details' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'body'
    //         ], 500);
    //     }
    // }
    
}
