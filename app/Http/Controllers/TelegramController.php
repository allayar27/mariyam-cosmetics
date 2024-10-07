<?php

namespace App\Http\Controllers;

use App\Models\v1\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $input = $request->all();
        $message = $input['message'];
        $chat_id = $message['chat']['id'];
        $text = $message['text'];

        $today = Carbon::today();
        $getUniqueAttendances = Attendance::whereDate('day', $today)
            ->where('type', 'in')
            ->distinct('user_id')
            ->get();

        $data = $this->formattedData($getUniqueAttendances);
        //return $data;
        if ($text == '/start') {
            $this->call('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Asssalomu alaykum"
            ]);
        }elseif ($text == '/attendances'){
            $this->call('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $data,
                'parse_mode' => 'Markdown'
            ]);
        }else {
            $this->call('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Unknown command"
            ]);
        }
    }

    private function formattedData($attendances)
    {
        $data = "*Attendance Data:*\n";

        foreach ($attendances as $attendance) {
        $data .= "\n*Employee Name:* {$attendance->user->name}\n";
        $data .= "*Date:* " . Carbon::parse($attendance->day)->format('Y-m-d') . "\n";
        $data .= "*Time:* " . Carbon::parse($attendance->time)->format('H:i:s') . "\n";
        $data .= "*Device ID:* {$attendance->device_id}\n";
        $data .= "*Type:* {$attendance->type}\n";
        $data .= "------------------------\n";
        }
        return $data;
    }

    private function call(string $method, $params = [])
    {
        $url = "https://api.telegram.org/bot" . config('services.telegram.api_key') . "/" . $method;
        $response = Http::post($url, $params);
        return $response->json();
    }

    public function setWebhook()
    {
        $url = "https://maryiam-cosmetics.faceai.uz/telegram/webhook";

        $telegram_api_url = "https://api.telegram.org/bot" . config('services.telegram.api_key') . "/setWebhook";

        $response = Http::post($telegram_api_url, [
            'url' => $url,
        ]);

        if ($response->successful()) {
            return "Webhook установлен!";
        } else {
            return "Ошибка при установке webhook: " . $response->body();
        }
    }
}
