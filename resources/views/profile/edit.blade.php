<x-app-layout>
    <x-slot name="title">Profil</x-slot>
    <x-slot name="header">
        <h1 class="text-display-sm text-surface-50">Profil Saya</h1>
        <p class="text-sm text-electric-200/80 mt-1">Kelola informasi akun Anda</p>
    </x-slot>

    {{-- Profile Hero --}}
    <div class="relative overflow-hidden rounded-2xl mb-6 animate-fade-slide-up" style="background: linear-gradient(135deg, #0d1b2a 0%, #1e4d8c 50%, #2563b8 100%);">
        <svg class="absolute bottom-0 left-0 w-full" viewBox="0 0 400 30" preserveAspectRatio="none" style="height:30px;">
            <path d="M0,15 Q100,0 200,15 T400,15 L400,30 L0,30 Z" fill="rgba(250,250,247,1)"/>
        </svg>
        <div class="relative z-10 px-6 pt-8 pb-14 text-center">
            <div class="w-20 h-20 mx-auto rounded-full bg-white/20 border-4 border-white/30 flex items-center justify-center shadow-glow mb-4">
                <span class="text-3xl font-bold text-white">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
            </div>
            <h2 class="text-xl font-bold text-white">{{ Auth::user()->name }}</h2>
            <p class="text-navy-300 text-sm mt-1">{{ Auth::user()->email }}</p>
            @php
                $roles = Auth::user()->getRoleNames()->toArray();
                $roleLabels = ['siswa'=>'Siswa','guru'=>'Guru','guru_walikelas'=>'Wali Kelas','petugas_piket'=>'Petugas Piket','admin'=>'Administrator'];
                $displayRole = collect($roles)->map(fn($r) => $roleLabels[$r] ?? $r)->implode(', ');
            @endphp
            <span class="inline-block mt-3 px-4 py-1.5 rounded-full bg-white/15 text-white text-xs font-semibold backdrop-blur-sm">{{ $displayRole }}</span>
        </div>
    </div>

    <div class="max-w-2xl mx-auto space-y-6">
        @if(Auth::user()->hasDefaultPassword() || empty(Auth::user()->whatsapp_number))
            @if(Auth::user()->hasAnyRole(['siswa', 'guru', 'guru_walikelas']))
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start gap-3 shadow-sm animate-fade-slide-up">
                    <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <h3 class="font-bold text-amber-900">Ubah Password & Nomor WhatsApp Wajib</h3>
                        <p class="text-sm mt-1">
                            @if(empty(Auth::user()->whatsapp_number) && Auth::user()->hasDefaultPassword())
                                Anda diwajibkan mengubah password default dan mengisi nomor WhatsApp sebelum dapat mengakses halaman lainnya.
                            @elseif(empty(Auth::user()->whatsapp_number))
                                Anda diwajibkan mengisi nomor WhatsApp sebelum dapat mengakses halaman lainnya.
                            @else
                                Anda diwajibkan mengubah password default Anda sebelum dapat mengakses halaman lainnya.
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        @endif

        @if (!Auth::user()->hasAnyRole(['admin', 'petugas_piket']))
            <div class="card animate-fade-slide-up">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card animate-fade-slide-up stagger-2">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        @else
            <div class="card animate-fade-slide-up p-6 text-center text-bw-400">
                @if (Auth::user()->hasRole('admin'))
                    {{ __('Password untuk akun Administrator hanya dapat diubah melalui database seeder atau hubungi operator.') }}
                @else
                    {{ __('Password untuk akun Petugas Piket hanya dapat diubah oleh Admin.') }}
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
