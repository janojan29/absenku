<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly LeaveRequest $leaveRequest)
    {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('leave-requests');
    }

    public function broadcastAs(): string
    {
        return 'leave-request.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->leaveRequest->id,
            'date' => $this->leaveRequest->date->toDateString(),
            'type' => $this->leaveRequest->type,
            'status' => $this->leaveRequest->status,
            'user_id' => $this->leaveRequest->user_id,
        ];
    }
}
