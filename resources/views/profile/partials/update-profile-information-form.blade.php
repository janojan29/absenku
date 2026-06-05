<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('No. HP') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Perbarui nomor HP yang terhubung dengan akun.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="whatsapp_number" :value="__('No. HP')" />
            <x-text-input
                id="whatsapp_number"
                name="whatsapp_number"
                type="text"
                class="mt-1 block w-full h-12 px-4"
                :value="old('whatsapp_number', $user->whatsapp_number)"
                placeholder="+62812..."
                autocomplete="tel"
            />
            <x-input-error class="mt-2" :messages="$errors->get('whatsapp_number')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>
</section>
