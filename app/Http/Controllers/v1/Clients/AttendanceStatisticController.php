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

        $query = ClientAttendance::query()->distinct('clients_id');
        if ($request->has('day')) {
            $clientQuery = $query->whereDate('date', $day);

            $uniqueClients = DB::table('client_attendances')
                ->whereDate('date', $day)
                ->join('clients', 'client_attendances.clients_id', '=', 'clients.id')
                ->selectRaw('clients_id, clients.gender, MIN(CONCAT(HOUR(date), ":", LPAD(FLOOR(MINUTE(date) / 30) * 30, 2, "0"))) as first_time')
                ->groupBy('clients_id', 'clients.gender')
                ->get();

            $attendanceByTime = $uniqueClients->groupBy('first_time')->map(function ($group) {
                return [
                    'time' => $group->first()->first_time,
                    'male_count' => $group->where('gender', 'male')->count(),
                    'female_count' => $group->where('gender', 'female')->count(),
                    'client_count' => $group->count(),
                ];
            })->values();

        } elseif ($request->has(['start_date', 'end_date'])) {
            $clientQuery = $query->whereBetween('date', [$startDate, $endDate]);

            $uniqueClients = DB::table('client_attendances')
                ->whereBetween('date', [$startDate, $endDate])
                ->join('clients', 'client_attendances.clients_id', '=', 'clients.id')
                ->selectRaw('clients_id, clients.gender, MIN(CONCAT(HOUR(date), ":", LPAD(FLOOR(MINUTE(date) / 30) * 30, 2, "0"))) as first_time')
                ->groupBy('clients_id', 'clients.gender')
                ->get();

            $attendanceByTime = $uniqueClients->groupBy('first_time')->map(function ($group) {
                return [
                    'time' => $group->first()->first_time,
                    'male_count' => $group->where('gender', 'male')->count(),
                    'female_count' => $group->where('gender', 'female')->count(),
                    'client_count' => $group->count(),
                ];
            })->values();
            
        }
        else {
            $clientQuery = $query->whereDate('date', $day);

            $uniqueClients = DB::table('client_attendances')
                ->whereDate('date', $day)
                ->join('clients', 'client_attendances.clients_id', '=', 'clients.id')
                ->selectRaw('clients_id, clients.gender, MIN(CONCAT(HOUR(date), ":", LPAD(FLOOR(MINUTE(date) / 30) * 30, 2, "0"))) as first_time')
                ->groupBy('clients_id', 'clients.gender')
                ->get();

            $attendanceByTime = $uniqueClients->groupBy('first_time')->map(function ($group) {
                return [
                    'time' => $group->first()->first_time,
                    'male_count' => $group->where('gender', 'male')->count(),
                    'female_count' => $group->where('gender', 'female')->count(),
                    'client_count' => $group->count(),
                ];
            })->values();
        }

        $regularClients = $clientQuery->clone()->distinct('clients_id')->where('status', 'regular')->count();
        $newClients = $clientQuery->clone()->distinct('clients_id')->where('status', 'new')->count();

        $male = $clientQuery->clone()->whereHas('clients', function ($query) {
            $query->where('gender', 'male');
        });
        
        $female = $clientQuery->clone()->whereHas('clients', function ($query) {
            $query->where('gender', 'female');
        });

        $malesData = [
            'male_count' => $male->count(),
            'males_id' => $male->orderBy('clients_id')->pluck('clients_id')
        ];
        $femalesData = [
            'female_count' => $female->count(),
            'females_id' => $female->orderBy('clients_id')->pluck('clients_id')
        ];

        $clients = $clientQuery->with('clients')->get();

        $ageRanges = [
            '1-5' => [1, 5],
            '6-10' => [6, 10],
            '11-15' => [11, 15],
            '16-20' => [16, 20],
            '21-25' => [21, 25],
            '26-30' => [26, 30],
            '31-35' => [31, 35],
            '36-40' => [36, 40],
            '41-45' => [41, 45],
            '46-50' => [46, 50],
            '51-55' => [51, 55],
            '56-60' => [56, 60],
            '61-65' => [61, 65],
            '66-70' => [66, 70],
            '71-75' => [71, 75],
            '76-80' => [76, 80],
            '81-85' => [81, 85],
            '86+' => [86, 100]
        ];

        $orderedKeys = array_keys($ageRanges);

        $ageStatistics = collect($ageRanges)->mapWithKeys(function ($range) use ($ageRanges){
            return [array_search($range, $ageRanges) => 0];
        });

        $ageStatistics = $clients->unique('clients_id')->groupBy(function ($client) use ($ageRanges) {
            $age = $client->clients->age;

            foreach ($ageRanges as $range => [$min, $max]) {
                if ($age >= $min && $age <= $max) {
                    return $range;
                }
            }
            return 'неопределенный возраст!';
        })->map(function ($group) {
            return $group->count();
        })->union($ageStatistics)->sortKeys();

        $ageStatistics = $ageStatistics->sortBy(function ($value, $key) use ($orderedKeys) {
            return array_search($key, $orderedKeys);
        });

        $totalCount = $clients->unique('clients_id')->count();


        $malePercentage = $totalCount > 0 ? ($male->count() / $totalCount) * 100 : 0;
        $femalePercentage = $totalCount > 0 ? ($female->count() / $totalCount) * 100 : 0;

        return [
            'total_clients' => $totalCount,
            'regular_clients' => $regularClients,
            'new_clients' => $newClients,
            'male' => $malesData,
            'female' => $femalesData,
            'male_percentage' => $malePercentage,
            'female_percentage' => $femalePercentage,
            'age_statistics' => $ageStatistics,
            'peak_attendance' => $attendanceByTime,
        ];
    }
}
