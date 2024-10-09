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
use Illuminate\Support\Facades\Http;

class AttendanceController extends Controller
{
    public function add(AttendanceAddRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($request->user_id);
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
            // $time = Carbon::createFromFormat('H:i:s', $validated['time'])->format('H:i:s');
            $time = $validated['time'];
            $today = Carbon::today();
            $existingAttendance = Attendance::where('user_id', $request->user_id)
                ->where('type', 'in')
                ->whereDate('day', $today)
                ->exists();

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
            if (!$existingAttendance) {
                \Log::info('This is the first attendance of the day');
                \Log::info('Existing attendance found: ' . ($existingAttendance ? 'Yes' : 'No'));

                $this->sendAttendanceNotification($attendance);
            }

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

    protected function sendAttendanceNotification(Attendance $attendance)
    {
        $user = $attendance->user;
        $message = "*Добавлено новое посещение:*\n";
        $message .= "*Имя сотрудника:* {$user->name}\n";
        $message .= "*Дата:* {$attendance->day}\n";
        $message .= "*Время:* {$attendance->time}\n";
        $message .= "*Тип:* {$attendance->type}\n";
        $message .= "*Device ID:* {$attendance->device_id}\n";

        $this->sendTelegramMessage($message);
    }

    protected function sendTelegramMessage($message)
    {
        $telegramApiUrl = "https://api.telegram.org/bot" . config('services.telegram.api_key') . "/sendMessage";

        $params = [
            'chat_id' => config('services.telegram.chat_id'),
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];

        Http::post($telegramApiUrl, $params);
    }
}
