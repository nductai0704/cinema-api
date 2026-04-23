<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CinemaResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->whenLoaded('role', function() {
                return $this->role->role_name;
            }),
            'cinema_id' => $this->cinema_id,
            'cinema' => new CinemaResource($this->whenLoaded('cinema')),
            'status' => $this->status,
        ];
    }
}
