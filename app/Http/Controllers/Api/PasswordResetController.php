<?php

namespace App\Http\Controllers\Api;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    use PasswordValidationRules;

    public function requestReset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'full_name' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:50'],
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])
            ->with(['teacher', 'studentProfile'])
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }

        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Untuk reset password admin, silakan hubungi operator sekolah.'], 403);
        }

        if ($user->hasRole('petugas_piket')) {
            return response()->json(['message' => 'Untuk reset password petugas piket, silakan hubungi administrator sekolah.'], 403);
        }

        if ($this->normalizeName($user->name) !== $this->normalizeName($data['full_name'])) {
            return response()->json(['message' => 'Nama lengkap tidak sesuai dengan akun.'], 400);
        }

        $identifier = $this->normalizeIdentifier($data['identifier']);
        $teacherNip = $this->normalizeIdentifier($user->teacher?->nip);
        $studentNis = $this->normalizeIdentifier($user->studentProfile?->nis);

        if ($identifier === '' || ($identifier !== $teacherNip && $identifier !== $studentNis)) {
            return response()->json(['message' => 'NISN/NIP tidak sesuai.'], 400);
        }

        $whatsappNumber = $user->whatsapp_number ?? $user->teacher?->phone_number ?? null;

        if (! $whatsappNumber) {
            return response()->json(['message' => 'Nomor WhatsApp tidak terdaftar untuk akun ini. Hubungi admin.'], 400);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'whatsapp_otp' => $otp,
            'whatsapp_otp_expires_at' => now()->addMinutes(1), // 1 min for app
        ]);

        $message = "Kode OTP untuk verifikasi lupa password: *{$otp}*\n\nKode ini berlaku 1 menit. Jangan bagikan ke siapapun.";
        SendWhatsAppMessage::dispatch(
            to: $whatsappNumber,
            message: $message,
            relatedType: User::class,
            relatedId: $user->id,
        );

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke nomor WhatsApp terdaftar.',
            'user_id' => $user->id,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::query()->find($request->user_id);
        if (! $user || ! $user->whatsapp_otp) {
            return response()->json(['message' => 'Permintaan tidak valid.'], 400);
        }

        if ($user->whatsapp_otp_expires_at && now()->isAfter($user->whatsapp_otp_expires_at)) {
            $user->update(['whatsapp_otp' => null, 'whatsapp_otp_expires_at' => null]);
            return response()->json(['message' => 'Kode OTP sudah kadaluarsa.'], 400);
        }

        if (! hash_equals((string) $user->whatsapp_otp, (string) $request->otp)) {
            return response()->json(['message' => 'Kode OTP tidak valid.'], 400);
        }

        // OTP Valid. Clear it and generate a reset token.
        $resetToken = Str::random(60);
        $user->forceFill([
            'whatsapp_otp' => null,
            'whatsapp_otp_expires_at' => null,
            'remember_token' => hash('sha256', $resetToken), // Store securely
        ])->save();

        return response()->json([
            'message' => 'Verifikasi berhasil.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'reset_token' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ]);

        $user = User::query()->find($request->user_id);
        if (! $user || ! hash_equals((string) $user->remember_token, hash('sha256', $request->reset_token))) {
            return response()->json(['message' => 'Sesi reset password tidak valid atau telah kedaluwarsa.'], 400);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60), // Invalidate current reset token
            'whatsapp_otp_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Password berhasil diperbarui. Silakan login kembali.']);
    }

    private function normalizeName(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value));
        return Str::lower($value ?? '');
    }

    private function normalizeIdentifier(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', '', $value);
        return Str::upper($value ?? '');
    }
}
