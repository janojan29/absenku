<?php

namespace App\Http\Requests\Auth;

use App\Models\StudentProfile;
use App\Models\Teacher;
use App\Models\User;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'login_identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginIdentifier = trim((string) $this->input('login_identifier', ''));
        $password = (string) $this->input('password', '');
        $remember = $this->boolean('remember');

        // Login menggunakan NIP (guru), NISN (siswa), atau email (admin/petugas piket)

        // Try teacher via NIP
        $teacher = Teacher::query()->where('nip', $loginIdentifier)->first();
        if ($teacher?->user && Hash::check($password, $teacher->user->password)) {
            Auth::login($teacher->user, $remember);
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // Try student via NISN
        $student = StudentProfile::query()->where('nis', $loginIdentifier)->first();
        if ($student?->user && Hash::check($password, $student->user->password)) {
            // Check for pending leave request today
            $today = Carbon::today();
            $pendingLeave = LeaveRequest::query()
                ->where('user_id', $student->user->id)
                ->whereDate('date', $today)
                ->where('status', 'pending')
                ->first();

            if ($pendingLeave) {
                $typeLabel = $pendingLeave->type === 'absent' ? 'tidak masuk' : 'pulang lebih awal';
                throw ValidationException::withMessages([
                    'login_identifier' => "Pengajuan izin {$typeLabel} Anda sedang diverifikasi oleh petugas piket.",
                ]);
            }

            Auth::login($student->user, $remember);
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // Try admin/petugas piket via email
        $user = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($loginIdentifier)])->first();
        $isAllowedPrivilegedRole = $user && (
            (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'petugas_piket']))
            || in_array((string) ($user->role ?? ''), ['admin', 'petugas_piket'], true)
        );

        if ($user && $isAllowedPrivilegedRole && Hash::check($password, $user->password)) {
            Auth::login($user, $remember);
            RateLimiter::clear($this->throttleKey());
            return;
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'login_identifier' => trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login_identifier' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login_identifier')) . '|' . $this->ip());
    }
}
