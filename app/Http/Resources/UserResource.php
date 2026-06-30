<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = $this->whenLoaded('roles', fn() => $this->roles->pluck('name')->values());
        $roleName = $this->whenLoaded('roles', fn() => $this->roles->pluck('name')->first());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'whatsapp_number' => $this->whatsapp_number,
            'roles' => $roles,
            'role_name' => $roleName,
            'has_default_password' => $this->hasDefaultPassword(),
            'student_profile' => $this->whenLoaded('studentProfile', fn() => $this->studentProfile ? new StudentProfileResource($this->studentProfile) : null),
            'teacher' => $this->whenLoaded('teacher', fn() => $this->teacher ? new TeacherResource($this->teacher) : null),
        ];
    }
}
