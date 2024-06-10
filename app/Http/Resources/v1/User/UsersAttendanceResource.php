<?php

namespace App\Http\Resources\v1\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersAttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $day = request('day') ?? Carbon::today()->toDateString();
        $attendanceRecord = $this->attendance()->whereDate('day', $day)->where('type', 'in')->first();
        $lateTime = $attendanceRecord && $attendanceRecord->time > $this->schedule->time_in
            ? Carbon::parse($attendanceRecord->time)->diffInMinutes($this->schedule->time_in)
            : null;
        $lateTimeFormatted = $lateTime ? gmdate('H:i', $lateTime * 60) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => [
                'id' => $this->position->id,
                'name' => $this->position->name,
            ],
            'branch' => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ],
            'schedule' => [
                'time_in' => Carbon::parse($this->schedule->time_in)->format('H:i'),
                'time_out' => Carbon::parse($this->schedule->time_out)->format('H:i'),
            ],
            'phone' => $this->phone,
            'attendance' => [
                'come' => $attendanceRecord ? Carbon::parse($attendanceRecord->time)->format('H:i') : null,
                'late' => $lateTimeFormatted
            ],
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
