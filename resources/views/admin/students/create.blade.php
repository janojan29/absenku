<x-app-layout>
    <x-slot name="title">Tambah Siswa Baru</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-surface-50 leading-tight">
            {{ __('Tambah Siswa Baru') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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

                    <form method="POST" action="{{ route('admin.students.store') }}" class="space-y-6">
                        @csrf

                        <!-- Nama Siswa -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Siswa <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Masukkan nama siswa" required>
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" id="password" name="password" 
                                    class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Minimal 8 karakter" required>
                                <button type="button" onclick="togglePasswordVisibility('password', this)" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600 focus:outline-none" aria-label="Lihat password">
                                    <svg class="eye-open w-5 h-5" style="display:none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg class="eye-closed w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Konfirmasi Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" name="password_confirmation" 
                                    class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Ulangi password" required>
                                <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600 focus:outline-none" aria-label="Lihat konfirmasi password">
                                    <svg class="eye-open w-5 h-5" style="display:none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg class="eye-closed w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- NISN -->
                        <div>
                            <label for="nis" class="block text-sm font-medium text-gray-700 mb-1">
                                NISN <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nis" name="nis" value="{{ old('nis') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Nomor Induk Siswa Nasional" required>
                            @error('nis')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="jurusan" class="block text-sm font-medium text-gray-700 mb-1">
                                    Jurusan <span class="text-red-500">*</span>
                                </label>
                                <select id="jurusan" name="jurusan"
                                    class="select-stable-text w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required>
                                    <option value="">-- Pilih Jurusan --</option>
                                    @foreach ($jurusans as $jurusan)
                                        <option value="{{ $jurusan }}" @selected(old('jurusan') === $jurusan)>
                                            {{ $jurusan }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('jurusan')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="class_room_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Kelas <span class="text-red-500">*</span>
                                </label>
                                <select id="class_room_id" name="class_room_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            data-jurusan="{{ $class->jurusan }}"
                                            @selected(old('class_room_id') == $class->id)>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_room_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- No. WhatsApp Siswa -->
                        <div>
                            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">
                                No. WhatsApp Siswa
                            </label>
                            <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="+62812...">
                            @error('whatsapp_number')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- No. WhatsApp Orang Tua -->
                        <div>
                            <label for="parent_phone_wa" class="block text-sm font-medium text-gray-700 mb-1">
                                No. WhatsApp Orang Tua
                            </label>
                            <input type="text" id="parent_phone_wa" name="parent_phone_wa" value="{{ old('parent_phone_wa') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="+62812...">
                            @error('parent_phone_wa')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t">
                            <a href="{{ route('admin.students.index') }}" 
                                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
                                Batal
                            </a>
                            <button type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                                Tambah Siswa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePasswordVisibility = function (inputId, button) {
                const input = document.getElementById(inputId);
                if (!input) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');

                const eyeOpen = button.querySelector('.eye-open');
                const eyeClosed = button.querySelector('.eye-closed');
                if (eyeOpen && eyeClosed) {
                    eyeOpen.style.display = isPassword ? '' : 'none';
                    eyeClosed.style.display = isPassword ? 'none' : '';
                }
            };

            window.togglePasswordVisibility = togglePasswordVisibility;

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
