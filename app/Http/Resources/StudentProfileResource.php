<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nis' => $this->nis,
            'jurusan' => $this->jurusan,
            'class_room_id' => $this->class_room_id,
            'class_room' => $this->whenLoaded('classRoom', fn() => new ClassRoomResource($this->classRoom)),
            'parent_phone_wa' => $this->parent_phone_wa,
            'parent_whatsapp_number' => $this->parent_whatsapp_number,
            'photo' => $this->photo,
        ];
    }
}
