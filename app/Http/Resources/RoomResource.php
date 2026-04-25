<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'room_id' => $this->room_id,
            'room_name' => $this->room_name,
            'capacity' => $this->capacity,
            'room_type_id' => $this->room_type_id,
            'room_type' => $this->whenLoaded('roomType', fn() => $this->roomType->name),
            'seat_layout_id' => $this->seat_layout_id,
            'seat_layout_name' => $this->whenLoaded('seatLayout', fn() => $this->seatLayout?->name),
            'status' => $this->status,
            'cinema' => $this->whenLoaded('cinema', fn() => $this->cinema->cinema_name),
        ];
    }
}
