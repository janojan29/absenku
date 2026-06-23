<div class="space-y-6">
    {{-- Search --}}
    <div class="card animate-fade-slide-up">
        <div class="filter-panel">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="filter-search-wrap flex-1">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text" class="filter-input" placeholder="Cari nama, email, NISN, NIP...">
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrapper animate-fade-slide-up stagger-1">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="table-header">
                        <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Pengguna</th>
                        <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Peran</th>
                        <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Identitas</th>
                        <th class="text-right py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="table-row">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-navy-500/20 to-navy-600/20 flex items-center justify-center shrink-0">
                                        <span class="text-sm font-bold text-navy-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-navy-800 text-sm truncate">{{ $user->name }}</div>
                                        <div class="text-xs text-bw-400 truncate">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $roleName = $user->getRoleNames()->first();
                                    $roleLabel = match ($roleName) {
                                        'petugas_piket' => 'Petugas Piket',
                                        'guru_walikelas' => 'Guru Walikelas',
                                        default => ucfirst(str_replace('_', ' ', $roleName ?? '-')),
                                    };
                                    $badgeClass = match($roleName) {
                                        'siswa' => 'badge-hadir',
                                        'guru', 'guru_walikelas' => 'badge-terlambat',
                                        'admin' => 'bg-navy-100 text-navy-700 border-navy-200',
                                        default => 'badge-izin'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $roleLabel }}</span>
                            </td>
                            <td class="py-3 px-4 hidden md:table-cell">
                                @if ($user->studentProfile)
                                    <div class="text-sm text-navy-800">NISN: <span class="font-medium">{{ $user->studentProfile->nis ?? '-' }}</span></div>
                                    <div class="text-xs text-bw-400">{{ $user->studentProfile->classRoom?->name ?? '-' }}</div>
                                @elseif ($user->teacher && $user->hasAnyRole(['guru', 'guru_walikelas']))
                                    <div class="text-sm text-navy-800">NIP: <span class="font-medium">{{ $user->teacher->nip ?? '-' }}</span></div>
                                    <div class="text-xs text-bw-400">{{ $user->teacher->subject ?? '-' }}</div>
                                @else
                                    <span class="text-bw-300">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                @php
                                    $canDelete = in_array($roleName, ['guru', 'guru_walikelas', 'siswa'], true) && auth()->id() !== $user->id;
                                @endphp
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" onclick="openEditModal(event, {{ $user->id }})" class="btn-secondary h-8 px-3 text-xs gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.89.11l-3.15-.8a.8.8 0 0 1-.505-.505l.8-3.15a4.5 4.5 0 0 1 1.11-1.89l12.42-12.42Zm8.25-2.257a2.25 2.25 0 0 1-3.182 0l-1.06-1.06a2.25 2.25 0 0 1 0-3.182l1.06-1.06a2.25 2.25 0 0 1 3.182 0l1.06 1.06a2.25 2.25 0 0 1 0 3.182l-1.06 1.06Zm-2.25 2.257-2.12-2.12"/></svg>
                                        Edit
                                    </button>
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Hapus Pengguna', message: 'Hapus pengguna ini? Tindakan ini tidak bisa dibatalkan.', confirmText: 'Ya, Hapus', type: 'danger', formEl: this } }));" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger h-8 px-3 text-xs gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-bw-400">
                            @if($search) Tidak ada pengguna yang cocok. @else Tidak ada data pengguna. @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="mt-4 px-4 pb-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
