<div class="space-y-6">
    <div class="card animate-fade-slide-up">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-navy-800">Ringkasan Hari Ini</h3>
                <p class="text-sm text-bw-400">{{ $today->translatedFormat('d M Y') }}</p>
            </div>
            <div class="w-full sm:w-64">
                <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400">Kelas</label>
                <select wire:model.live="classRoomId" class="form-select mt-1">
                    <option value="">Semua Kelas</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->jurusan ? $class->name.' : '.$class->jurusan : $class->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 animate-fade-slide-up stagger-1">
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Hadir</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $counts['present'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Terlambat</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $counts['late'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Ijin</p>
            <p class="text-2xl font-bold text-cyan-600 mt-1">{{ $counts['leave'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Belum Absen</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $counts['unknown'] }}</p>
        </div>
    </div>

    <div class="table-wrapper animate-fade-slide-up stagger-2">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header">
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Nama</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden sm:table-cell">Kelas</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Masuk</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Pulang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $status = $effectiveStatuses[$student->user_id] ?? 'unknown';
                            $attendance = $attendances->get($student->user_id);
                        @endphp
                        <tr class="table-row">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-navy-500/20 to-navy-600/20 flex items-center justify-center shrink-0">
                                        <span class="text-sm font-bold text-navy-600">{{ strtoupper(substr($student->user?->name ?? '-', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-navy-800 truncate">{{ $student->user?->name ?? '-' }}</div>
                                        <div class="text-xs text-bw-400">{{ $student->nis ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 hidden sm:table-cell">
                                <div class="text-sm text-navy-800 font-medium">{{ $student->classRoom?->name ?? '-' }}</div>
                                <div class="text-xs text-bw-400">{{ $student->jurusan ?? $student->classRoom?->jurusan ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <x-status-badge :status="$status" />
                            </td>
                            <td class="py-3 px-4 text-sm text-navy-600 hidden md:table-cell">
                                {{ $attendance?->check_in_at ? \Illuminate\Support\Carbon::parse($attendance->check_in_at)->format('H:i') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-sm text-navy-600 hidden md:table-cell">
                                {{ $attendance?->check_out_at ? \Illuminate\Support\Carbon::parse($attendance->check_out_at)->format('H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-bw-400 text-sm">Tidak ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
