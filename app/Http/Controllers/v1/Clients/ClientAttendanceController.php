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

            // Проверяем, было ли посещение сегодня
            $lastAttendance = ClientAttendance::query()->with('clients')
                ->where('device_id', $validated['device_id'])
                ->where('clients_id', $validated['client_id'])
                ->where('date', '<=', $yesterday)
                ->latest('date')
                ->first();

            if ($lastAttendance) {
                $lastTime = Carbon::createFromFormat('Y-m-d H:i:s', $lastAttendance->date, 'UTC');
                $calculateAge = round(($lastAttendance->clients->age + $validated['age']) / 2);
                $lastAttendance->clients()->update(['age' => $calculateAge]);

                if($lastAttendance->score >= $validated['score']){
                }
                else {
                    $lastAttendance->clients()->update(['gender' => $validated['gender']]);
                }
                if ($lastTime->diffInHours($time) >= 24) {
                    // Обновляем или добавляем запись как для постоянного клиента
                    $userStatus = 'regular';
                }
                else {
                    $userStatus = 'new';
                }
            }
            else {
                $userStatus = 'new';
            }

            // добавляем запись в таблицу посещаемость
            ClientAttendance::query()->create([
                'clients_id' => $client->id,
                'device_id' => $validated['device_id'],
                'date' => $time,
                'score' => $validated['score'],
                'status' => $userStatus
            ]);


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