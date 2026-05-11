<div wire:poll.5s class="space-y-6">
    {{-- Error & Status Messages --}}
    @if ($errors->any())
        <div class="card border-accent-danger/30 bg-red-50/80 animate-fade-slide-up">
            <div class="flex items-center gap-2 font-semibold text-red-700 text-sm mb-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                Terjadi kesalahan
            </div>
            <ul class="list-disc pl-5 text-sm text-red-600">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div x-data x-init="$dispatch('toast', { message: '{{ session('status') }}', type: 'success' })" class="hidden"></div>
    @endif

    {{-- Pending Section --}}
    <div class="card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-navy-800 flex items-center gap-2">
                <div class="live-dot"></div>
                Antrian Pending
            </h3>
            <span class="text-xs text-electric-200/80 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 animate-spin-smooth" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Auto-refresh 5s
            </span>
        </div>

        @forelse ($leaveRequests as $leave)
            <div class="group relative p-4 rounded-xl border border-bw-200/80 hover:border-navy-200 bg-bw-50/50 hover:bg-white mb-3 last:mb-0 transition-all duration-250"
                 style="border-left: 4px solid {{ $leave->type === 'absent' ? '#06b6d4' : '#f59e0b' }};"
                 x-data="{ expanded: false }">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    {{-- Avatar + Info --}}
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-navy-500/20 to-navy-600/20 flex items-center justify-center shrink-0">
                            <span class="text-sm font-bold text-navy-600">{{ strtoupper(substr($leave->user->name, 0, 1)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-navy-800 text-sm truncate">{{ $leave->user->name }}</div>
                            <div class="text-xs text-bw-400">
                                {{ $leave->user->studentProfile?->classRoom?->name ?? '-' }} · {{ $leave->date->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>

                    {{-- Type Badge --}}
                    <div class="flex items-center gap-2">
                        <span class="badge {{ $leave->type === 'absent' ? 'badge-ijin' : 'badge-terlambat' }}">
                            <span class="badge-dot"></span>
                            {{ $leave->type === 'absent' ? 'Tidak Masuk' : 'Pulang Awal' }}
                        </span>
                    </div>
                </div>

                {{-- Reason --}}
                <div class="mt-3 text-sm text-navy-600">
                    <span class="font-medium text-navy-700">Alasan:</span>
                    {{ $leave->reason === 'urgent' ? 'Urusan Penting/Mendadak' : ($leave->reason === 'sick' ? 'Sakit' : $leave->reason) }}
                </div>

                {{-- Keterangan (expandable) --}}
                @if($leave->keterangan)
                    <div class="mt-2">
                        <button @click="expanded = !expanded" class="text-xs text-navy-400 hover:text-navy-600 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-90': expanded }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                            Keterangan
                        </button>
                        <div x-show="expanded" x-transition class="mt-2 p-3 rounded-lg bg-bw-100 text-sm text-navy-600">
                            {{ $leave->keterangan }}
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 mt-4">
                    <form method="POST" action="{{ route('picket.leave-requests.approve', $leave) }}" class="flex-1">
                        @csrf
                        <button type="submit" class="btn-success btn-ripple w-full h-10 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Terima
                        </button>
                    </form>
                    <form method="POST" action="{{ route('picket.leave-requests.reject', $leave) }}" class="flex-1">
                        @csrf
                        <button type="submit" class="btn-danger btn-ripple w-full h-10 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            Tolak
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="py-12 text-center">
                <svg class="w-16 h-16 mx-auto text-bw-300 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                <p class="text-bw-400 font-medium">Tidak ada pengajuan pending</p>
                <p class="text-xs text-bw-300 mt-1">Semua pengajuan sudah diproses</p>
            </div>
        @endforelse

        <div class="mt-4">{{ $leaveRequests->links() }}</div>
    </div>

    {{-- History Section --}}
    <div class="card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-navy-800">Riwayat Ijin</h3>
            <span class="text-xs text-electric-200/80">Pengajuan yang sudah diproses</span>
        </div>

        <div class="table-wrapper -mx-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="table-header">
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Tanggal</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Siswa</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden sm:table-cell">Kelas</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Jenis</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden lg:table-cell">Diproses</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider hidden lg:table-cell">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaveHistories as $leave)
                            <tr class="table-row">
                                <td class="py-3 px-4 text-sm text-navy-700">{{ $leave->date->format('d/m/Y') }}</td>
                                <td class="py-3 px-4 text-sm font-medium text-navy-800">{{ $leave->user->name }}</td>
                                <td class="py-3 px-4 text-sm text-navy-600 hidden sm:table-cell">{{ $leave->user->studentProfile?->classRoom?->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-sm text-navy-600 hidden md:table-cell">{{ $leave->type === 'absent' ? 'Tidak Masuk' : 'Pulang Awal' }}</td>
                                <td class="py-3 px-4"><x-status-badge :status="$leave->status" /></td>
                                <td class="py-3 px-4 text-sm text-navy-600 hidden lg:table-cell">{{ optional($leave->decided_at)->format('d/m H:i') ?? '-' }}</td>
                                <td class="py-3 px-4 text-sm text-navy-600 hidden lg:table-cell">{{ $leave->decidedBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-bw-400 text-sm">Belum ada riwayat ijin.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $leaveHistories->links() }}</div>
    </div>
</div>
