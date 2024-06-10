<?php

namespace App\Http\Resources\v1\User;

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
                'time_in' => $this->schedule->time_in,
                'time_out' => $this->schedule->time_out
            ],
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
