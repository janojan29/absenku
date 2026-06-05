<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengaturan Profil')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $whatsapp_number = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->whatsapp_number = Auth::user()->whatsapp_number ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $rules = $this->profileRules($user->id);
        
        if ($user->hasAnyRole(['siswa', 'guru', 'guru_walikelas'])) {
            $rules['whatsapp_number'] = ['required', 'string', 'max:20', 'regex:/^[0-9+]+$/'];
        } else {
            $rules['whatsapp_number'] = ['nullable', 'string', 'max:20', 'regex:/^[0-9+]+$/'];
        }

        $validated = $this->validate($rules);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profil diperbarui.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('Tautan verifikasi baru telah dikirim ke alamat email Anda.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Pengaturan Profil') }}</flux:heading>

    @if(Auth::user()->hasDefaultPassword() || empty(Auth::user()->whatsapp_number))
        @if(Auth::user()->hasAnyRole(['siswa', 'guru', 'guru_walikelas']))
            <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <h3 class="font-bold text-amber-900">Ubah Password & Nomor WhatsApp Wajib</h3>
                    <p class="text-sm mt-1">
                        @if(empty(Auth::user()->whatsapp_number) && Auth::user()->hasDefaultPassword())
                            Anda diwajibkan mengubah password default di menu <flux:link :href="route('security.edit')">Keamanan</flux:link> dan mengisi nomor WhatsApp di bawah sebelum dapat mengakses halaman lainnya.
                        @elseif(empty(Auth::user()->whatsapp_number))
                            Anda diwajibkan mengisi nomor WhatsApp di bawah sebelum dapat mengakses halaman lainnya.
                        @else
                            Anda diwajibkan mengubah password default Anda di menu <flux:link :href="route('security.edit')">Keamanan</flux:link> sebelum dapat mengakses halaman lainnya.
                        @endif
                    </p>
                </div>
            </div>
        @endif
    @endif

    <x-pages::settings.layout :heading="__('Profil')" :subheading="__('Perbarui nama, alamat email, dan nomor WhatsApp Anda')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Nama')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Alamat email Anda belum diverifikasi.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                            </flux:link>
                        </flux:text>

                    </div>
                @endif
            </div>

            <flux:input wire:model="whatsapp_number" :label="__('Nomor WhatsApp')" type="text" autocomplete="tel" placeholder="Contoh: 081234567890" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
