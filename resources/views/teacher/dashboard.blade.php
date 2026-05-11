<x-app-layout>
    <x-slot name="title">Dashboard Guru</x-slot>
    <x-slot name="header">
        <div x-data="clock()">
            <h1 class="text-display-sm text-surface-50"><span x-text="greeting">Selamat</span>, {{ Auth::user()->name }}!</h1>
            <p class="text-sm text-electric-200/80 mt-1" x-text="date"></p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <livewire:teacher.dashboard />
    </div>
</x-app-layout>
