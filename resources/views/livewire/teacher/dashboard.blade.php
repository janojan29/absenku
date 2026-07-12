<div class="space-y-6">
    <div class="card animate-fade-slide-up relative z-10">
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
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">Izin</p>
            <p class="text-2xl font-bold text-cyan-600 mt-1">{{ $counts['leave'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-semibold uppercase tracking-wider text-bw-400">{{ $isCheckInClosed ? 'Alfa' : 'Belum Absen' }}</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $counts['unknown'] }}</p>
        </div>
    </div>

    <div class="table-wrapper animate-fade-slide-up stagger-2">
        <div class="px-4 py-4 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-bw-100">
            <h4 class="font-bold text-navy-800 text-lg">Daftar Kehadiran Siswa ({{ $students->total() }})</h4>
            
            <div class="w-full sm:w-72">
                <div style="position: relative; display: flex; align-items: center;">
                    <svg style="position: absolute; left: 14px; width: 18px; height: 18px; pointer-events: none; color: #94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari nama siswa..." style="padding-left: 42px; padding-right: 14px; padding-top: 10px; padding-bottom: 10px; width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; color: #1e293b; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#334155'; this.style.boxShadow='0 0 0 3px rgba(51,65,85,0.1)'" onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header">
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Nama</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden sm:table-cell">Kelas</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Masuk</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Pulang</th>
                        <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden lg:table-cell">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $status = $effectiveStatuses[$student->user_id] ?? 'unknown';
                            $label = $statusLabels[$student->user_id] ?? null;
                            $attendance = $attendances->get($student->user_id);
                        @endphp
                        <tr class="table-row group hover:bg-navy-50/50 transition-colors duration-200 relative" wire:key="student-{{ $student->id }}">
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
                            <td class="py-3 px-4 hidden lg:table-cell text-center">
                                <button
                                    wire:click="openReportModal({{ $student->id }}, '{{ addslashes($student->user?->name ?? '-') }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 bg-red-50 border border-red-200/60 hover:bg-red-100 hover:border-red-300 transition-all duration-200 opacity-0 group-hover:opacity-100"
                                    title="Laporkan siswa tidak hadir di kelas"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Laporkan
                                </button>
                            </td>
                            {{-- Mobile: tap the whole row --}}
                            <td class="lg:hidden absolute inset-0 cursor-pointer" wire:click="openReportModal({{ $student->id }}, '{{ addslashes($student->user?->name ?? '-') }}')"></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-bw-400 text-sm">Tidak ada data siswa.</td>
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

    {{-- ═══════════════════════════════════════════
         REPORT MODAL — Laporkan Ketidakhadiran
    ═══════════════════════════════════════════ --}}
    @if($showReportModal)
    @teleport('body')
    <div
        x-data="{ open: false }"
        x-init="$nextTick(() => { open = true; document.body.style.overflow = 'hidden' })"
        @keydown.escape.window="$wire.closeReportModal(); document.body.style.overflow = ''"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-space-900/80 backdrop-blur-lg"
            wire:click="closeReportModal"
            x-on:click="document.body.style.overflow = ''"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        ></div>

        {{-- Modal Panel --}}
        <div
            class="relative w-full max-w-md"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        >
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-bw-200" style="box-shadow: 0 25px 80px -12px rgba(0,0,0,0.5), 0 0 40px rgba(239,68,68,0.08);">

                {{-- Gradient Top Accent --}}
                <div class="h-1 w-full bg-gradient-to-r from-red-500 via-red-400 to-orange-400"></div>

                {{-- Header --}}
                <div class="px-6 pt-6 pb-5 border-b border-bw-100">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg" style="box-shadow: 0 4px 20px rgba(239,68,68,0.3);">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-navy-900 leading-tight">Laporkan Ketidakhadiran</h3>
                                <p class="text-xs text-bw-400 mt-0.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                    Notifikasi otomatis ke WhatsApp Orang Tua
                                </p>
                            </div>
                        </div>
                        <button wire:click="closeReportModal" x-on:click="document.body.style.overflow = ''" class="text-bw-400 hover:text-navy-900 hover:bg-bw-100 rounded-xl p-2 transition-all duration-200 -mr-1 -mt-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit.prevent="submitReport" class="p-6">

                    {{-- Student Info --}}
                    <div class="mb-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-bw-400 mb-2">Siswa yang Dilaporkan</p>
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-navy-200 bg-gradient-to-r from-navy-50 to-navy-100/50">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-navy-500 to-navy-700 text-white flex items-center justify-center font-bold text-base shadow-md shrink-0">
                                {{ strtoupper(substr($reportStudentName, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-navy-900 truncate">{{ $reportStudentName }}</p>
                                <p class="text-[11px] text-navy-500 mt-0.5">Klik nama lain di tabel untuk mengganti</p>
                            </div>
                        </div>
                    </div>

                    {{-- Form Fields --}}
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-navy-700 mb-2">
                                Mata Pelajaran <span class="text-red-500 text-[10px] normal-case font-normal ml-1">wajib diisi</span>
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-[18px] h-[18px] text-bw-300 group-focus-within:text-navy-500 transition-colors" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                                </div>
                                <input
                                    type="text"
                                    wire:model.defer="reportSubject"
                                    class="form-input !rounded-xl !border-bw-200 focus:!border-navy-500 focus:!ring-2 focus:!ring-navy-500/15 !bg-white !shadow-none"
                                    placeholder="Contoh: Matematika, Fisika, dll..."
                                    required
                                >
                            </div>
                            @error('reportSubject')
                                <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-navy-700 mb-2">
                                Keterangan <span class="text-bw-300 text-[10px] normal-case font-normal ml-1">opsional</span>
                            </label>
                            <textarea
                                wire:model.defer="reportDescription"
                                class="form-input-clean !rounded-xl !border-bw-200 focus:!border-navy-500 focus:!ring-2 focus:!ring-navy-500/15 !bg-white !shadow-none resize-none"
                                rows="3"
                                placeholder="Contoh: Siswa izin ke toilet tapi tidak kembali hingga jam pelajaran selesai..."
                            ></textarea>
                            @error('reportDescription')
                                <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-5 border-t border-bw-100">
                        <button type="button" wire:click="closeReportModal" x-on:click="document.body.style.overflow = ''" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-navy-700 bg-bw-100 hover:bg-bw-200 border border-bw-200 transition-all duration-200">
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 transition-all duration-200 flex items-center gap-2.5 shadow-lg hover:shadow-xl"
                            style="box-shadow: 0 4px 14px rgba(239,68,68,0.3);"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-wait"
                        >
                            <span wire:loading.remove wire:target="submitReport" class="flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                Kirim Laporan
                            </span>
                            <span wire:loading wire:target="submitReport" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Mengirim...
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    @endteleport
    @endif

    {{-- Success Toast --}}
    @if(session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="fixed bottom-6 right-6 z-[10000] px-5 py-3.5 bg-emerald-600 text-white rounded-xl shadow-lg flex items-center gap-3"
             style="box-shadow: 0 8px 30px rgba(16,185,129,0.35);">
            <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif
</div>
