<?php

namespace App\Http\Resources\v1\Branch;

use App\Models\v1\Attendance;
use App\Models\v1\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $day = request('day') ?? Carbon::today()->toDateString();
        $currentUsers = User::getUsersByDateAndBranch($day, $this->id);
        $workers = User::getWorkersByDate($day, $this->id);
        $workerIds = $workers->pluck('id');
        $attendances = Attendance::whereDate('created_at', $day)
            ->where('type', 'in')
            ->whereIn('user_id', $workerIds)
            ->get();

        $allComers = $attendances->pluck('user_id');
        $lateComers = $attendances->filter(function ($attendance) use ($day) {
            $user = $attendance->user;
            return Carbon::parse($attendance->time)->gt(Carbon::parse($user->schedule->time_in($day)));
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'all_users' => $currentUsers->count(),
            'workers_count' => $workers->count(),
            'all_comers' => $allComers->count(),
            'late_comers' => $lateComers->count(),
            'not_comers' => $workers->count() - $allComers->count(),
        ];
    }
}
