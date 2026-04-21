<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'region_id' => $this->region_id,
            'city' => $this->city,
            'district' => $this->district,
            'full_location' => "{$this->district}, {$this->city}",
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
