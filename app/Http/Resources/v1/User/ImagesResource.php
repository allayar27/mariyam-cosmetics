<?php

namespace App\Http\Resources\v1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImagesResource extends JsonResource
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
            'url' => $this->getImageUrls()
        ];
    }

    protected function getImageUrls()
    {
        return url("storage/" . $this->path . $this->name);
    }
}
