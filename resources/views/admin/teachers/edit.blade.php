<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Data Guru') }}
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

                    <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="teacher_role" class="block text-sm font-medium text-gray-700 mb-1">Jenis Guru</label>
                            <select id="teacher_role" name="teacher_role" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="guru" {{ old('teacher_role', $teacher->hasRole('guru_walikelas') ? 'guru_walikelas' : 'guru') === 'guru' ? 'selected' : '' }}>Guru</option>
                                <option value="guru_walikelas" {{ old('teacher_role', $teacher->hasRole('guru_walikelas') ? 'guru_walikelas' : 'guru') === 'guru_walikelas' ? 'selected' : '' }}>Guru Walikelas</option>
                            </select>
                            @error('teacher_role')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Guru</label>
                            <input type="text" value="{{ $teacher->name }}" class="w-full px-4 py-2 border border-gray-200 rounded-md bg-gray-100 text-gray-700" disabled>
                        </div>

                        <div>
                            <label for="nip" class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                            <input type="text" id="nip" name="nip" value="{{ old('nip', $teacher->teacher?->nip) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="NIP">
                            @error('nip')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                            <input type="text" id="subject" name="subject" value="{{ old('subject', $teacher->teacher?->subject) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Mata Pelajaran">
                            @error('subject')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="wali_kelas" class="block text-sm font-medium text-gray-700 mb-1">Keterangan Wali Kelas</label>
                            <input type="text" id="wali_kelas" name="wali_kelas" value="{{ old('wali_kelas', $teacher->teacher?->wali_kelas) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Wali Kelas X IPA 1">
                            <p class="text-gray-500 text-sm mt-1">Wajib diisi jika jenis guru adalah Guru Walikelas.</p>
                            @error('wali_kelas')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp</label>
                            <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $teacher->whatsapp_number) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="+62812...">
                            @error('whatsapp_number')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-6 border-t">
                            <a href="{{ route('admin.teachers.index') }}" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
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
</x-app-layout>
