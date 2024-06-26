<?php

namespace App\Http\Controllers\v1\Users;

use Carbon\Carbon;
use App\Models\v1\User;
use App\Models\v1\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\ImagesResource;
use App\Http\Resources\v1\User\LastAttendancesResource;
use App\Http\Resources\v1\User\UserControlResource;
use App\Http\Resources\v1\User\UsersAttendanceResource;
use App\Http\Resources\v2\Schedule\DaysResource;
use App\Http\Resources\v2\Users\UsersWithScheduleDays;
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
        $users = User::getWorkersByDate($day, $id)->get();
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
        if ($position_id) {
            $position = Position::findOrFail($position_id);
            $usersQuery->where('position_id', $position->id);
        }
        $users = $usersQuery->paginate($perPage);
        return response()->json([
            'success' => true,
            'total' => $users->total(),
            'data' => UsersWithScheduleDays::collection($users),
        ]);
    }

    //daily for home page //1
    public function daily()
    {
        $id = request('id');
        $day = request('day') ?? Carbon::today()->toDateString();
        $usersQuery = User::getWorkersByDate($day, $id);
        $allUsersCount = $usersQuery->count();
        $attendancesQuery = Attendance::query();
        if ($id) {
            $attendancesQuery->whereHas('user', function ($query) use ($id) {
                $query->where('branch_id', $id);
            });
        }
        $attendances = $attendancesQuery->whereDate('day', $day)->where('type', 'in')->whereIn('user_id',$usersQuery->pluck('id'))->get();
        $allComersCount = $attendances->count();
        return $allComersCount;
        $lateComersCount = $attendances->filter(function ($attendance) use ($day) {
            return $attendance->time > $attendance->user->schedule->time_in($day);
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
        $attendances = request('id')
            ? Attendance::where('branch_id', request('id'))->whereDate('day', request('day', Carbon::today()))->latest()
            : Attendance::whereDate('created_at', request('day', Carbon::today()))->latest();

        $attendances = $attendances->paginate(request('per_page', 10));

        return response()->json([
            'success' => true,
            'total' => $attendances->count(),
            'data' => LastAttendancesResource::collection($attendances),
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
                ->where('branch_id', $branch->id)->where('type', 'work_day')
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

    //About user v2//6
    public function about($id, Request $request)
    {
        $user = User::findOrFail($id);
        $month = $request->input('month') ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $dates = DB::table('work__days')->whereBetween('work_day', [$startDate, $endDate])
            ->where('type', 'work_day')->orderBy('work_day','desc')
            ->pluck('work_day')
            ->unique()
            ->values();
        $data = [];
        foreach ($dates as $date) {
            $in = Attendance::where('user_id', $user->id)->where('type', 'in')->where('day', $date)->first();
            $out = Attendance::where('user_id', $user->id)->where('type', 'out')->where('day', $date)->first();
            if ($in && $in->time > $user->schedule->time_in($date)) {
                $lateTime = Carbon::parse($in->time)->diff(Carbon::parse($user->schedule->time_in($date)));
                $attendanceData['late'] = $lateTime->format('%H:%I');
            }

            if ($out && $out->time < $user->schedule->time_out($date)) {
                $lateTime = Carbon::parse($out->time)->diff(Carbon::parse($user->schedule->time_out($date)));
                $attendanceData['early'] = $lateTime->format('%H:%I');
            }
            if ($in  or $out) {
                $data[] = [
                    'day' => $date,
                    'in' => $in ? $in->time : null,
                    'out' => $out ? $out->time : null,
                    'late' => isset($attendanceData['late']) ? $attendanceData['late'] : null,
                    'early' => isset($attendanceData['early']) ? $attendanceData['early'] : null,
                    'in_images' => $in ? ImagesResource::collection($in->images) : null,
                    'out_images' => $out ? ImagesResource::collection($out->images) : null
                ];
            }
        }
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UsersWithScheduleDays($user),
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
        $usersQuery = User::getWorkersByDate($today, $branchId);
        $users = $usersQuery->with(['attendance' => function ($query) use ($today) {
            $query->where('type', 'in')->whereDate('created_at', $today);
        }, 'schedule', 'position'])->get();
        $latecomers = $users->filter(function ($user) use ($today) {
            $attendance = $user->attendance->first();
            return $attendance && $attendance->time > $user->schedule->time_in($today);
        })->map(function ($user) use ($today) {
            $attendance = $user->attendance->first();
            $lateTime = Carbon::parse($attendance->time)->diff(Carbon::parse($user->schedule->time_in($today)));
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $user->position->name,
                'time_in' => Carbon::parse($user->schedule->time_in($today))->format('H:i'),
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
        $absentUsers = User::getWorkersByDate($today, $branchId)->whereNotIn('id', $todayAttendances)->paginate($perPage);
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
        $usersQuery = User::getWorkersByDate($today, $branchId);
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

    public function month(Request $request)
    {
        $month = $request->input('month') ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $perPage = $request->input('per_page', 10);

        $workDays = DB::table('work__days')
            ->whereBetween('work_day', [$startDate, $endDate])
            ->where('type', 'work_day')
            ->select('work_day', 'branch_id', 'workers_count', 'late_workers', 'total_workers')
            ->get();

        $collection = [];
        foreach ($workDays as $day) {
            $collection[$day->work_day]['branches'][] = [
                'branch_id' => $day->branch_id,
                'branch' => Branch::find($day->branch_id)->name,
                'total_workers' => $day->total_workers,
                'workers_count' => $day->workers_count,
                'late_workers' => $day->late_workers,
            ];
        }

        return response()->json([
            'uccess' => true,
            'data' => $collection
        ]);
    }

    public function usersbyschedule(Request $request){
        $id = $request->input('id') ?? null;
        $day = $request->input('day') ?? Carbon::today();
        $users = User::getWorkersByDate($day, $id)->get();
        return response()->json([
            'success' => true,
            'total' => $users->count(),
            'users' => UserControlResource::collection($users)
        ]);
    }
}
