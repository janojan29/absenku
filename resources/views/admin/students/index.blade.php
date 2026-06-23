<x-app-layout>
    <x-slot name="title">Data Siswa</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-display-sm text-surface-50">Data Siswa</h1>
                <p class="text-sm text-electric-200/80 mt-1">Kelola data profil dan identitas siswa</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <a href="{{ route('admin.students.create') }}" class="btn-primary btn-ripple h-10 px-5 gap-2 w-full sm:w-auto justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Siswa
                </a>
                <a href="{{ route('admin.students.import') }}" class="btn-primary btn-ripple h-10 px-5 gap-2 w-full sm:w-auto justify-center flex items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V3m0 0l-4.5 4.5M12 3l4.5 4.5M3 16.5v3A1.5 1.5 0 004.5 21h15a1.5 1.5 0 001.5-1.5v-3"/></svg>
                    Impor Data
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Bulk Class Update --}}
        <div class="card animate-fade-slide-up">
            <form method="POST" action="{{ route('admin.students.bulk-class') }}" class="filter-panel filter-form"
                  x-data="{ fromClassId: '', toClassId: '' }">
                @csrf
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <label class="text-xs text-bw-400 font-semibold uppercase tracking-wider">Kelas Asal</label>
                        <select name="from_class_room_id" class="form-select h-[42px] mt-1" x-model="fromClassId" required
                                @change="
                                    toClassId = '';
                                    const toSel = $refs.toClassSelect;
                                    while (toSel.options.length > 1) { toSel.remove(1); }
                                    const fromId = parseInt(fromClassId);
                                    if (!fromId) return;
                                    const allClasses = {{ Js::from($classes->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'jurusan' => trim($c->jurusan ?? '')])) }};
                                    const selected = allClasses.find(c => c.id === fromId);
                                    if (!selected) return;
                                    const filtered = allClasses.filter(c => c.id !== fromId && (c.jurusan || '').trim().toLowerCase() === (selected.jurusan || '').trim().toLowerCase());
                                    filtered.forEach(function(item) {
                                        const opt = document.createElement('option');
                                        opt.value = item.id;
                                        opt.textContent = item.name + ' \u2014 ' + (item.jurusan || '-');
                                        toSel.appendChild(opt);
                                    });
                                ">
                            <option value="">Pilih kelas asal</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }} — {{ $class->jurusan ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs text-bw-400 font-semibold uppercase tracking-wider">Kelas Tujuan</label>
                        <select name="to_class_room_id" class="form-select h-[42px] mt-1" x-model="toClassId" :disabled="!fromClassId" required x-ref="toClassSelect">
                            <option value="">Pilih kelas tujuan</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-auto">
                        <button type="submit" class="btn-primary btn-ripple h-[42px] px-6 w-full sm:w-auto">Pindahkan Semua</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Bulk Delete By Class --}}
        <div class="card animate-fade-slide-up">
            <form method="POST" action="{{ route('admin.students.bulk-delete') }}" class="filter-panel filter-form" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Hapus akun kelas', message: 'Hapus semua akun siswa pada kelas ini? Tindakan ini tidak dapat dibatalkan.', confirmText: 'Ya, hapus', cancelText: 'Batal', type: 'danger', formEl: this } }));">
                @csrf
                @method('DELETE')
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <label class="text-xs text-bw-400 font-semibold uppercase tracking-wider">Hapus 1 Kelas</label>
                        <select name="class_room_id" class="form-select h-[42px] mt-1" required>
                            <option value="">Pilih kelas</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }} — {{ $class->jurusan ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full sm:w-auto">
                        <button type="submit" class="btn-danger h-[42px] px-6 w-full sm:w-auto">Hapus Akun Kelas</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Livewire Search + Table --}}
        <livewire:admin.student-table />
    </div>
</x-app-layout>
