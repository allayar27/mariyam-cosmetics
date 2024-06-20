<?php

namespace App\Http\Resources\v1\Position;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionsResource extends JsonResource
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
            'users' => $this->users()->count()
        ];
    }
}
