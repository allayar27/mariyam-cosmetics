<?php

namespace App\Http\Resources\v1\Branch;

use App\Models\v1\Attendance;
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
        $day = request('day')?? Carbon::today()->toDateString();
        $all_comers = Attendance::whereDate('created_at', $day)
        ->where('type', 'in')
        ->whereHas('user', function($query) {
            $query->where('branch_id', $this->id);
        })
        ->count();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'workers_count' => $this->users()->count(),
            'all_comers' => $all_comers,
            'late_comers' => Attendance::whereDate('created_at', $day)
                                ->where('type', 'in')
                                ->whereHas('user', function($query) {
                                    $query->where('branch_id', $this->id);
                                })
                                ->whereHas('user.schedule', function($scheduleQuery) {
                                    $scheduleQuery->whereColumn('attendances.time', '>', 'schedules.time_in');
                                })
                                ->count(),
            'not_comers' => $this->users()->count() - $all_comers
        ];
    }
}