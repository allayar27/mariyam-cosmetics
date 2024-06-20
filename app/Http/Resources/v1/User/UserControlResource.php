<?php

namespace App\Http\Resources\v1\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserControlResource extends JsonResource
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
                'time_in' => Carbon::parse($this->schedule->time_in($day))->format('H:i'),
                'time_out' => Carbon::parse($this->schedule->time_out($day))->format('H:i')
            ],
            'images' => ImagesResource::collection($this->images),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
