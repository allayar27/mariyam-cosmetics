<?php

namespace App\Http\Controllers\v1\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Client\ClientAttendanceAddRequest;
use App\Models\v1\ClientAttendance;
use App\Models\v1\Clients;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientAttendanceController extends Controller
{
    public function add(ClientAttendanceAddRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $client = Clients::query()->firstOrCreate(
                ['id' => $validated['client_id']],
                [
                    'gender' => $validated['gender'],
                    'age' => $validated['age'],
                ]);

            $time = Carbon::createFromFormat('Y-m-d H:i:s', $validated['date'], 'UTC');
            $yesterday = $time->copy()->subDay()->format('Y-m-d H:i:s');

            $query = ClientAttendance::query();
            $gender = $query->where('device_id', $validated['device_id'])
                ->where('clients_id', $validated['client_id'])->pluck('gender');

            $lastAttendance = $query->with('clients')
                ->where('device_id', $validated['device_id'])
                ->where('clients_id', $validated['client_id'])
                ->where('date', '<=', $yesterday)
                ->latest('date')
                ->first();

            
            $male = 0;
            $female = 0;
            foreach ($gender as $item) {
                if ($item == 'female') {
                    $female++;
                }
                elseif ($item == 'male') {
                    $male++;
                }
            }

            // $todayAttendance = ClientAttendance::query()
            // ->where('device_id', $validated['device_id'])
            // ->where('clients_id', $validated['client_id'])
            // ->whereDate('date', '=', $time->format('Y-m-d'))
            // ->first();

            if ($lastAttendance) {
                
                $calculateAge = round(($lastAttendance->clients->age + $validated['age']) / 2);
                $lastAttendance->clients()->update(['age' => $calculateAge]);
        
                if ($male > $female) {
                    if ($validated['gender'] == 'female') {
                        $lastAttendance->clients()->update(['gender' => 'male']);
                    }
                        
                }
                elseif ($female > $male) {
                    if ($validated['gender'] == 'male') {
                        $lastAttendance->clients()->update(['gender' => 'female']);
                    }
                }
                else {
                    if ($lastAttendance->score < $validated['score']) {
                        $lastAttendance->clients()->update(['gender' => $validated['gender']]);
                    }
                }

                if ($lastAttendance->date <= $yesterday) {
                    $userStatus = 'regular';
                }
                else {
                    $userStatus = 'new';
                }
            }
            else {
                $userStatus = 'new';
            }

            // if ($todayAttendance) {
            //     $userStatus = $todayAttendance->status;
            // }
            // else {
                ClientAttendance::query()->create([
                    'clients_id' => $client->id,
                    'device_id' => $validated['device_id'],
                    'date' => $time,
                    'score' => $validated['score'],
                    'status' => $userStatus,
                    'gender' => $validated['gender']
                ]);
            //}

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully',
                'status' => $userStatus
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'An error occurred while recording attendance.',
                'details' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

}
