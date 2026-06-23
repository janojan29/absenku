<?php

namespace App\Http\Requests\LeaveRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::today()->addDay()->toDateString();

        return [
            'type' => ['required', 'string', 'in:absent,early_leave'],
            'reason' => ['required', 'string', 'in:urgent,sick'],
            'keterangan' => ['required', 'string', 'min:5', 'max:2000'],
            'leave_date' => ['nullable', 'date', 'required_if:type,absent', Rule::in([$today, $tomorrow])],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_date.required_if' => 'Tanggal izin wajib diisi untuk izin tidak masuk.',
            'leave_date.in' => 'Tanggal izin tidak masuk hanya boleh hari ini atau besok.',
        ];
    }
}
