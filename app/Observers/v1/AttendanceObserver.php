<?php

namespace App\Observers\v1;

use App\Models\v1\Attendance;
use App\Models\v1\Work_Days;
use App\Models\v1\User;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AttendanceObserver
{
    private $today;
    private $time;
    public function __construct()
    {
        $this->today = request('day') ?? Carbon::today()->format('Y-m-d');
        $this->time = request('time');
    }
    
    public function creating(Attendance $attendance)
    {
        $attendance->day = $this->today;
        $attendance->type = 'in';
        $attendance->branch_id = $attendance->device->branch_id;
        $attendance->created_at = $this->today . ' ' . $this->time;
    }

    public function created(Attendance $attendance)
    {
        $this->updateAttendanceTypes($attendance);
    }

    protected function updateAttendanceTypes(Attendance $attendance)
    {
        $user_id = $attendance->user_id;
        $today = $this->today;
        $todayAttendances = Attendance::where('user_id', $user_id)
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'asc')
            ->get();
        if ($todayAttendances->isEmpty()) {
            return;
        }
        $firstAttendance = $todayAttendances->first();
        $firstAttendance->update(['type' => 'in']);
        if ($todayAttendances->count() > 1) {
            $lastAttendance = $todayAttendances->last();
            $lastAttendance->update(['type' => 'out']);
            foreach ($todayAttendances->slice(1, -1) as $attendance) {
                $attendance->update(['type' => 'none']);
            }
        }
        $this->updateWorkDay($attendance);
    }
    protected function updateWorkDay(Attendance $attendance)
    {
        $user = User::find($attendance->user_id);
        if (!$user) {
            return;
        }
        $day = $this->today;
        $workDay = Work_Days::updateOrCreate(
            [
                'work_day' => Carbon::today(),
                'branch_id' => $user->branch_id,
            ],
            [
                'total_workers' => $user->branch->users()->count(),
            ]
        );
        $attendances = Attendance::with('user.schedule')
            ->where('type', 'in')
            ->whereDate('day', Carbon::today())
            ->where('branch_id', $user->branch_id)
            ->get();

        $lateComersCount = $attendances->filter(function ($attendance) use ($day) {
            if ($attendance->user && $attendance->user->schedule) {
                $isLate = $attendance->time > $attendance->user->schedule->time_in($day);
                if ($isLate) {
                    return true;
                }
            } else {
                return false;
            }
        })->count();
        $workDay->update([
            'workers_count' => Attendance::where('type', 'in')->whereDate('day', Carbon::today())->where('branch_id', $user->branch_id)->count(),
            'late_workers' => $lateComersCount
        ]);

        $this->checkWorkDayType($workDay);
    }

    protected function checkWorkDayType($workDay)
    {
        $id = null;
        $totalWorkers = User::getWorkersByDate($workDay->work_day, $id)->count();
        $workersToday = Attendance::whereDate('day', Carbon::today())->where('type', 'in')->count();
        $percent = $workersToday * 100 / $totalWorkers;

        if ($percent > 20) {
            $workDay->update(['type' => 'work_day']);
        } else {
            $workDay->update(['type' => 'none']);
        }
    }
}
