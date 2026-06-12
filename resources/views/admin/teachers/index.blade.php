<x-app-layout>
    <x-slot name="title">Data Guru</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-display-sm text-surface-50">Data Guru</h1>
                <p class="text-sm text-electric-200/80 mt-1">Kelola data profil dan penugasan guru</p>
            </div>
            <a href="{{ route('admin.teachers.create') }}" class="btn-primary btn-ripple h-10 px-5 gap-2 w-full sm:w-auto justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Guru
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <livewire:admin.teacher-table />
    </div>
</x-app-layout>
