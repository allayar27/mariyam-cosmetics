<?php

namespace App\Http\Resources\v2\Schedule;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchedulesResource extends JsonResource
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
           'name' => $this->name,
           'users' => $this->users->count(),
           'days' => DaysResource::collection($this->days)
        ];
    }
}
