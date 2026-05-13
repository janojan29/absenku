<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_meters' => $this->radius_meters,
            'check_in_start_time' => $this->check_in_start_time,
            'check_in_end_time' => $this->check_in_end_time,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'check_out_start_time' => $this->check_out_start_time,
            'check_out_end_time' => $this->check_out_end_time,
        ];
    }
}
