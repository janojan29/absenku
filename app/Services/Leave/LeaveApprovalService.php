<?php

namespace App\Services\Leave;

use App\Events\LeaveRequestUpdated;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveApprovalService
{
    public function approve(User $actor, LeaveRequest $leaveRequest, ?string $decisionNote): string
    {
        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'approval' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        DB::transaction(function () use ($actor, $leaveRequest, $decisionNote) {
            $leaveRequest->update([
                'status' => 'approved',
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_note' => $decisionNote,
            ]);

            if ($leaveRequest->type === 'absent' && !\App\Helpers\HolidayHelper::isHoliday($leaveRequest->date)) {
                $statusToSet = $leaveRequest->reason === 'sick' ? 'sick' : 'leave';
                Attendance::query()->updateOrCreate(
                    ['user_id' => $leaveRequest->user_id, 'date' => $leaveRequest->date->toDateString()],
                    ['status' => $statusToSet],
                );
            }

            event(new LeaveRequestUpdated($leaveRequest));

            $user = $leaveRequest->user()->with('studentProfile')->first();
            $message = 'Pengajuan izin kamu untuk tanggal ' . $leaveRequest->date->format('d/m/Y') . ' telah DISETUJUI.';
            if (!empty($decisionNote)) {
                $message .= "\n\nCatatan: " . $decisionNote;
            }

            $cleanNumber = function ($number) {
                if (empty($number)) return null;
                $num = preg_replace('/[^0-9]/', '', $number);
                if (str_starts_with($num, '62')) return substr($num, 2);
                if (str_starts_with($num, '0')) return substr($num, 1);
                return $num;
            };

            $sentCleaned = [];

            if (! empty($user?->whatsapp_number)) {
                SendWhatsAppMessage::dispatch(
                    to: $user->whatsapp_number,
                    message: $message,
                    relatedType: LeaveRequest::class,
                    relatedId: $leaveRequest->id,
                );
                $sentCleaned[] = $cleanNumber($user->whatsapp_number);
            }

            $parentWa = $user?->studentProfile?->parent_phone_wa ?: $user?->studentProfile?->parent_whatsapp_number;
            if (! empty($parentWa) && !in_array($cleanNumber($parentWa), $sentCleaned)) {
                SendWhatsAppMessage::dispatch(
                    to: $parentWa,
                    message: $message,
                    relatedType: LeaveRequest::class,
                    relatedId: $leaveRequest->id,
                );
            }
        });

        return 'Pengajuan disetujui.';
    }

    public function reject(User $actor, LeaveRequest $leaveRequest, ?string $decisionNote): string
    {
        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'approval' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        DB::transaction(function () use ($actor, $leaveRequest, $decisionNote) {
            $leaveRequest->update([
                'status' => 'rejected',
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_note' => $decisionNote,
            ]);

            event(new LeaveRequestUpdated($leaveRequest));

            $user = $leaveRequest->user()->with('studentProfile')->first();
            $message = 'Pengajuan izin kamu untuk tanggal ' . $leaveRequest->date->format('d/m/Y') . ' telah DITOLAK.';
            if (!empty($decisionNote)) {
                $message .= "\n\nAlasan: " . $decisionNote;
            }

            $cleanNumber = function ($number) {
                if (empty($number)) return null;
                $num = preg_replace('/[^0-9]/', '', $number);
                if (str_starts_with($num, '62')) return substr($num, 2);
                if (str_starts_with($num, '0')) return substr($num, 1);
                return $num;
            };

            $sentCleaned = [];

            if (! empty($user?->whatsapp_number)) {
                SendWhatsAppMessage::dispatch(
                    to: $user->whatsapp_number,
                    message: $message,
                    relatedType: LeaveRequest::class,
                    relatedId: $leaveRequest->id,
                );
                $sentCleaned[] = $cleanNumber($user->whatsapp_number);
            }

            $parentWa = $user?->studentProfile?->parent_phone_wa ?: $user?->studentProfile?->parent_whatsapp_number;
            if (! empty($parentWa) && !in_array($cleanNumber($parentWa), $sentCleaned)) {
                SendWhatsAppMessage::dispatch(
                    to: $parentWa,
                    message: $message,
                    relatedType: LeaveRequest::class,
                    relatedId: $leaveRequest->id,
                );
            }
        });

        return 'Pengajuan ditolak.';
    }
}
