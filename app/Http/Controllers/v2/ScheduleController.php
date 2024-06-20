<?php

namespace App\Http\Controllers\v2;

use Carbon\Carbon;
use App\Models\Weekly;
use App\Models\v1\User;
use App\Models\v1\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\v2\ScheduleAddRequest;
use App\Http\Resources\v2\Schedule\SchedulesResource;

class ScheduleController extends Controller
{
    public function create(ScheduleAddRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $schedule = Schedule::create([
                'name' => $data['name'],
            ]);

            foreach ($data['days'] as $day) {
                DB::table('weeklies')->insert([
                    'schedule_id' => $schedule->id,
                    'day' => $day['day_of_week'],
                    'time_in' => $day['time_in'],
                    'time_out' => $day['time_out'],
                    'is_work_day' => $day['is_work_day'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Schedule created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ], 500);
        }
       
    }


    public function delete($id)
    {
        $schedule = Schedule::findOrFail($id);
        if ($schedule->id == 1) {
            return response()->json([
                'success' => false,
                'message' => 'You can not delete this schedule'
            ], 400);
        }
        if ($schedule) {
            $schedule->days()->delete();
            $schedule->delete();
            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully'
            ], 201);
        }
        return response()->json([
            'success' => false,
            'message' => 'Schedule not found'
        ], 404);
    }

    public function update($id, ScheduleAddRequest $request)
    {
        $data = $request->validated();
        $schedule = Schedule::findOrFail($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $schedule->update([
                'name' => $data['name']
            ]);
            $days = $schedule->days()->get();
            foreach ($days as $day) {
                Weekly::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'day' => $day['day_of_week']
                    ],
                    [
                        'time_in' => $day['time_in'],
                        'time_out' => $day['time_out'],
                        'is_work_day' => $day['is_work_day']
                    ]
                );
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ], 500);
        }
    }

    public function all(Request $request){
        $schedules  = Schedule::all();
        return response()->json([
            'success' => true,
            'total' => $schedules->count(),
            'data' => SchedulesResource::collection($schedules)
        ]);
    }
}
