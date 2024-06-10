<?php

namespace App\Http\Controllers\v1\Users;

use Carbon\Carbon;
use App\Models\v1\User;
use App\Models\v1\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\LastAttendancesResource;
use App\Http\Resources\v1\User\UserControlResource;
use App\Http\Resources\v1\User\UsersAttendanceResource;
use App\Models\v1\Attendance;
use App\Models\v1\Position;
use App\Models\v1\Work_Days;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserAttendanceController extends Controller
{

    //for all statistika  //2
    public function all(Request $request)
    {
        $day = $request->input('day', Carbon::now());
        $id = $request->input('id');
        $users = User::getUsersByDateAndBranch($day, $id)->get();
        return response()->json([
            'success' => true,
            'total' => $users->count(),
            'data' => UsersAttendanceResource::collection($users)
        ]);
    }
    //for user control//5
    public function allWithAttendance(Request $request)
    {
        $id = $request->input('id');
        $position_id = $request->input('position_id');
        $perPage = $request->input('per_page', 10);
        $day = Carbon::now();
        $usersQuery = User::getUsersByDateAndBranch($day, $id);
        if ($id) {
            $branch = Branch::findOrFail($id);
            $usersQuery->where('branch_id', $branch->id);
        }
        if ($position_id) {
            $position = Position::findOrFail($position_id);
            $usersQuery->where('position_id', $position->id);
        }
        $users = $usersQuery->paginate($perPage);
        return response()->json([
            'success' => true,
            'total' => $users->total(),
            'data' => UserControlResource::collection($users),
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
            return $attendance->time > $attendance->user->schedule->time_in;
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
        $month = $request->input('month') ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $branches = Branch::all();
        $data = $branches->map(function ($branch) use ($startDate, $endDate) {
            $workDays = Work_Days::whereBetween('work_day', [$startDate, $endDate])
                ->where('branch_id', $branch->id)
                ->get();
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'work_days' => $workDays->map(function ($workDay) {
                    return [
                        'work_day' => $workDay->work_day,
                        'workers_count' => $workDay->workers_count,
                        'late_workers' => $workDay->late_workers,
                        'total_workers' => $workDay->total_workers,
                    ];
                })
            ];
        });
        return response()->json([
           'success' => true,
            'total' => $data->count(),
            'data' => $data,
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
                $lateTime = Carbon::parse($in->time)->diff(Carbon::parse($user->schedule->time_in));
                $attendanceData['late'] = $lateTime->format('%H:%I');
            }

            if ($in && $in->time > $user->schedule->time_in) {
                $lateTime = Carbon::parse($in->time)->diff(Carbon::parse($user->schedule->time_in));
                $attendanceData['late'] = $lateTime->format('%H:%I');
            }

            return [
                'day' => $date,
                'in' => $in ? $in->time : null,
                'out' => $out ? $out->time : null,
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
        $usersQuery = User::getUsersByDateAndBranch($today, $branchId);
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
                'time_in' => $user->schedule->time_in,
                'late_by_time' => $lateTime->format('%H:%I'),
                'attendance_time' => $attendance->time,
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
        $today = $request->input('day') ?? Carbon::now();
        $perPage = $request->input('per_page', 10);
        $todayAttendances = Attendance::whereDate('day', $today)->pluck('user_id')->toArray();
        $absentUsers = User::getUsersByDateAndBranch($today, $branchId)->whereNotIn('id', $todayAttendances)->paginate($perPage);
        return response()->json([
            'success' => true,
            'current_page' => $absentUsers->currentPage(),
            'last_page' => $absentUsers->lastPage(),
            'per_page' => $perPage,
            'total' => $absentUsers->total(),
            'data' => UserControlResource::collection($absentUsers)
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

            $totalWorkers = $branchData->total_workers ?: 1;
            $onTimePercentage = ($branchData->on_time_total / $totalWorkers) * 100;
            $latePercentage = ($branchData->late_total / $totalWorkers) * 100;
            $notcomers = $totalWorkers - ($branchData->on_time_total);
            $notcomersPercentage = ($notcomers / $totalWorkers) * 100;
            $collection['branches'][] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'al_comers' => $onTimePercentage,
                'late_percentage' => $latePercentage,
                'not_comers' => $notcomersPercentage,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $collection
        ]);
    }

    public function comers(Request $request)
    {
        $branchId = $request->input('id');
        $today = $request->input('day') ?? Carbon::now();
        $perPage = $request->input('per_page', 10);

        $usersQuery = User::getUsersByDateAndBranch($today, $branchId);

        $users = $usersQuery->with(['attendance' => function ($query) use ($today) {
            $query->where('type', 'in')->whereDate('day', $today);
        }])
            ->whereHas('attendance', function ($query) use ($today) {
                $query->where('type', 'in')->whereDate('created_at', $today);
            })
            ->paginate($perPage);

        return response()->json([
            'uccess' => true,
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'last_page' => $users->lastPage(),
            'data' => UsersAttendanceResource::collection($users->items())
        ]);
    }
}
