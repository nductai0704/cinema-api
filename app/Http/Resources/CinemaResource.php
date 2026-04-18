<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CinemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cinema_id' => $this->cinema_id,
            'cinema_name' => $this->cinema_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'status' => $this->status,
            'region' => new RegionResource($this->whenLoaded('region')),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'managers' => UserResource::collection($this->whenLoaded('users')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
