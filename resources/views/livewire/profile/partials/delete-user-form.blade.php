@php
    $authUser = auth()->user();
    $isProtectedAccount = $authUser && method_exists($authUser, 'hasAnyRole')
        ? $authUser->hasAnyRole(['admin', 'petugas_piket', 'guru', 'guru_walikelas', 'siswa'])
        : false;

    $deleteBlockedMessage = 'Akun ini tidak dapat dihapus dari halaman profile.';

    if ($authUser && method_exists($authUser, 'hasAnyRole')) {
        if ($authUser->hasAnyRole(['guru', 'guru_walikelas', 'siswa'])) {
            $deleteBlockedMessage = 'Akun guru/guru walikelas/siswa hanya dapat dihapus oleh admin melalui menu Users.';
        } elseif ($authUser->hasAnyRole(['admin', 'petugas_piket'])) {
            $deleteBlockedMessage = 'Akun admin/petugas piket tidak dapat dihapus.';
        }
    }
@endphp

@if ($isProtectedAccount)
    <section class="space-y-2">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Hapus Akun') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ $deleteBlockedMessage }}
            </p>
        </header>
    </section>
@else
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Hapus Akun') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Setelah akun Anda dihapus, semua data dan sumber daya akan dihapus secara permanen. Sebelum menghapus akun, silakan unduh data yang ingin Anda simpan.') }}
            </p>
        </header>

        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >{{ __('Hapus Akun') }}</x-danger-button>

        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Apakah Anda yakin ingin menghapus akun?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Setelah akun Anda dihapus, semua data dan sumber daya akan dihapus secara permanen. Masukkan password Anda untuk mengonfirmasi penghapusan akun.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('Password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Batal') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        {{ __('Hapus Akun') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    </section>
@endif
