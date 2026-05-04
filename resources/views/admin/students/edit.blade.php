<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Data Siswa') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded mb-6">
                            <div class="font-semibold">Terjadi kesalahan:</div>
                            <ul class="list-disc pl-5 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.students.update', $student) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa</label>
                            <input type="text" value="{{ $student->name }}" class="w-full px-4 py-2 border border-gray-200 rounded-md bg-gray-100 text-gray-700" disabled>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="jurusan" class="block text-sm font-medium text-gray-700 mb-1">Jurusan <span class="text-red-500">*</span></label>
                                <select id="jurusan" name="jurusan" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="">-- Pilih Jurusan --</option>
                                    @foreach ($jurusans as $jurusan)
                                        <option value="{{ $jurusan }}" @selected(old('jurusan', $student->studentProfile?->jurusan ?? $student->studentProfile?->classRoom?->jurusan) === $jurusan)>
                                            {{ $jurusan }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('jurusan')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="class_room_id" class="block text-sm font-medium text-gray-700 mb-1">Kelas <span class="text-red-500">*</span></label>
                                <select id="class_room_id" name="class_room_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            data-jurusan="{{ $class->jurusan }}"
                                            @selected(old('class_room_id', $student->studentProfile?->class_room_id) == $class->id)>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_room_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="nis" class="block text-sm font-medium text-gray-700 mb-1">NISN</label>
                            <input type="text" id="nis" name="nis" value="{{ old('nis', $student->studentProfile?->nis) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="NISN">
                            @error('nis')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="parent_phone_wa" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp Orang Tua</label>
                            <input type="text" id="parent_phone_wa" name="parent_phone_wa" value="{{ old('parent_phone_wa', $student->studentProfile?->parent_phone_wa) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="+62812...">
                            @error('parent_phone_wa')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-6 border-t">
                            <a href="{{ route('admin.students.index') }}" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
                                Batal
                            </a>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
