<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordAssistanceController extends Controller
{
    use PasswordValidationRules;

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->boolean('reset')) {
            $request->session()->forget([
                'password_reset_user_id',
                'password_reset_otp_verified_at',
                'password_reset_otp_sent_at',
            ]);
        }

        $verifiedUser = null;
        $userId = $request->session()->get('password_reset_user_id');
        $otpSentAt = $request->session()->get('password_reset_otp_sent_at');
        $otpVerified = $request->session()->get('password_reset_otp_verified_at');

        if ($userId) {
            $verifiedUser = User::query()->with(['teacher', 'studentProfile'])->find($userId);

            if (! $verifiedUser) {
                $request->session()->forget([
                    'password_reset_user_id',
                    'password_reset_otp_verified_at',
                    'password_reset_otp_sent_at',
                ]);

                return redirect()->route('password.request', ['reset' => 1]);
            }
        }

        return view('auth.forgot-password', [
            'verifiedUser' => $verifiedUser,
            'otpSentAt' => $otpSentAt,
            'otpVerified' => $otpVerified,
        ]);
    }

    public function verify(Request $request): RedirectResponse
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
            return back()->withErrors([
                'email' => 'Email tidak ditemukan.',
            ])->withInput();
        }

        if ($this->normalizeName($user->name) !== $this->normalizeName($data['full_name'])) {
            return back()->withErrors([
                'full_name' => 'Nama lengkap tidak sesuai dengan akun.',
            ])->withInput();
        }

        $identifier = $this->normalizeIdentifier($data['identifier']);
        $teacherNip = $this->normalizeIdentifier($user->teacher?->nip);
        $studentNis = $this->normalizeIdentifier($user->studentProfile?->nis);

        if ($identifier === '' || ($identifier !== $teacherNip && $identifier !== $studentNis)) {
            return back()->withErrors([
                'identifier' => 'NISN/NIP tidak sesuai.',
            ])->withInput();
        }

        // Get WhatsApp number
        $whatsappNumber = $user->whatsapp_number
            ?? $user->teacher?->phone_number
            ?? null;

        if (! $whatsappNumber) {
            return back()->withErrors([
                'email' => 'Nomor WhatsApp tidak terdaftar untuk akun ini.',
            ])->withInput();
        }

        // Generate and save OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'whatsapp_otp' => $otp,
            'whatsapp_otp_expires_at' => now()->addSeconds(30),
        ]);

        // Send OTP via WhatsApp
        $message = "Kode OTP untuk verifikasi lupa password: *{$otp}*\n\nKode ini berlaku 30 detik. Jangan bagikan ke siapapun.";
        SendWhatsAppMessage::dispatch(
            to: $whatsappNumber,
            message: $message,
            relatedType: User::class,
            relatedId: $user->id,
        );

        $request->session()->put('password_reset_user_id', $user->id);
        $request->session()->put('password_reset_otp_sent_at', now()->timestamp);
        $request->session()->put('password_reset_otp_ttl', 30);

        return redirect()
            ->route('password.request')
            ->with('status', 'Kode OTP telah dikirim ke nomor WhatsApp terdaftar.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('password_reset_user_id');

        if (! $userId) {
            return redirect()
                ->route('password.request')
                ->withErrors(['otp' => 'Silakan verifikasi data terlebih dahulu.']);
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'otp.regex' => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        $otpInput = preg_replace('/\D+/', '', (string) $request->input('otp'));

        $user = User::query()->find($userId);

        if (! $user || ! $user->whatsapp_otp) {
            return redirect()
                ->route('password.request')
                ->withErrors(['otp' => 'Silakan verifikasi data terlebih dahulu.']);
        }

        // Check OTP expiration
        if ($user->whatsapp_otp_expires_at && now()->isAfter($user->whatsapp_otp_expires_at)) {
            $user->update([
                'whatsapp_otp' => null,
                'whatsapp_otp_expires_at' => null,
            ]);

            return back()->withErrors([
                'otp' => 'Kode OTP sudah kadaluarsa. Silakan verifikasi data kembali.',
            ]);
        }

        // Verify OTP
        if (! hash_equals((string) $user->whatsapp_otp, (string) $otpInput)) {
            return back()->withErrors([
                'otp' => 'Kode OTP tidak valid.',
            ]);
        }

        // Clear OTP after successful verification
        $user->update([
            'whatsapp_otp' => null,
            'whatsapp_otp_expires_at' => null,
        ]);

        $request->session()->put('password_reset_otp_verified_at', now()->timestamp);

        return redirect()
            ->route('password.request')
            ->with('status', 'Verifikasi berhasil. Silakan buat password baru.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('password_reset_user_id');

        if (! $userId) {
            return redirect()
                ->route('password.request')
                ->withErrors(['otp' => 'Silakan verifikasi data terlebih dahulu.']);
        }

        $user = User::query()->find($userId);

        if (! $user) {
            $request->session()->forget([
                'password_reset_user_id',
                'password_reset_otp_verified_at',
                'password_reset_otp_sent_at',
            ]);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Akun tidak ditemukan.']);
        }

        $whatsappNumber = $user->whatsapp_number
            ?? $user->teacher?->phone_number
            ?? null;

        if (! $whatsappNumber) {
            return back()->withErrors([
                'otp' => 'Nomor WhatsApp tidak terdaftar untuk akun ini.',
            ]);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'whatsapp_otp' => $otp,
            'whatsapp_otp_expires_at' => now()->addSeconds(30),
        ]);

        $message = "Kode OTP untuk verifikasi lupa password: *{$otp}*\n\nKode ini berlaku 30 detik. Jangan bagikan ke siapapun.";
        SendWhatsAppMessage::dispatch(
            to: $whatsappNumber,
            message: $message,
            relatedType: User::class,
            relatedId: $user->id,
        );

        $request->session()->put('password_reset_otp_sent_at', now()->timestamp);
        $request->session()->put('password_reset_otp_ttl', 30);

        return back()->with('status', 'Kode OTP baru telah dikirim ke nomor WhatsApp terdaftar.');
    }

    public function update(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('password_reset_user_id');
        $otpVerified = $request->session()->get('password_reset_otp_verified_at');

        if (! $userId || ! $otpVerified) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Silakan verifikasi OTP terlebih dahulu.']);
        }

        $request->validate([
            'password' => $this->passwordRules(),
        ]);

        $user = User::query()->find($userId);

        if (! $user) {
            $request->session()->forget([
                'password_reset_user_id',
                'password_reset_otp_verified_at',
            ]);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Akun tidak ditemukan.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->setRememberToken(Str::random(60));

        $user->save();

        $request->session()->forget([
            'password_reset_user_id',
            'password_reset_otp_verified_at',
            'password_reset_otp_sent_at',
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil diperbarui. Silakan login kembali.');
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
