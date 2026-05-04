<x-app-layout>
    <x-slot name="title">Data Kelas</x-slot>
    <x-slot name="header">
        <h1 class="text-display-sm text-surface-50">Manajemen Kelas</h1>
        <p class="text-sm text-electric-200/80 mt-1">Kelola data kelas dan jurusan</p>
    </x-slot>

    <div class="space-y-6">
        {{-- Add Class Form --}}
        <div class="card animate-fade-slide-up">
            <div class="font-semibold text-navy-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-navy-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Tambah Kelas Baru
            </div>
            <form method="POST" action="{{ route('admin.class-rooms.store') }}" class="flex flex-col sm:flex-row gap-3 items-end">
                @csrf
                <div class="flex-1 w-full">
                    <label class="block text-xs font-medium text-navy-600 mb-1">Nama Kelas <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="form-input-clean w-full" placeholder="Cth: X, XI, XII" value="{{ old('name') }}" required>
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-xs font-medium text-navy-600 mb-1">Jurusan <span class="text-red-500">*</span></label>
                    <input type="text" name="jurusan" class="form-input-clean w-full" placeholder="Cth: MIPA, IPS, TKJ" value="{{ old('jurusan') }}" required>
                </div>
                <button type="submit" class="btn-primary btn-ripple h-[42px] px-6 w-full sm:w-auto shrink-0">
                    Tambah
                </button>
            </form>
        </div>

        {{-- Class List Table --}}
        <div class="table-wrapper animate-fade-slide-up stagger-1">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="table-header">
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Kelas</th>
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Jurusan</th>
                            <th class="text-right py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $class)
                            <tr class="table-row">
                                <td class="py-3 px-4 font-semibold text-navy-800">{{ $class->name }}</td>
                                <td class="py-3 px-4 text-navy-600">{{ $class->jurusan }}</td>
                                <td class="py-3 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.class-rooms.destroy', $class) }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Hapus Kelas', message: 'Hapus kelas {{ $class->name }} {{ $class->jurusan }}? Tindakan ini tidak bisa dibatalkan.', confirmText: 'Ya, Hapus', type: 'danger', formEl: this } }));" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger h-8 px-3 text-xs gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-12 text-center text-bw-400">Belum ada data kelas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($classes->hasPages())
                <div class="mt-4 px-4 pb-4">
                    {{ $classes->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
