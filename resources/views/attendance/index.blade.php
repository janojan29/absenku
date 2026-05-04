<x-app-layout>
    <x-slot name="title">Absensi</x-slot>
    <x-slot name="header">
        <div class="hidden lg:block">
            <h1 class="text-display-sm text-surface-50">Absensi Harian</h1>
            <p class="text-sm text-electric-200/80 mt-1">Lakukan absensi masuk dan pulang dari lokasi sekolah</p>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto space-y-6"
         x-data="geolocation({{ $setting->latitude }}, {{ $setting->longitude }}, {{ $setting->radius_meters }})">

        <div class="animate-fade-slide-up px-1">
            <div class="text-xl font-bold text-white tracking-tight">{{ auth()->user()->name }}</div>
            <div class="text-lg font-semibold text-white/90">{{ auth()->user()->studentProfile?->classRoom?->name ?? '-' }}</div>
        </div>

        {{-- Leave Submission Status --}}
        @if (!empty($todayLeaveSubmission))
            @php
                $leaveStatus = $todayLeaveSubmission->status;
                $leaveStatusLabel = $leaveStatus === 'approved' ? 'Disetujui' : ($leaveStatus === 'rejected' ? 'Ditolak' : 'Menunggu ACC');
                $lsBadge = $leaveStatus === 'approved' ? 'badge-hadir' : ($leaveStatus === 'rejected' ? 'badge-alfa' : 'badge-terlambat');
            @endphp
            <div class="card animate-fade-slide-up">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-accent-info" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    <span class="text-sm font-semibold text-navy-800">Pengajuan Ijin Hari Ini</span>
                    <x-status-badge :status="$leaveStatus" />
                </div>
                <p class="text-sm text-navy-600">Tanggal {{ optional($todayLeaveSubmission->date)->format('d/m/Y') ?? '-' }}</p>
                @if (!empty($todayLeaveSubmission->decision_note))
                    <p class="text-xs text-bw-400 mt-1">Catatan: {{ $todayLeaveSubmission->decision_note }}</p>
                @endif
                @if (!$showLeaveForm)
                    <p class="text-xs text-bw-400 mt-2">Form perijinan muncul kembali mulai jam pulang ({{ substr($setting->check_out_start_time, 0, 5) }}).</p>
                @endif
            </div>
        @endif

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="card border-accent-danger/30 bg-red-50/80 animate-fade-slide-up">
                <div class="flex items-center gap-2 font-semibold text-red-700 text-sm mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    Terjadi kesalahan
                </div>
                <ul class="list-disc pl-5 text-sm text-red-600 space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Main Attendance Card --}}
        <div class="overflow-hidden rounded-2xl shadow-lg animate-fade-slide-up stagger-1" x-data="clock()">
            {{-- Card Header with Clock --}}
            <div class="relative px-6 py-8 text-center text-white overflow-hidden" style="background: linear-gradient(135deg, #0d1b2a 0%, #1e4d8c 50%, #2563b8 100%);">
                {{-- Wave decoration --}}
                <svg class="absolute bottom-0 left-0 w-full" viewBox="0 0 400 40" preserveAspectRatio="none" style="height:40px;">
                    <path d="M0,20 Q50,0 100,20 T200,20 T300,20 T400,20 L400,40 L0,40 Z" fill="rgba(255,255,255,0.06)" class="animate-wave-move" style="animation: waveMove 6s ease-in-out infinite;"/>
                    <path d="M0,25 Q50,10 100,25 T200,25 T300,25 T400,25 L400,40 L0,40 Z" fill="rgba(255,255,255,0.04)"/>
                </svg>

                <div class="relative z-10">
                    <div class="text-5xl font-bold tabular-nums tracking-tight font-mono" x-text="digitalClock">--:--:--</div>
                    <div class="text-navy-300 text-sm mt-2" x-text="date">Memuat...</div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="bg-white p-6 space-y-5">
                {{-- Location Status --}}
                <div class="flex items-center gap-3 p-4 rounded-xl transition-all duration-300"
                     :class="isLoading ? 'bg-bw-100' : (isInRange ? 'bg-emerald-50 border border-emerald-200/60' : 'bg-red-50 border border-red-200/60')">
                    <div class="shrink-0">
                        <template x-if="isLoading">
                            <div class="w-10 h-10 rounded-full bg-bw-200 flex items-center justify-center">
                                <svg class="w-5 h-5 text-bw-400 animate-spin-smooth" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </div>
                        </template>
                        <template x-if="!isLoading && isInRange">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center animate-pulse-glow-green">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </div>
                        </template>
                        <template x-if="!isLoading && !isInRange && !error">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </div>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold" :class="isInRange ? 'text-emerald-700' : 'text-red-700'" x-text="statusText">Mendeteksi lokasi...</div>
                        <div class="text-xs text-bw-400 mt-0.5" x-show="distance !== null">Jarak: <span x-text="distanceText"></span> · Radius: {{ $setting->radius_meters }}m</div>
                    </div>
                </div>

                {{-- Attendance Buttons --}}
                @if (!empty($hasApprovedAbsentLeaveToday) && $hasApprovedAbsentLeaveToday)
                    <div class="text-center p-5 rounded-xl bg-sky-50 border border-sky-200/70">
                        <svg class="w-10 h-10 mx-auto text-sky-500 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <div class="font-semibold text-sky-700">Ijin Tidak Masuk Disetujui</div>
                        <div class="text-sm text-sky-600 mt-1">Absensi hari ini dikunci otomatis sebagai ijin.</div>
                    </div>
                @elseif (!$attendance?->check_in_at)
                    @if ($canCheckInNow)
                        <form method="POST" action="{{ route('attendance.check-in') }}" x-data="{ submitting: false }" @submit.prevent="submitting = true; attachAndSubmit($event, $el)">
                            @csrf
                            <input type="hidden" name="latitude" value="">
                            <input type="hidden" name="longitude" value="">
                            <button type="submit" :disabled="submitting" class="btn-primary btn-ripple w-full h-14 text-lg">
                                <template x-if="!submitting"><span class="flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>Absen Masuk</span></template>
                                <template x-if="submitting"><span class="flex items-center gap-2"><svg class="w-5 h-5 animate-spin-smooth" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Memproses...</span></template>
                            </button>
                        </form>
                    @elseif ($isAfterCheckInEnd)
                        <button disabled class="w-full h-14 text-lg rounded-xl font-semibold bg-bw-200 text-bw-400 cursor-not-allowed">Absen Masuk ditutup (batas {{ substr($setting->check_in_end_time, 0, 5) }})</button>
                    @else
                        <button disabled class="w-full h-14 text-lg rounded-xl font-semibold bg-bw-200 text-bw-400 cursor-not-allowed">Absen Masuk (buka {{ substr($setting->check_in_start_time, 0, 5) }})</button>
                    @endif
                @elseif (!$attendance?->check_out_at)
                    @if ($canCheckOutNow)
                        <form method="POST" action="{{ route('attendance.check-out') }}" x-data="{ submitting: false }" @submit.prevent="submitting = true; attachAndSubmit($event, $el)">
                            @csrf
                            <input type="hidden" name="latitude" value="">
                            <input type="hidden" name="longitude" value="">
                            <button type="submit" :disabled="submitting" class="w-full h-14 text-lg rounded-xl font-semibold text-white btn-ripple transition-all duration-350 ease-bounce-in hover:translate-y-[-2px]" style="background: linear-gradient(135deg, #1a2744, #1e3a5f);">
                                <template x-if="!submitting"><span class="flex items-center justify-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"/></svg>Absen Pulang</span></template>
                                <template x-if="submitting"><span class="flex items-center justify-center gap-2"><svg class="w-5 h-5 animate-spin-smooth" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Memproses...</span></template>
                            </button>
                        </form>
                    @elseif (!empty($isAfterCheckOutEnd) && $isAfterCheckOutEnd)
                        <button disabled class="w-full h-14 text-lg rounded-xl font-semibold bg-bw-200 text-bw-400 cursor-not-allowed">Check-out ditutup (batas {{ substr($setting->check_out_end_time, 0, 5) }})</button>
                    @else
                        <button disabled class="w-full h-14 text-lg rounded-xl font-semibold bg-bw-200 text-bw-400 cursor-not-allowed">Absen Pulang (buka {{ substr($setting->check_out_start_time, 0, 5) }})</button>
                    @endif
                @else
                    <div class="text-center p-6 rounded-xl bg-emerald-50 border border-emerald-200/60">
                        <svg class="w-12 h-12 mx-auto text-emerald-500 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <div class="font-semibold text-emerald-700">Absensi Selesai</div>
                        <div class="text-sm text-emerald-600 mt-1">Sudah check-in & check-out hari ini</div>
                    </div>
                @endif

                {{-- Today's Status --}}
                <div class="flex items-center justify-between p-4 rounded-xl bg-bw-100/60">
                    <div class="text-sm">
                        <span class="text-bw-400">Masuk:</span>
                        <span class="font-semibold text-navy-800 ml-1">{{ optional($attendance?->check_in_at)->format('H:i') ?? '—' }}</span>
                    </div>
                    <div class="w-px h-6 bg-bw-300"></div>
                    <div class="text-sm">
                        <span class="text-bw-400">Pulang:</span>
                        <span class="font-semibold text-navy-800 ml-1">{{ optional($attendance?->check_out_at)->format('H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Leave Request Form --}}
        @if ($showLeaveForm)
        <div id="ijin" class="card animate-fade-slide-up stagger-2">
            <div class="flex items-center gap-2 mb-5">
                <svg class="w-5 h-5 text-accent-info" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                <h3 class="font-semibold text-navy-800">Pengajuan Ijin</h3>
            </div>
            <form method="POST" action="{{ route('leave-requests.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="type" class="block text-sm font-medium text-navy-700 mb-1.5">Jenis Ijin</label>
                    <select id="type" name="type" class="form-select" required>
                        <option value="absent" @selected(old('type', 'absent') === 'absent')>Ijin Tidak Masuk</option>
                        <option value="early_leave" @selected(old('type') === 'early_leave')>Ijin Pulang Lebih Awal</option>
                    </select>
                </div>
                <div id="leave-date-wrapper">
                    <label for="leave_date" class="block text-sm font-medium text-navy-700 mb-1.5">Waktu Ijin</label>
                    <select id="leave_date" name="leave_date" class="form-select">
                        <option value="{{ now()->toDateString() }}" @selected(old('leave_date', now()->toDateString()) === now()->toDateString())>Hari Ini ({{ now()->format('d/m/Y') }})</option>
                        <option value="{{ now()->copy()->addDay()->toDateString() }}" @selected(old('leave_date') === now()->copy()->addDay()->toDateString())>Besok ({{ now()->copy()->addDay()->format('d/m/Y') }})</option>
                    </select>
                    <p class="text-xs text-bw-400 mt-1">1 hari hanya boleh 1 kali pengajuan ijin.</p>
                </div>
                <div>
                    <label for="reason" class="block text-sm font-medium text-navy-700 mb-1.5">Alasan</label>
                    <select id="reason" name="reason" class="form-select" required>
                        <option value="">-- Pilih Alasan --</option>
                        <option value="urgent" @selected(old('reason') === 'urgent')>Urusan Penting/Mendadak</option>
                        <option value="sick" @selected(old('reason') === 'sick')>Sakit</option>
                    </select>
                </div>
                <div>
                    <label for="keterangan" class="block text-sm font-medium text-navy-700 mb-1.5">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3" class="form-input-clean" required placeholder="Jelaskan alasan ijin...">{{ old('keterangan') }}</textarea>
                </div>
                <div class="space-y-2">
                    <p id="leave-submit-warning" class="text-xs text-red-600 hidden"></p>
                    <button type="submit" id="leave-submit-button" class="btn-primary btn-ripple w-full h-12">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Attendance History --}}
        <div id="riwayat" class="card animate-fade-slide-up stagger-3">
            <h3 class="font-semibold text-navy-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-navy-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Riwayat 14 Hari
            </h3>
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs uppercase tracking-wider text-bw-400 border-b border-bw-200">
                        <th class="pb-3 pr-4 font-semibold">Tanggal</th>
                        <th class="pb-3 pr-4 font-semibold">Masuk</th>
                        <th class="pb-3 font-semibold">Pulang</th>
                    </tr></thead>
                    <tbody class="divide-y divide-bw-200/60">
                        @forelse ($recent as $row)
                        <tr class="hover:bg-navy-500/[0.03] transition-colors duration-150">
                            <td class="py-3 pr-4 font-medium text-navy-700">{{ $row->date->format('d/m/Y') }}</td>
                            <td class="py-3 pr-4 tabular-nums text-navy-600">{{ optional($row->check_in_at)->format('H:i') ?? '—' }}</td>
                            <td class="py-3 tabular-nums text-navy-600">{{ optional($row->check_out_at)->format('H:i') ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="py-8 text-center text-bw-400">Belum ada data absensi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const typeSelect = document.getElementById('type');
            const leaveDateWrapper = document.getElementById('leave-date-wrapper');
            const leaveDateInput = document.getElementById('leave_date');
            const submitButton = document.getElementById('leave-submit-button');
            const submitWarning = document.getElementById('leave-submit-warning');
            const blockedDates = @json($leaveDatesWithSubmission ?? []);
            if (!typeSelect || !leaveDateWrapper || !leaveDateInput || !submitButton || !submitWarning) return;
            const syncLeaveDateVisibility = () => {
                const isAbsent = typeSelect.value === 'absent';
                leaveDateWrapper.style.display = isAbsent ? '' : 'none';
                leaveDateInput.disabled = !isAbsent;
                leaveDateInput.required = isAbsent;
                let targetDate = new Date().toISOString().slice(0, 10);
                if (isAbsent) targetDate = leaveDateInput.value;
                const isBlocked = blockedDates.includes(targetDate);
                submitButton.disabled = isBlocked;
                submitButton.classList.toggle('opacity-50', isBlocked);
                submitButton.classList.toggle('cursor-not-allowed', isBlocked);
                if (isBlocked) { submitWarning.textContent = 'Pengajuan ijin untuk tanggal ini sudah ada.'; submitWarning.classList.remove('hidden'); }
                else { submitWarning.classList.add('hidden'); submitWarning.textContent = ''; }
            };
            typeSelect.addEventListener('change', syncLeaveDateVisibility);
            leaveDateInput.addEventListener('change', syncLeaveDateVisibility);
            syncLeaveDateVisibility();
        })();
    </script>
</x-app-layout>
