<x-app-layout>
    <x-slot name="title">Edit Data Guru</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-display-sm text-surface-50">Edit Data Guru</h1>
                <p class="text-sm text-electric-200/80 mt-1">Ubah data profil dan peran guru</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl">
                <div class="font-semibold text-sm">Terjadi kesalahan:</div>
                <ul class="list-disc pl-5 mt-1.5 text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card p-6 animate-fade-slide-up">
            <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="teacher_role" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Jenis Guru <span class="text-red-500">*</span></label>
                        <select id="teacher_role" name="teacher_role" class="form-select w-full" required>
                            <option value="guru" {{ old('teacher_role', $teacher->hasRole('guru_walikelas') ? 'guru_walikelas' : 'guru') === 'guru' ? 'selected' : '' }}>Guru</option>
                            <option value="guru_walikelas" {{ old('teacher_role', $teacher->hasRole('guru_walikelas') ? 'guru_walikelas' : 'guru') === 'guru_walikelas' ? 'selected' : '' }}>Guru Walikelas</option>
                        </select>
                    </div>

                    <div>
                        <label for="name" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Nama Guru <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $teacher->name) }}" class="form-input-clean w-full" required>
                    </div>

                    <div>
                        <label for="nip" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">NIP <span class="text-red-500">*</span></label>
                        <input type="text" id="nip" name="nip" value="{{ old('nip', $teacher->teacher?->nip) }}" class="form-input-clean w-full" placeholder="NIP" required>
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Mata Pelajaran</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject', $teacher->teacher?->subject) }}" class="form-input-clean w-full" placeholder="Mata Pelajaran">
                    </div>

                    <div>
                        <label for="whatsapp_number" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">No. WhatsApp</label>
                        <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $teacher->whatsapp_number) }}" class="form-input-clean w-full" placeholder="+628...">
                    </div>

                    <div>
                        <label for="wali_kelas" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Keterangan Wali Kelas</label>
                        <input type="text" id="wali_kelas" name="wali_kelas" value="{{ old('wali_kelas', $teacher->teacher?->wali_kelas) }}" class="form-input-clean w-full" placeholder="Contoh: X IPA 1">
                        <p class="text-bw-400 text-[10px] mt-1">Wajib diisi jika jenis guru adalah Guru Walikelas.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-bw-200">
                    <a href="{{ route('admin.teachers.index') }}" class="btn-secondary px-6 h-10 flex items-center justify-center">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary btn-ripple px-6 h-10">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
