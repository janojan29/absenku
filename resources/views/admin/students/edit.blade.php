<x-app-layout>
    <x-slot name="title">Edit Data Siswa</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-display-sm text-surface-50">Edit Data Siswa</h1>
                <p class="text-sm text-electric-200/80 mt-1">Ubah data profil dan identitas siswa</p>
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
            <form method="POST" action="{{ route('admin.students.update', $student) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $student->name) }}" class="form-input-clean w-full" required>
                    </div>

                    <div>
                        <label for="jurusan" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Jurusan <span class="text-red-500">*</span></label>
                        <select id="jurusan" name="jurusan" class="form-select w-full" required>
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach ($jurusans as $jurusan)
                                <option value="{{ $jurusan }}" @selected(old('jurusan', $student->studentProfile?->jurusan ?? $student->studentProfile?->classRoom?->jurusan) === $jurusan)>
                                    {{ $jurusan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_room_id" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Kelas <span class="text-red-500">*</span></label>
                        <select id="class_room_id" name="class_room_id" class="form-select w-full" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}"
                                    data-jurusan="{{ $class->jurusan }}"
                                    @selected(old('class_room_id', $student->studentProfile?->class_room_id) == $class->id)>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="nis" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">NISN <span class="text-red-500">*</span></label>
                        <input type="text" id="nis" name="nis" value="{{ old('nis', $student->studentProfile?->nis) }}" class="form-input-clean w-full" placeholder="NISN" required>
                    </div>

                    <div>
                        <label for="whatsapp_number" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">No. WhatsApp Siswa</label>
                        <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $student->whatsapp_number) }}" class="form-input-clean w-full" placeholder="+628...">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="parent_phone_wa" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">No. WhatsApp Orang Tua</label>
                        <input type="text" id="parent_phone_wa" name="parent_phone_wa" value="{{ old('parent_phone_wa', $student->studentProfile?->parent_phone_wa) }}" class="form-input-clean w-full" placeholder="+628...">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-bw-200">
                    <a href="{{ route('admin.students.index') }}" class="btn-secondary px-6 h-10 flex items-center justify-center">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary btn-ripple px-6 h-10">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const jurusanSelect = document.getElementById('jurusan');
            const classSelect = document.getElementById('class_room_id');
            if (!jurusanSelect || !classSelect) return;

            const classOptions = Array.from(classSelect.options).slice(1);

            const filterClassOptions = function () {
                const selectedJurusan = jurusanSelect.value;
                const selectedClassId = classSelect.value;

                classOptions.forEach(function (option) {
                    option.hidden = !!selectedJurusan && option.dataset.jurusan !== selectedJurusan;
                });

                if (selectedClassId) {
                    const selectedOption = classOptions.find(function (option) {
                        return option.value === selectedClassId;
                    });

                    if (selectedOption && selectedOption.hidden) {
                        classSelect.value = '';
                    }
                }
            };

            jurusanSelect.addEventListener('change', function () {
                classSelect.value = '';
                filterClassOptions();
            });

            filterClassOptions();
        });
    </script>
</x-app-layout>
