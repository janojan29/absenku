<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-surface-50 leading-tight">
            {{ __('Tambah Guru Baru') }}
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

                    <form method="POST" action="{{ route('admin.teachers.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="teacher_role" class="block text-sm font-medium text-gray-700 mb-1">
                                Jenis Guru <span class="text-red-500">*</span>
                            </label>
                            <select id="teacher_role" name="teacher_role" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="guru" {{ old('teacher_role', 'guru') === 'guru' ? 'selected' : '' }}>Guru</option>
                                <option value="guru_walikelas" {{ old('teacher_role') === 'guru_walikelas' ? 'selected' : '' }}>Guru Walikelas</option>
                            </select>
                            @error('teacher_role')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nama Guru -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Guru <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Masukkan nama guru" required>
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password" name="password" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Minimal 8 karakter" required>
                            @error('password')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Konfirmasi Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Ulangi password" required>
                            @error('password_confirmation')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>


                        <!-- NIP -->
                        <div>
                            <label for="nip" class="block text-sm font-medium text-gray-700 mb-1">
                                NIP (Nomor Induk Pegawai) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nip" name="nip" value="{{ old('nip') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Nomor Induk Pegawai (NIP)" required>
                            <p class="text-gray-500 text-sm mt-1">NIP ini akan digunakan sebagai login identifier untuk guru</p>
                            @error('nip')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Mata Pelajaran -->
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                                Mata Pelajaran yang Diajar
                            </label>
                            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Contoh: Matematika, Bahasa Indonesia">
                            @error('subject')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="wali_kelas" class="block text-sm font-medium text-gray-700 mb-1">
                                Keterangan Wali Kelas
                            </label>
                            <input type="text" id="wali_kelas" name="wali_kelas" value="{{ old('wali_kelas') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Contoh: Wali Kelas X IPA 1">
                            <p class="text-gray-500 text-sm mt-1">Wajib diisi jika jenis guru adalah Guru Walikelas.</p>
                            @error('wali_kelas')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- No. WhatsApp -->
                        <div>
                            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">
                                No. WhatsApp
                            </label>
                            <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="+62812...">
                            @error('whatsapp_number')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t">
                            <a href="{{ route('admin.teachers.index') }}" 
                                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
                                Batal
                            </a>
                            <button type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                                Tambah Guru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
