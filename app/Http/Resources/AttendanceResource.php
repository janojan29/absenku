<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $setting = \App\Models\SchoolSetting::singleton();
        $date = \Illuminate\Support\Carbon::parse($this->date);
        $checkOutEnd = \Illuminate\Support\Carbon::today()->setTimeFromTimeString($setting->check_out_end_time);
        $isMissingCheckout = $this->check_in_at !== null && $this->check_out_at === null;
        $isPastOrEnded = $date->lt(\Illuminate\Support\Carbon::today()) || (now()->greaterThan($checkOutEnd));
        
        $status = ($isMissingCheckout && $isPastOrEnded) ? 'absent' : $this->status;

        $leaveForDate = \App\Models\LeaveRequest::query()
            ->where('user_id', $this->user_id)
            ->whereDate('date', $date)
            ->first();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => optional($this->date)->toDateString(),
            'status' => $status,
            'late_minutes' => $this->late_minutes,
            'check_in_at' => optional($this->check_in_at)->toDateTimeString(),
            'check_out_at' => optional($this->check_out_at)->toDateTimeString(),
            'check_in_distance_meters' => $this->check_in_distance_meters,
            'check_out_distance_meters' => $this->check_out_distance_meters,
            'leave_request' => $leaveForDate ? [
                'type' => $leaveForDate->type,
                'reason' => $leaveForDate->reason,
                'keterangan' => $leaveForDate->keterangan,
                'status' => $leaveForDate->status,
            ] : null,
        ];
    }
}
