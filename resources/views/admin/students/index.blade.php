<x-app-layout>
    <x-slot name="title">Data Siswa</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-display-sm text-surface-50">Data Siswa</h1>
                <p class="text-sm text-electric-200/80 mt-1">Kelola data profil dan identitas siswa</p>
            </div>
            <a href="{{ route('admin.students.create') }}" class="btn-primary btn-ripple h-10 px-5 gap-2 w-full sm:w-auto justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Siswa
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Search Card --}}
        <div class="card animate-fade-slide-up">
            <form method="GET" action="{{ route('admin.students.index') }}" class="filter-panel filter-form">
                <div class="filter-search-wrap">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        class="filter-input"
                        placeholder="Cari nama, NISN, kelas, jurusan, WA ortu..."
                    >
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary btn-ripple px-5 h-[42px]">
                        Cari
                    </button>
                    @if ($q !== '')
                        <a href="{{ route('admin.students.index') }}" class="btn-secondary px-5 h-[42px] flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="table-wrapper animate-fade-slide-up stagger-1">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="table-header">
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Siswa</th>
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider hidden sm:table-cell">NISN</th>
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Kelas & Jurusan</th>
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">WA Ortu</th>
                            <th class="text-right py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr class="table-row">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-navy-500/20 to-navy-600/20 flex items-center justify-center shrink-0">
                                            <span class="text-sm font-bold text-navy-600">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                        </div>
                                        <div class="font-semibold text-navy-800 text-sm truncate">{{ $student->name }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 hidden sm:table-cell text-sm text-navy-600 font-medium">
                                    {{ $student->studentProfile?->nis ?? '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-sm text-navy-800 font-medium">{{ $student->studentProfile?->classRoom?->name ?? '-' }}</div>
                                    <div class="text-xs text-bw-400">{{ $student->studentProfile?->jurusan ?? $student->studentProfile?->classRoom?->jurusan ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4 hidden md:table-cell">
                                    @if($student->studentProfile?->parent_phone_wa)
                                        <div class="flex items-center gap-1.5 text-sm text-navy-600">
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                                            {{ $student->studentProfile->parent_phone_wa }}
                                        </div>
                                    @else
                                        <span class="text-bw-300">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('admin.students.edit', $student) }}" class="inline-flex btn-secondary h-8 px-3 text-xs gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.89.11l-3.15-.8a.8.8 0 0 1-.505-.505l.8-3.15a4.5 4.5 0 0 1 1.11-1.89l12.42-12.42Zm8.25-2.257a2.25 2.25 0 0 1-3.182 0l-1.06-1.06a2.25 2.25 0 0 1 0-3.182l1.06-1.06a2.25 2.25 0 0 1 3.182 0l1.06 1.06a2.25 2.25 0 0 1 0 3.182l-1.06 1.06Zm-2.25 2.257-2.12-2.12"/></svg>
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-bw-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-bw-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                        Tidak ada data siswa.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($students->hasPages())
                <div class="mt-4 px-4 pb-4">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
