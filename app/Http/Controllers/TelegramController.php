<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        
    }

    protected function sendTelegramMessage($chat_id, $message)
    {
        $telegramApiUrl = "https://api.telegram.org/bot" . config('services.telegram.api_key') . "/sendMessage";

        $params = [
            'chat_id' => $chat_id,
            'text' => $message,
        ];

        $response = Http::post($telegramApiUrl, $params);
        \Log::info("Response from Telegram: " . $response->body());

        if ($response->successful()) {
            return 'Xabar yuborildi!';
        } else {
            return 'Xato yuz berdi: ' . $response->body();
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
        $url = "https://299e-89-236-210-100.ngrok-free.app/telegram/webhook";

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
