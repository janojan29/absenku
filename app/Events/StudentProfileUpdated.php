<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentProfileUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $previousClassRoomId,
        public readonly ?int $classRoomId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $classRoomIds = array_filter([
            $this->previousClassRoomId,
            $this->classRoomId,
        ]);

        return collect($classRoomIds)
            ->unique()
            ->values()
            ->map(fn($classRoomId) => new PrivateChannel('classroom.' . $classRoomId))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'student-profile.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'previous_class_room_id' => $this->previousClassRoomId,
            'class_room_id' => $this->classRoomId,
        ];
    }
}
