<?php

namespace App\Http\Resources\v2\Schedule;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DaysResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day' => $this->type,
            'time_in' => $this->time_in,
            'time_out' => $this->time_out,
            'is_work_day' => $this->is_work_day,
        ];
    }
}
