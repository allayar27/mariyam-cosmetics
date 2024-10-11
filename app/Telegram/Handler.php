<?php

namespace App\Telegram;

use App\Models\v1\Attendance;
use Carbon\Carbon;
use DefStudio\Telegraph\Handlers\WebhookHandler;

class Handler extends WebhookHandler
{
    public function start()
    {
        $this->reply("Здравствуйте это контроль бот для Maryam Cosmetics!");
    }

    public function attendances()
    {
        $today = Carbon::today();
        $getUniqueAttendances = Attendance::whereDate('day', $today)
            ->where('type', 'in')
            ->get();

        $data = $this->formattedData($getUniqueAttendances);

        $this->reply($data);
    }

    private function formattedData($attendances)
    {
        if (count($attendances) > 0) {
        $data = "*Cегодняшняя посещаемость:*\n";
            foreach ($attendances as $attendance) {
                $data .= "\n*Имя сотрудника:* {$attendance->user->name}\n";
                $data .= "*Дата:* " . Carbon::parse($attendance->day)->format('Y-m-d') . "\n";
                $data .= "*Время:* " . Carbon::parse($attendance->time)->format('H:i:s') . "\n";
                $data .= "*Тип:* {$attendance->type}\n";
                $data .= "---------------------------------------\n";
            }
            return $data;
        }
        
        return "На сегодня пока нет посещений";
    }
}