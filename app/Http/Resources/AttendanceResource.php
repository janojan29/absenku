<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => optional($this->date)->toDateString(),
            'status' => $this->status,
            'late_minutes' => $this->late_minutes,
            'check_in_at' => optional($this->check_in_at)->toDateTimeString(),
            'check_out_at' => optional($this->check_out_at)->toDateTimeString(),
            'check_in_distance_meters' => $this->check_in_distance_meters,
            'check_out_distance_meters' => $this->check_out_distance_meters,
        ];
    }
}
