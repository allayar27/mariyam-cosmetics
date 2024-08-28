<?php

namespace App\Http\Controllers\v1\Clients;

use App\Http\Controllers\Controller;
use App\Models\v1\ClientAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceStatisticController extends Controller
{
    public static function getClientsByDate(Request $request)
    {
        $day = $request->input('day') ? $request->input('day', Carbon::parse()->format('Y-m-d')) : Carbon::today()->toDateString();

        $startDate = $request->input('start_date', Carbon::parse()->startOfDay()) ?? Carbon::today()->toDateString();
        $endDate = $request->input('end_date', Carbon::parse()->endOfDay()) ?? Carbon::today()->toDateString();

        $query = ClientAttendance::query();
        if ($request->has('day')) {
            $clientQuery = $query->whereDate('date', $day);

            $attendanceByTime = DB::table('client_attendances')
                ->whereDate('date', $day)
                ->selectRaw('CONCAT(HOUR(date), ":", LPAD(FLOOR(MINUTE(date) / 30) * 30, 2, "0")) as time,
                         SUM(CASE WHEN clients.gender = "male" THEN 1 ELSE 0 END) as male_count,
                         SUM(CASE WHEN clients.gender = "female" THEN 1 ELSE 0 END) as female_count, COUNT(*) as client_count')
                ->join('clients', 'client_attendances.clients_id', '=', 'clients.id')
                ->groupBy('time', 'clients.gender')
                ->orderBy('time')
                ->get();

        } elseif ($request->has(['start_date', 'end_date'])) {
            $clientQuery = $query->whereBetween('date', [$startDate, $endDate]);

            $attendanceByTime = DB::table('client_attendances')
                ->whereBetween('date', [$startDate, $endDate])
//                    ->selectRaw('gender, CONCAT(HOUR(date), ":", LPAD(FLOOR(MINUTE(date) / 30) * 30, 2, "0")) as time,
//                            COUNT(*) as client_count')
                ->selectRaw('CONCAT(HOUR(date), ":", LPAD(FLOOR(MINUTE(date) / 30) * 30, 2, "0")) as time,
                         SUM(CASE WHEN clients.gender = "male" THEN 1 ELSE 0 END) as male_count,
                         SUM(CASE WHEN clients.gender = "female" THEN 1 ELSE 0 END) as female_count, COUNT(*) as client_count')
                ->join('clients', 'client_attendances.clients_id', '=', 'clients.id')
                ->groupBy('time', 'clients.gender')
                ->orderBy('time')
                ->get();
        }
        else {
            $clientQuery = $query->whereDate('date', $day);
            $attendanceByTime = DB::table('client_attendances')
                ->whereDate('date', $day)
                ->selectRaw('CONCAT(HOUR(date), ":", LPAD(FLOOR(MINUTE(date) / 30) * 30, 2, "0")) as time,
                         SUM(CASE WHEN clients.gender = "male" THEN 1 ELSE 0 END) as male_count,
                         SUM(CASE WHEN clients.gender = "female" THEN 1 ELSE 0 END) as female_count, COUNT(*) as client_count')
                ->join('clients', 'client_attendances.clients_id', '=', 'clients.id')
                ->groupBy('time', 'clients.gender')
                ->orderBy('time')
                ->get();
        }

        $regularClients = $clientQuery->clone()->where('status', 'regular')->count();
        $newClients = $clientQuery->clone()->where('status', 'new')->count();

        // Подсчитываем количество мужчин и женщин
        $maleCount = $clientQuery->clone()->whereHas('clients', function ($query) {
            $query->where('gender', 'male');
        })->count();

        $femaleCount = $clientQuery->clone()->whereHas('clients', function ($query) {
            $query->where('gender', 'female');
        })->count();

        $clients = $clientQuery->with('clients')->get();

        //группировка клиентов по возрастам
        $ageRanges = [
            '1-5' => 0,
            '6-10' => 0,
            '11-15' => 0,
            '16-20' => 0,
            '21-25' => 0,
            '26-30' => 0,
            '31-35' => 0,
            '36-40' => 0,
            '41-45' => 0,
            '46-50' => 0,
            '51-55' => 0,
            '56-60' => 0,
            '61-65' => 0,
            '66-70' => 0,
            '71-75' => 0,
            '76-80' => 0,
            '81-85' => 0,
            '86-100' > 0
        ];
        $ageStatistics = $clients->groupBy(function ($client) use (&$ageRanges) {
            $age = $client->clients->age;
            if ($age >= 1 && $age <= 5) {
                return '1-5';
            } elseif ($age >= 6 && $age <= 10) {
                return '6-10';
            }elseif ($age >= 11 && $age <= 15) {
                return '11-15';
            } elseif ($age >= 16 && $age <= 20) {
                return '16-20';
            }elseif ($age >= 21 && $age <= 25) {
                return '21-25';
            } elseif ($age >= 26 && $age <= 30) {
                return '26-30';
            }elseif ($age >= 31 && $age <= 35) {
                return '31-35';
            }elseif ($age >= 36 && $age <= 40) {
                return '36-40';
            } elseif ($age >= 41 && $age <= 45) {
                return '41-45';
            } elseif ($age >= 46 && $age <= 50) {
                return '46-50';
            }elseif ($age >= 51 && $age <= 55) {
                return '51-55';
            }elseif ($age >= 56 && $age <= 60) {
                return '56-60';
            } elseif ($age >= 61 && $age <= 65){
                return '61-65';
            }elseif ($age >= 66 && $age <= 70) {
                return '66-70';
            }elseif ($age >= 71 && $age <= 75) {
                return '71-75';
            }elseif ($age >= 76 && $age <= 80) {
                return '76-80';
            }elseif ($age >= 81 && $age <= 85) {
                return '81-85';
            } else {
                return '86-100';
            }
        })->map(function ($group) {
            return $group->count();
        })->reverse();

        $totalCount = $clients->count();

        // Рассчитываем процентное соотношение
        $malePercentage = $totalCount > 0 ? ($maleCount / $totalCount) * 100 : 0;
        $femalePercentage = $totalCount > 0 ? ($femaleCount / $totalCount) * 100 : 0;

        return [
            'total_clients' => $totalCount,
            'regular_clients' => $regularClients,
            'new_clients' => $newClients,
            'male_count' => $maleCount,
            'female_count' => $femaleCount,
            'male_percentage' => $malePercentage,
            'female_percentage' => $femalePercentage,
            'age_statistics' => $ageStatistics,
            'peak_attendance' => $attendanceByTime,
        ];
    }
}
