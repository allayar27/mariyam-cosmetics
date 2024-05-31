<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\v1\User;
use App\Models\v1\Attendance;
use App\Models\v1\Work_Days;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AttendanceControl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $attendance;
    public function __construct($attendance)
    {
        $this-> attendance = $attendance;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateWorkDay($this->attendance);
        $this->updateAttendanceTypes($this->attendance);
       
    }
    protected function updateAttendanceTypes($attendance)
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
        $this->updateLateWorkers($attendance,User::findOrFail($user_id));

        if ($todayAttendances->count() > 1) {
            $lastAttendance = $todayAttendances->last();
            $lastAttendance->update(['type' => 'out']);

            foreach ($todayAttendances->slice(1, -1) as $attendance) {
                $attendance->update(['type' => 'none']);
            }
        }
    }

    protected function updateLateWorkers(Attendance $attendance,$user)
    {
        // $user = User::find($attendance->user_id);
        if (!$user) {
            return;
        }
        $actualArrivalTime = Carbon::parse($attendance->time);
        $scheduledArrivalTime = Carbon::parse($user->schedule->time_in);

        if (!$actualArrivalTime->lessThanOrEqualTo($scheduledArrivalTime)) {
            $workDay = Work_Days::firstOrCreate(['work_day' => today(), 'branch_id' => $user->branch_id]);
            $workDay->increment('late_workers');
        }
    }

    protected function updateWorkDay(Attendance  $attendance)
    {
        $user = User::find($attendance->user_id);
        if (!$user) {
            return;
        }
        $workDay = Work_Days::firstOrCreate(['work_day' => today(),
                     'branch_id' => $user->branch_id]);
        $workDay->update([
            'workers_count' => $workDay->attendances()->where('type', 'in')->where('branch_id',$user->branch_id)->count()
        ]);

        $this->checkWorkDayType($workDay);
    }

    protected function checkWorkDayType($workDay)
    {
        $totalWorkers = User::where('branch_id',$workDay->branch_id)->count();
        $workersToday = $workDay->attendances()->where('type', 'in')->where('branch_id',$workDay->branch_id)->count();
        $percent = $workersToday * 100 / $totalWorkers;

        if ($percent > 20) {
            $workDay->update(['type' => 'work_day']);
        }else{
            $workDay->update(['type' => 'none']);
        }
    }
}
