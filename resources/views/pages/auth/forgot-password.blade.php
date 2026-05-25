<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Lupa password')"
            :description="__('Isi data Anda untuk memverifikasi akun terlebih dahulu')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (! $verifiedUser)
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-zinc-500">
                Langkah 1 dari 2 • Verifikasi data
            </div>

            <form method="POST" action="{{ route('password.verify') }}" class="flex flex-col gap-5">
                @csrf

                <flux:input
                    name="email"
                    :label="__('Email terdaftar')"
                    type="email"
                    required
                    autofocus
                    :value="old('email')"
                    placeholder="email@example.com"
                />

                <flux:input
                    name="full_name"
                    :label="__('Nama lengkap')"
                    type="text"
                    required
                    :value="old('full_name')"
                    placeholder="Nama lengkap sesuai akun"
                />

                <flux:input
                    name="identifier"
                    :label="__('NISN / NIP')"
                    type="text"
                    required
                    :value="old('identifier')"
                    placeholder="Masukkan NISN atau NIP"
                />

                <flux:button variant="primary" type="submit" class="w-full" data-test="verify-reset-identity-button">
                    {{ __('Verifikasi data') }}
                </flux:button>
            </form>
        @else
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <div class="font-semibold">Data terverifikasi</div>
                <div class="mt-1 text-xs text-emerald-600">{{ $verifiedUser->name }} • {{ $verifiedUser->email }}</div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-zinc-500">
                Langkah 2 dari 2 • Buat password baru
            </div>

            <form method="POST" action="{{ route('password.custom.update') }}" class="flex flex-col gap-5">
                @csrf

                <flux:input
                    name="password"
                    :label="__('Password baru')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password baru')"
                    viewable
                />

                <flux:input
                    name="password_confirmation"
                    :label="__('Konfirmasi password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Ulangi password')"
                    viewable
                />

                <flux:button variant="primary" type="submit" class="w-full" data-test="reset-password-button">
                    {{ __('Simpan password baru') }}
                </flux:button>
            </form>

            <div class="text-center text-sm text-zinc-400">
                <flux:link :href="route('password.request', ['reset' => 1])" wire:navigate>
                    {{ __('Ganti data verifikasi') }}
                </flux:link>
            </div>
        @endif

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Kembali ke') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('login') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
