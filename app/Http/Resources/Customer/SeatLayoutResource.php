<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatLayoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'seat_id' => $this->seat_id,
            'row_label' => $this->row_label,
            'seat_number' => $this->seat_number,
            'seat_type' => $this->seat_type,
            'grid_x' => $this->grid_x,
            'grid_y' => $this->grid_y,
            'pair_uuid' => $this->pair_uuid,
            'is_available' => !($this->is_sold ?? false) && !($this->is_held ?? false),
            'is_sold' => (bool)($this->is_sold ?? false),
            'is_held' => (bool)($this->is_held ?? false),
            'status' => $this->status, // active/inactive
        ];
    }
}
