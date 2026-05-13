<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => optional($this->date)->toDateString(),
            'type' => $this->type,
            'reason' => $this->reason,
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'decided_by' => $this->decided_by,
            'decided_at' => optional($this->decided_at)->toDateTimeString(),
            'decision_note' => $this->decision_note,
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'decided_by_user' => $this->whenLoaded('decidedBy', fn() => new UserResource($this->decidedBy)),
        ];
    }
}
