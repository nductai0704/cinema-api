<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ShowtimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'showtime_id' => $this->showtime_id,
            'movie_id' => $this->movie_id,
            'movie_title' => $this->movie ? $this->movie->title : null,
            'start_time' => Carbon::parse($this->start_time)->format('H:i'),
            'end_time' => Carbon::parse($this->end_time)->format('H:i'),
            'ticket_price' => (float)$this->ticket_price,
            'price_standard' => (float)$this->price_standard,
            'price_vip' => (float)$this->price_vip,
            'price_double' => (float)$this->price_double,
            'status' => $this->display_status,
            'room_name' => $this->room ? $this->room->room_name : null,
            'room_type' => $this->roomType ? $this->roomType->name : ($this->room && $this->room->roomType ? $this->room->roomType->name : null),
        ];
    }
}
