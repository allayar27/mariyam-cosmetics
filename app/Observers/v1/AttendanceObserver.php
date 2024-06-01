<?php

namespace App\Observers\v1;

use App\Models\v1\Attendance;
use App\Models\v1\Work_Days;
use App\Models\v1\User;
use Carbon\Carbon;

class AttendanceObserver
{
    public function creating(Attendance $attendance)
    {
        $attendance->day = Carbon::today();
        $attendance->type = 'in';
        $attendance->branch_id = $attendance->device->branch_id;
    }
    public function created(Attendance $attendance)
    {
        $this->updateAttendanceTypes($attendance);
        
    }
    protected function updateAttendanceTypes(Attendance $attendance)
    {
        $user_id = $attendance->user_id;
        $today = Carbon::today();
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

    protected function updateLateWorkers(Attendance $attendance, $user)
    {
        if (!$user) {
            return;
        }
        $actualArrivalTime = Carbon::parse($attendance->time);
        $scheduledArrivalTime = Carbon::parse($user->schedule->time_in);
        if ($attendance->type == 'in' && $actualArrivalTime->gt($scheduledArrivalTime)) {
            $workDay = Work_Days::firstOrCreate(
                ['work_day' => Carbon::today(), 'branch_id' => $user->branch_id],
                ['late_workers' => 0],
                ['total_workers' => $user->branch->users()->count()]
            );
            $workDay->late_workers += 1;
            $workDay->save();
        }
    }
    protected function updateWorkDay(Attendance $attendance)
    {
        $user = User::find($attendance->user_id);
        if (!$user) {
            return;
        }
        $workDay = Work_Days::firstOrCreate([
            'work_day' => Carbon::today(),
            'branch_id' => $user->branch_id,
            'total_workers' => $user->branch->users()->count(),
        ]);
        $workDay->update([
            'workers_count' => Attendance::where('type', 'in')->whereDate('day',Carbon::today())->where('branch_id', $user->branch_id)->count(),
            'late_workers' => Attendance::whereDate('created_at', Carbon::today())
            ->where('type', 'in')
            ->whereHas('user', function($query)  use ($user){
                $query->where('branch_id', $user->branch_id);
            })
            ->whereHas('user.schedule', function($scheduleQuery) {
                $scheduleQuery->whereColumn('attendances.time', '>', 'schedules.time_in');
            })
            ->count(),
        ]);
        $this->checkWorkDayType($workDay);
    }

    protected function checkWorkDayType($workDay)
    {
        $totalWorkers = User::count();
        $workersToday = Attendance::whereDate('day', Carbon::today())->where('type', 'in')->count();
        $percent = $workersToday * 100 / $totalWorkers;

        if ($percent > 20) {
            $workDay->update(['type' => 'work_day']);
        } else {
            $workDay->update(['type' => 'none']);
        }
    }

    
}
