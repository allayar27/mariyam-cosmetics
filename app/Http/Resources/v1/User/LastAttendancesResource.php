<?php

namespace App\Http\Resources\v1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LastAttendancesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'name' => $this->user->name,
            'position' => $this->user->position->name,
            'branch' => $this->user->branch->name,
            'time' => $this->time,
            'score' => $this->score,
            'user_image' => $this->getImageUrl(),
            'attendance_image' => $this->getImageUrls(),
        ];
    }

    protected function getImageUrls()
    {
        return $this->images->map(function ($image) {
            return url("storage/" . $image->path . $image->name);
        })->toArray();
    }
    protected function getImageUrl()
    {
        return $this->user->images->map(function ($image) {
            return url("storage/" . $image->path . $image->name);
        })->toArray();
    }
}
