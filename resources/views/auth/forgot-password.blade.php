<x-guest-layout :hide-auth-header="true">
    <form
        method="POST"
        action="{{ $otpVerified ? route('password.custom.update') : ($otpSentAt ? route('password.verify-otp') : route('password.verify')) }}"
        class="space-y-5"
    >
        @csrf

        <div class="text-center">
            <h2 class="text-2xl font-bold text-navy-900">Lupa Password</h2>
            <p class="text-bw-400 text-sm mt-1">
                {{ $otpVerified ? 'Buat password baru untuk akun Anda.' : ($otpSentAt ? 'Verifikasi kode OTP.' : 'Verifikasi data terlebih dahulu.') }}
            </p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (! $otpSentAt)
            {{-- Step 1: Data Verification --}}
            <div class="rounded-2xl border border-bw-200 bg-bw-50 px-4 py-3 text-xs text-bw-400">
                Langkah 1 dari 3 • Verifikasi data
            </div>

            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-medium text-navy-700">Email terdaftar</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="form-input"
                >
                @if ($errors->has('email'))
                    <p class="text-xs text-red-500">{{ $errors->first('email') }}</p>
                @endif
            </div>

            <div class="space-y-1.5">
                <label for="full_name" class="block text-sm font-medium text-navy-700">Nama lengkap</label>
                <input
                    id="full_name"
                    name="full_name"
                    type="text"
                    value="{{ old('full_name') }}"
                    required
                    placeholder="Nama lengkap sesuai akun"
                    class="form-input"
                >
                @if ($errors->has('full_name'))
                    <p class="text-xs text-red-500">{{ $errors->first('full_name') }}</p>
                @endif
            </div>

            <div class="space-y-1.5">
                <label for="identifier" class="block text-sm font-medium text-navy-700">NISN / NIP</label>
                <input
                    id="identifier"
                    name="identifier"
                    type="text"
                    value="{{ old('identifier') }}"
                    required
                    placeholder="Masukkan NISN atau NIP"
                    class="form-input"
                >
                @if ($errors->has('identifier'))
                    <p class="text-xs text-red-500">{{ $errors->first('identifier') }}</p>
                @endif
            </div>

            <button type="submit" class="btn-primary btn-ripple w-full h-12 text-base">
                Lanjutkan ke OTP
            </button>
        @elseif (! $otpVerified)
            {{-- Step 2: OTP Verification --}}
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <div class="font-semibold">Data terverifikasi</div>
                <div class="mt-1 text-xs text-emerald-600">{{ $verifiedUser?->name ?? '-' }} • {{ $verifiedUser?->email ?? '-' }}</div>
            </div>

            <div class="rounded-2xl border border-bw-200 bg-bw-50 px-4 py-3 text-xs text-bw-400">
                Langkah 2 dari 3 • Verifikasi OTP
            </div>

            <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5 text-sm text-blue-700">
                <div class="text-xs">Kode OTP telah dikirim ke nomor WhatsApp terdaftar. Masukkan kode 6 digit yang Anda terima.</div>
            </div>

            <div class="space-y-1.5">
                <label for="otp" class="block text-sm font-medium text-navy-700">Kode OTP</label>
                <input
                    id="otp"
                    name="otp"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    value="{{ old('otp') }}"
                    required
                    autofocus
                    placeholder="000000"
                    pattern="[0-9]{6}"
                    class="form-input text-center text-2xl tracking-widest font-mono"
                >
                @if ($errors->has('otp'))
                    <p class="text-xs text-red-500">{{ $errors->first('otp') }}</p>
                @endif
            </div>

            <button type="submit" class="btn-primary btn-ripple w-full h-12 text-base">
                Verifikasi OTP
            </button>

            <div class="pt-2 text-center text-sm" style="color:#0f172a; display:block;">
                <button
                    type="submit"
                    form="resend-otp-form"
                    id="resend-otp-btn"
                    class="font-semibold transition-colors duration-150"
                    style="color:#2563eb;background:none;border:0;padding:0;cursor:pointer;"
                    aria-disabled="true"
                >
                    Kirim ulang OTP
                </button>
                <span class="px-1" style="color:#0f172a;">atau</span>
                <a href="{{ route('password.request', ['reset' => 1]) }}" class="font-semibold transition-colors duration-150" style="color:#2563eb;">
                    Ganti data
                </a>
            </div>

            <div class="text-center text-xs" style="color:#334155;">
                OTP kedaluwarsa dalam <span id="otp-expire-countdown" data-sent-at="{{ $otpSentAt ?? 0 }}" data-ttl="{{ session('password_reset_otp_ttl', 600) }}" style="color:#0f172a;"></span>
            </div>

            <script>
                (function () {
                    var el = document.getElementById('otp-expire-countdown');
                    var resendBtn = document.getElementById('resend-otp-btn');
                    if (!el || !resendBtn) return;

                    var sentAt = Number(el.dataset.sentAt || 0);
                    var ttl = Number(el.dataset.ttl || 0);
                    if (!sentAt || !ttl) {
                        el.textContent = '--:--';
                        resendBtn.style.opacity = '1';
                        resendBtn.style.pointerEvents = 'auto';
                        resendBtn.setAttribute('aria-disabled', 'false');
                        return;
                    }

                    function tick() {
                        var nowSeconds = Math.floor(Date.now() / 1000);
                        var remaining = Math.max(0, sentAt + ttl - nowSeconds);
                        var minutes = Math.floor(remaining / 60);
                        var seconds = remaining % 60;
                        el.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                        if (remaining > 0) {
                            resendBtn.style.opacity = '0.8';
                            resendBtn.style.pointerEvents = 'none';
                            resendBtn.setAttribute('aria-disabled', 'true');
                        } else {
                            resendBtn.style.opacity = '1';
                            resendBtn.style.pointerEvents = 'auto';
                            resendBtn.setAttribute('aria-disabled', 'false');
                        }
                        if (remaining === 0) {
                            clearInterval(timer);
                        }
                    }

                    tick();
                    var timer = setInterval(tick, 1000);
                })();
            </script>
        @else
            {{-- Step 3: Set New Password --}}
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <div class="font-semibold">Data terverifikasi</div>
                <div class="mt-1 text-xs text-emerald-600">{{ $verifiedUser?->name ?? '-' }} • {{ $verifiedUser?->email ?? '-' }}</div>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <div class="font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    OTP terverifikasi
                </div>
            </div>

            <div class="rounded-2xl border border-bw-200 bg-bw-50 px-4 py-3 text-xs text-bw-400">
                Langkah 3 dari 3 • Buat password baru
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-sm font-medium text-navy-700">Password baru</label>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Password baru"
                        class="form-input pr-11"
                    >
                    <button
                        type="button"
                        onclick="togglePasswordVisibility('password', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-bw-400 hover:text-navy-500"
                        aria-label="Lihat password"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
                @if ($errors->has('password'))
                    <p class="text-xs text-red-500">{{ $errors->first('password') }}</p>
                @endif
            </div>

            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-sm font-medium text-navy-700">Konfirmasi password</label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi password"
                        class="form-input pr-11"
                    >
                    <button
                        type="button"
                        onclick="togglePasswordVisibility('password_confirmation', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-bw-400 hover:text-navy-500"
                        aria-label="Lihat konfirmasi password"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary btn-ripple w-full h-12 text-base">
                Simpan password baru
            </button>

            <div class="text-center">
                <a class="text-sm text-navy-500 hover:text-navy-700 font-medium transition-colors duration-150"
                   href="{{ route('password.request', ['reset' => 1]) }}">
                    Ganti data verifikasi
                </a>
            </div>
        @endif

        <div class="text-center">
            <a class="text-sm text-navy-500 hover:text-navy-700 font-medium transition-colors duration-150"
               href="{{ route('login') }}">
                Kembali ke login
            </a>
        </div>
    </form>

    <form id="resend-otp-form" method="POST" action="{{ route('password.resend-otp') }}" class="hidden">
        @csrf
    </form>

    <script>
        (function () {
            if (window.togglePasswordVisibility) return;

            window.togglePasswordVisibility = function (inputId, button) {
                var input = document.getElementById(inputId);
                if (!input) return;

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');
            };
        })();
    </script>
</x-guest-layout>
