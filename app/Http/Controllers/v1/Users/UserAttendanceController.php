<?php

namespace App\Http\Controllers\v1\Users;

use Carbon\Carbon;
use App\Models\v1\User;
use App\Models\v1\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\DailyAllUsersCountResource;
use App\Http\Resources\v1\User\LastAttendancesResource;
use App\Http\Resources\v1\User\UserControlResource;
use App\Http\Resources\v1\User\UsersAttendanceResource;
use App\Models\v1\Attendance;
use App\Models\v1\Work_Days;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserAttendanceController extends Controller
{

    //for all statistika  //2
    public function all()
    {
        $id = request('id');
        $users = $id ? Branch::findOrFail($id)->users()->get() : User::all();
        return response()->json([
            'success' => true,
            'data' => UsersAttendanceResource::collection($users)
        ]);
    }

    //for user control//5
    public function allWithAttendance()
    {
        $id = request('id');
        $perPage = request('per_page') ?? 10;
        $usersQuery = $id ? Branch::findOrFail($id)->users() : User::query();
        $users = $usersQuery->paginate($perPage);
        $collection = [
            'users' => []
        ];
        foreach ($users as $user) {
            $collection['users'][] = new UserControlResource($user);
        }
        return response()->json([
            'success' => true,
            'total' => $users->count(),
            'data' => $collection['users'],
        ]);
    }

    //daily for home page //1
    public function daily()
    {
        $id = request('id');
        $day = request('day') ?? Carbon::today();
        $usersQuery = $id ? Branch::findOrFail($id)->users() : User::query();
        $allUsersCount = $usersQuery->count();
        $attendancesQuery = Attendance::query();
        if ($id) {
            $attendancesQuery->whereHas('user', function ($query) use ($id) {
                $query->where('branch_id', $id);
            });
        }
        $attendances = $attendancesQuery->whereDate('day', $day)->where('type', 'in')->get();
        $allComersCount = $attendances->count();
        $lateComersCount = $attendances->filter(function ($attendance) {
            return $attendance->user->schedule->time_in->gt($attendance->time);
        })->count();
        $notComersCount = $allUsersCount - $allComersCount;
        return [
            'allUsers' => $allUsersCount,
            'allComers' => $allComersCount,
            'lateComers' => $lateComersCount,
            'notComers' => $notComersCount,
        ];
    }
    //last comers//3
    public function lastAttendances()
    {
        $id = request('id');
        $day = request('day') ?? Carbon::today();
        $attendances = Attendance::whereDate('created_at', $day)->latest()->paginate(request('per_page', 10));
        $collection = [
            'attendances' => []
        ];
        foreach ($attendances as $attendance) {
            $collection['attendances'][] = new LastAttendancesResource($attendance);
        }
        return response()->json([
            'success' => true,
            'total' => $attendances->count(),
            'data' => $collection['attendances'],
        ]);
    }

    //daily statistika//4
    public function dailyAllUsersGraph(Request $request)
    {
        $branchId = $request->input('id');
        $month = $request->input('month') ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $query = DB::table('work__days')
            ->where('type', 'work_day')
            ->where('work_day', '>=', $startDate)
            ->where('work_day', '<=', $endDate);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        $dailyData = $query->select(
            'work_day',
            DB::raw('SUM(total_workers) AS total_workers'),
            DB::raw('SUM(workers_count) AS workers_count'),
            DB::raw('SUM(late_workers) AS late_workers')
        )
            ->groupBy('work_day')
            ->orderBy('work_day')
            ->get();

        $result = $dailyData->map(function ($day) {
            return [
                'day' => $day->work_day,
                'total_workers' => $day->total_workers,
                'workers_count' => $day->workers_count,
                'late_workers' => $day->late_workers,
            ];
        });
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    //about user//6
    public function aboutUser($id, Request $request)
    {
        $user = User::findOrFail($id);
        $month = $request->input('month') ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $dates = DB::table('work__days')->whereBetween('work_day', [$startDate, $endDate])
            ->where('type', 'work_day')
            ->pluck('work_day')
            ->unique()
            ->values();

        $data = $dates->map(function ($date) use ($user) {
            $in = Attendance::whereDate('created_at', $date)->where('type', 'in')->first();
            $out = Attendance::whereDate('created_at', $date)->where('type', 'out')->first();
            if ($in && $in->time > $user->schedule->time_in) {
                $lateTime = $in->time->diff($user->schedule->time_in);
                $attendanceData['late'] = $lateTime->format('%H:%I');
            }

            if ($out && $out->time < $user->schedule->time_out) {
                $earlyTime = $user->schedule->time_out->diff($out->time);
                $attendanceData['early'] = $earlyTime->format('%H:%I');
            }

            return [
                'day' => $date,
                'in' => $in ? $in->time->format('h-i-s') : null,
                'out' => $out ? $out->time->format('h-i-s') : null,
                'late' => isset($attendanceData['late']) ? $attendanceData['late'] : null,
                'early' => isset($attendanceData['early']) ? $attendanceData['early'] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserControlResource($user),
                'dates' => $data,
            ]
        ]);
    }

    //About user v2//6
    public function about($id, Request $request)
    {
        $user = User::findOrFail($id);
        $month = $request->input('month') ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $dates = DB::table('work__days')->whereBetween('work_day', [$startDate, $endDate])
            ->where('type', 'work_day')
            ->pluck('work_day');

        $data = [];
        foreach ($dates as $date) {
            $attendanceData = [];
            $in = Attendance::whereDate('created_at', $date)->where('type', 'in')->first();
            $out = Attendance::whereDate('created_at', $date)->where('type', 'out')->first();

            if ($in && $in->time > $user->schedule->time_in) {
                $lateTime = $in->time->diff($user->schedule->time_in);
                $attendanceData['late'] = $lateTime->format('%H:%I');
            }

            if ($out && $out->time < $user->schedule->time_out) {
                $earlyTime = $user->schedule->time_out->diff($out->time);
                $attendanceData['early'] = $earlyTime->format('%H:%I');
            }

            $data[] = [
                'day' => $date,
                'in' => $in ? $in->time->format('h-i-s') : null,
                'out' => $out ? $out->time->format('h-i-s') : null,
                'late' => $attendanceData['late'] ?? null,
                'early' => $attendanceData['early'] ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserControlResource($user),
                'dates' => $data,
            ]
        ]);
    }


    //for late comers//9
    public function lateComersWithDetails(Request $request)
    {
        $branchId = $request->input('id');
        $today = $request->input('day') ?? Carbon::today()->toDateString();
        $perPage = $request->input('per_page', 10);
        $usersQuery = $branchId ? Branch::findOrFail($branchId)->users() : User::query();

        $users = $usersQuery->with(['attendance' => function ($query) use ($today) {
            $query->where('type', 'in')->whereDate('created_at', $today);
        }, 'schedule', 'position'])->get();
        $latecomers = $users->filter(function ($user) {
            $attendance = $user->attendance->first();
            return $attendance && $attendance->time > $user->schedule->time_in;
        })->map(function ($user) {
            $attendance = $user->attendance->first();
            $lateTime = Carbon::parse($attendance->time)->diff(Carbon::parse($user->schedule->time_in));
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $user->position->name,
                'time_in' => $user->schedule->time_in->format('H:i'),
                'late_by_time' => $lateTime->format('%H:%I'),
                'attendance_time' => $attendance->time->format('H:i:s'),
            ];
        })->values();
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginatedLatecomers = new LengthAwarePaginator(
            $latecomers->forPage($currentPage, $perPage),
            $latecomers->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return response()->json([
            'success' => true,
            'current_page' => $paginatedLatecomers->currentPage(),
            'last_page' => $paginatedLatecomers->lastPage(),
            'per_page' => $perPage,
            'total' => $paginatedLatecomers->total(),
            'data' => $paginatedLatecomers->items(),
        ]);
    }


    //note comers//10
    public function noteComers(Request $request)
    {
        $branchId = $request->input('id');
        $today = $request->input('day') ?? Carbon::today()->toDateString();
        $perPage = $request->input('per_page', 10);
        $usersQuery = $branchId ? Branch::findOrFail($branchId)->users() : User::query();
        $todayAttendances = Attendance::whereDate('day', $today)->pluck('user_id')->toArray();
        $absentUsers = $usersQuery->whereNotIn('id', $todayAttendances)->get();
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $absentUsersCollection = collect($absentUsers);
        $paginatedAbsentUsers = new LengthAwarePaginator(
            $absentUsersCollection->forPage($currentPage, $perPage),
            $absentUsersCollection->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return response()->json([
            'success' => true,
            'current_page' => $paginatedAbsentUsers->currentPage(),
            'last_page' => $paginatedAbsentUsers->lastPage(),
            'per_page' => $perPage,
            'total' => $paginatedAbsentUsers->total(),
            'data' => UserControlResource::collection($paginatedAbsentUsers->items())
        ]);
    }

    public function monthly(Request $request)
    {
        $month = $request->input('month') ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $perPage = $request->input('per_page', 10);
        $branches = Branch::paginate($perPage);
        $branchIds = $branches->pluck('id');
        $workDays = DB::table('work__days')
            ->whereBetween('work_day', [$startDate, $endDate])
            ->where('type', 'work_day')
            ->whereIn('branch_id', $branchIds)
            ->select(
                'branch_id',
                DB::raw('SUM(workers_count) as on_time_total'),
                DB::raw('SUM(late_workers) as late_total'),
                DB::raw('SUM(total_workers) as total_workers')
            )
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $collection = [
            'lastPage' => $branches->lastPage(),
            'currentPage' => $branches->currentPage(),
            'perPage' => $branches->perPage(),
            'total' => $branches->total(),
            'branches' => []
        ];
        foreach ($branches as $branch) {
            $branchData = $workDays->get($branch->id, (object)[
                'on_time_total' => 0,
                'late_total' => 0,
                'total_workers' => 0
            ]);

            $totalWorkers = $branchData->total_workers ?: 1; // Avoid division by zero
            $onTimePercentage = ($branchData->on_time_total / $totalWorkers) * 100;
            $latePercentage = ($branchData->late_total / $totalWorkers) * 100;

            $collection['branches'][] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'al_comers' => $onTimePercentage,
                'late_percentage' => $latePercentage
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $collection
        ]);
    }
}
