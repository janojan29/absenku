<div class="space-y-6">
    <div class="card animate-fade-slide-up relative z-20">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-navy-800">Ringkasan Hari Ini</h3>
                <p class="text-sm text-bw-400">{{ $today->translatedFormat('d M Y') }}</p>
            </div>
            <div class="w-full sm:w-64">
                <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Kelas</label>
                <x-expandable-select
                    name="classRoomId"
                    :options="array_merge(
                        [['value' => '', 'label' => 'Semua Kelas']],
                        $classes->map(fn($c) => ['value' => $c->id, 'label' => $c->jurusan ? $c->name.' : '.$c->jurusan : $c->name])->toArray()
                    )"
                    :selected="$classRoomId ?? ''"
                    placeholder="Semua Kelas"
                    wireClick="$set('classRoomId', :value)"
                />
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 animate-fade-slide-up stagger-1 relative z-10">
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Hadir</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $counts['present'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Terlambat</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $counts['late'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Izin</p>
            <p class="text-2xl font-bold text-cyan-600 mt-1">{{ $counts['leave'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Belum Absen</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $counts['unknown'] }}</p>
        </div>
    </div>

    <div class="table-wrapper animate-fade-slide-up stagger-2 relative z-0">
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
                            $label = $statusLabels[$student->user_id] ?? null;
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
                                        <div class="text-xs text-bw-400 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                            <span>{{ $student->nis ?? '-' }}</span>
                                            <span class="sm:hidden text-bw-300">•</span>
                                            <span class="sm:hidden font-medium text-navy-600">{{ $student->classRoom?->name ?? '-' }}</span>
                                        </div>
                                        {{-- Responsive time badges for mobile/tablet screens --}}
                                        <div class="md:hidden text-[11px] text-bw-500 mt-1 flex items-center gap-2">
                                            <span class="bg-bw-100 px-1.5 py-0.5 rounded text-navy-700">
                                                Masuk: {{ $attendance?->check_in_at ? \Illuminate\Support\Carbon::parse($attendance->check_in_at)->format('H:i') : '-' }}
                                            </span>
                                            <span class="bg-bw-100 px-1.5 py-0.5 rounded text-navy-700">
                                                Pulang: {{ $attendance?->check_out_at ? \Illuminate\Support\Carbon::parse($attendance->check_out_at)->format('H:i') : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 hidden sm:table-cell">
                                <div class="text-sm text-navy-800 font-medium">{{ $student->classRoom?->name ?? '-' }}</div>
                                <div class="text-xs text-bw-400">{{ $student->jurusan ?? $student->classRoom?->jurusan ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <x-status-badge :status="$status" :label="$label" />
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
        @if($students->hasPages())
            <div class="mt-4 px-4 pb-4">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
