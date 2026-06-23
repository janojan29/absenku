<x-app-layout>
    <x-slot name="title">Rekap Absensi</x-slot>
    <x-slot name="header">
        <h1 class="text-display-sm text-surface-50">Rekap Absensi</h1>
        <p class="text-sm text-electric-200/80 mt-1">Laporan kehadiran siswa</p>
    </x-slot>

    <div class="space-y-6">
        {{-- Tab Switcher --}}
        <div class="card p-2 animate-fade-slide-up">
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('teacher.report', array_merge(request()->query(), ['tab' => 'detail'])) }}"
                   class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-250 {{ $tab === 'detail' ? 'bg-gradient-to-r from-navy-500 to-navy-600 text-white shadow-md' : 'text-navy-600 hover:bg-bw-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 0v.375"/></svg>
                    Rekap Absen
                </a>
                <a href="{{ route('teacher.report', array_merge(request()->query(), ['tab' => 'summary'])) }}"
                   class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-250 {{ $tab === 'summary' ? 'bg-gradient-to-r from-navy-500 to-navy-600 text-white shadow-md' : 'text-navy-600 hover:bg-bw-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                    Rekap Keterangan
                </a>
            </div>
        </div>

        @if ($tab === 'summary')
            {{-- Summary Filter --}}
            <div class="card animate-fade-slide-up stagger-1">
                <h3 class="font-semibold text-navy-800 mb-4">Filter Rekap Keterangan</h3>
                <form id="lwSummaryFilterForm" method="GET" action="{{ route('teacher.report') }}">
                    <input type="hidden" name="tab" value="summary">
                    <input type="hidden" name="summary_period" value="range">
                    <div class="filter-panel">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Kelas</label>
                                <x-expandable-select
                                    name="summary_class_room_id"
                                    :options="array_merge(
                                        [['value' => '', 'label' => 'Semua Kelas']],
                                        $classes->map(fn($c) => ['value' => $c->id, 'label' => $c->jurusan ? $c->name.' : '.$c->jurusan : $c->name])->toArray()
                                    )"
                                    :selected="(string) ($summaryFilter['class_room_id'] ?? '')"
                                    placeholder="Semua Kelas"
                                    onSelect="document.getElementById('lwSummaryFilterForm').submit()"
                                />
                            </div>
                            <div class="flex gap-2 flex-1">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Dari</label>
                                    <input name="summary_start_date" type="date" class="form-input-clean" style="height:38px; border-radius:10px; font-size:13px; padding-top:0; padding-bottom:0;" value="{{ $summaryFilter['start_date'] ?? now()->toDateString() }}" onchange="document.getElementById('lwSummaryFilterForm').submit()">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Sampai</label>
                                    <input name="summary_end_date" type="date" class="form-input-clean" style="height:38px; border-radius:10px; font-size:13px; padding-top:0; padding-bottom:0;" value="{{ $summaryFilter['end_date'] ?? now()->toDateString() }}" onchange="document.getElementById('lwSummaryFilterForm').submit()">
                                </div>
                            </div>
                            <div class="sm:w-auto flex items-end">
                                <a href="{{ route('teacher.report', ['tab' => 'summary']) }}" class="btn-secondary w-full sm:w-auto flex items-center justify-center gap-1" style="height:38px; min-height:38px; border-radius:10px; font-size:12px; padding:0 14px; white-space:nowrap;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183"/></svg>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Summary Table --}}
            <div class="table-wrapper animate-fade-slide-up stagger-2">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="table-header">
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Nama</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden sm:table-cell">Kelas</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Jurusan</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider">Hadir</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider">Izin</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider">Telat</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider">Alfa</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($summaryRows as $row)
                                <tr class="table-row">
                                    <td class="py-3 px-4 text-sm font-medium text-navy-800">{{ $row['Nama'] }}</td>
                                    <td class="py-3 px-4 text-sm text-navy-600 hidden sm:table-cell">{{ $row['Kelas'] }}</td>
                                    <td class="py-3 px-4 text-sm text-navy-600 hidden md:table-cell">{{ $row['Jurusan'] }}</td>
                                    <td class="py-3 px-4 text-sm text-center font-semibold text-emerald-600">{{ $row['Hadir'] }}</td>
                                    <td class="py-3 px-4 text-sm text-center font-semibold text-cyan-600">{{ $row['Izin'] }}</td>
                                    <td class="py-3 px-4 text-sm text-center font-semibold text-amber-600">{{ $row['Telat'] }}</td>
                                    <td class="py-3 px-4 text-sm text-center font-semibold text-red-600">{{ $row['Alfa'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-bw-400 text-sm">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            {{-- Detail Filter --}}
            <div class="card animate-fade-slide-up stagger-1">
                <h3 class="font-semibold text-navy-800 mb-4">Filter Rekap Absen</h3>
                <form id="lwDetailFilterForm" method="GET" action="{{ route('teacher.report') }}">
                    <input type="hidden" name="tab" value="detail">
                    <div class="filter-panel">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="sm:w-48">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Kelas</label>
                                <x-expandable-select
                                    name="class_room_id"
                                    :options="array_merge(
                                        [['value' => '', 'label' => 'Semua']],
                                        $classes->map(fn($c) => ['value' => $c->id, 'label' => $c->jurusan ? $c->name.' : '.$c->jurusan : $c->name])->toArray()
                                    )"
                                    :selected="(string) request('class_room_id', $classRoomId)"
                                    placeholder="Semua"
                                    onSelect="document.getElementById('lwDetailFilterForm').submit()"
                                />
                            </div>
                            <div class="sm:w-48">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Status</label>
                                <x-expandable-select
                                    name="status"
                                    :options="[
                                        ['value' => '', 'label' => 'Semua'],
                                        ['value' => 'present', 'label' => 'Hadir'],
                                        ['value' => 'late', 'label' => 'Terlambat'],
                                        ['value' => 'leave', 'label' => 'Izin'],
                                        ['value' => 'absent', 'label' => 'Alfa'],
                                    ]"
                                    :selected="(string) request('status', $status)"
                                    placeholder="Semua"
                                    onSelect="document.getElementById('lwDetailFilterForm').submit()"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 mt-3 sm:items-end">
                            <div class="flex gap-2 flex-1">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Dari</label>
                                    <input name="detail_start_date" type="date" class="form-input-clean" style="height:38px; border-radius:10px; font-size:13px; padding-top:0; padding-bottom:0;" value="{{ request('detail_start_date', $startDate->toDateString()) }}" onchange="document.getElementById('lwDetailFilterForm').submit()">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-bw-400 mb-1">Sampai</label>
                                    <input name="detail_end_date" type="date" class="form-input-clean" style="height:38px; border-radius:10px; font-size:13px; padding-top:0; padding-bottom:0;" value="{{ request('detail_end_date', $endDate->toDateString()) }}" onchange="document.getElementById('lwDetailFilterForm').submit()">
                                </div>
                            </div>
                            <div class="sm:w-auto">
                                <a href="{{ route('teacher.report', ['tab' => 'detail']) }}" class="btn-secondary w-full sm:w-auto flex items-center justify-center gap-1" style="height:38px; min-height:38px; border-radius:10px; font-size:12px; padding:0 14px; white-space:nowrap;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183"/></svg>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Export Buttons --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('teacher.report.excel', request()->query()) }}" class="btn-secondary gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Ekspor Excel
                    </a>
                    <a href="{{ route('teacher.report.pdf', request()->query()) }}" class="btn-secondary gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        Ekspor PDF
                    </a>
                </div>
            </div>

            {{-- Detail Table --}}
            <div class="table-wrapper animate-fade-slide-up stagger-2">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="table-header">
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Tanggal</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden sm:table-cell">Kelas</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Nama</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Masuk</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Pulang</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden lg:table-cell">Keterangan</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr class="table-row">
                                    <td class="py-3 px-4 text-sm text-navy-700">{{ $row['Tanggal'] }}</td>
                                    <td class="py-3 px-4 text-sm text-navy-600 hidden sm:table-cell">{{ $row['Kelas'] }}</td>
                                    <td class="py-3 px-4 text-sm font-medium text-navy-800">{{ $row['Nama'] }}</td>
                                    <td class="py-3 px-4">
                                        @php
                                            $statusLower = strtolower($row['Status']);
                                            if (str_starts_with($statusLower, 'hadir')) {
                                                $badgeStatus = 'present';
                                            } elseif (str_starts_with($statusLower, 'terlambat')) {
                                                $badgeStatus = 'late';
                                            } elseif (str_starts_with($statusLower, 'alfa')) {
                                                $badgeStatus = 'absent';
                                            } elseif (str_starts_with($statusLower, 'izin') || str_starts_with($statusLower, 'ijin')) {
                                                $badgeStatus = 'leave';
                                            } elseif (str_starts_with($statusLower, 'sakit')) {
                                                $badgeStatus = 'sick';
                                            } else {
                                                $badgeStatus = 'unknown';
                                            }
                                        @endphp
                                        <x-status-badge :status="$badgeStatus" :label="$row['Status']" />
                                    </td>
                                    <td class="py-3 px-4 text-sm tabular-nums text-navy-600 hidden md:table-cell">{{ $row['Masuk'] }}</td>
                                    <td class="py-3 px-4 text-sm tabular-nums text-navy-600 hidden md:table-cell">{{ $row['Pulang'] }}</td>
                                    <td class="py-3 px-4 text-sm text-navy-600 hidden lg:table-cell max-w-[200px] truncate">{{ $row['Keterangan Izin'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-bw-400 text-sm">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
