<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Attendance $attendance,
        public readonly int $classRoomId,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('classroom.'.$this->classRoomId);
    }

    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->attendance->id,
            'user_id' => $this->attendance->user_id,
            'date' => $this->attendance->date->toDateString(),
            'status' => $this->attendance->status,
            'check_in_at' => optional($this->attendance->check_in_at)->toIso8601String(),
            'check_out_at' => optional($this->attendance->check_out_at)->toIso8601String(),
        ];
    }
}
