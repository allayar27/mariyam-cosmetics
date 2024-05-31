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
            'position' => $this->position->name,
            'branch' => $this->branch->name,
            'schedule' => [
                'time_in' => $this->schedule->time_in->format('h:i:s'),
                'time_out' => $this->schedule->time_out->format('h:i:s'),
            ],
        ];
    }
}
