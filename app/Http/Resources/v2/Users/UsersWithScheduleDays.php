<?php

namespace App\Http\Resources\v2\Users;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources\v1\User\ImagesResource;
use App\Http\Resources\v2\Schedule\DaysResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersWithScheduleDays extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $day = request('day') ?? Carbon::now();
        return [
            'id' =>$this->id,
            'name' => $this->name,
            'position' => [
                'id' => $this->position->id,
                'name' => $this->position->name,
            ],
            'branch' => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ],
            'phone' => $this->phone,
            'schedule' => [
                'id' => $this->schedule->id,
                'name' => $this->schedule->name,
                'days' => DaysResource::collection($this->schedule->days)
            ],
            'images' => ImagesResource::collection($this->images),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
