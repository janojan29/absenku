<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">
        <div x-data="clock()">
            <h1 class="text-display-sm text-surface-50"><span x-text="greeting">Selamat</span>!</h1>
            <p class="text-sm text-electric-200/80 mt-1" x-text="date"></p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card animate-fade-slide-up text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-navy-500 to-navy-600 flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-navy-900 mb-2">Anda sudah login!</h2>
            <p class="text-bw-400 mb-6">Selamat datang di Sistem Absensi Digital</p>
            <a href="{{ route('attendance.index') }}" class="btn-primary btn-ripple inline-flex gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Buka Absensi
            </a>
        </div>
    </div>
</x-app-layout>
