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
        <div class="card animate-fade-slide-up stagger-2">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card animate-fade-slide-up stagger-3">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
