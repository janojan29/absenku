<x-app-layout>
    <x-slot name="title">Impor Data Siswa</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-surface-50 leading-tight">
            {{ __('Impor Data Siswa') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('import_errors'))
                <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    <div class="font-semibold">Impor selesai dengan catatan:</div>
                    <ul class="list-disc pl-5 mt-2 text-sm">
                        @foreach (session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_1fr] gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Panduan Impor</h3>
                        <p class="text-sm text-bw-400 mt-1">Ikuti format kolom agar data masuk tanpa error.</p>

                        <div class="mt-5 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 h-6 w-6 rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold flex items-center justify-center">1</div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Unduh template</div>
                                    <div class="text-xs text-bw-400">Gunakan file template agar header kolom sesuai.</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 h-6 w-6 rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold flex items-center justify-center">2</div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Isi data siswa</div>
                                    <div class="text-xs text-bw-400">Pastikan kolom kelas dan jurusan sesuai data kelas di sistem.</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 h-6 w-6 rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold flex items-center justify-center">3</div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Unggah dan impor</div>
                                    <div class="text-xs text-bw-400">Password siswa otomatis: siswa123.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-700">Format kolom</div>
                            <div class="mt-2 text-xs text-gray-600 font-mono">nama | nisn | kelas | jurusan | nohp orangtua | no hp siswa</div>
                            <div class="mt-3 text-[11px] text-bw-400">Catatan: kolom boleh kosong untuk nomor HP, tetapi nama, nisn, kelas, jurusan wajib.</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form method="POST" action="{{ route('admin.students.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    File Excel <span class="text-red-500">*</span>
                                </label>
                                <div class="flex flex-col items-start gap-2">
                                    <input id="import-file" type="file" name="file" accept=".xlsx,.xls,.csv" class="sr-only" required>
                                    <label for="import-file" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium cursor-pointer">
                                        Pilih File
                                    </label>
                                    <span id="import-file-name" class="text-xs text-bw-400">Belum ada file dipilih.</span>
                                </div>
                                @error('file')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-2 pt-3 border-t">
                                <a href="{{ route('admin.students.import.template') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium text-center">
                                    Unduh Template
                                </a>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                                    Impor Data
                                </button>
                                <a href="{{ route('admin.students.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium text-center">
                                    Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var input = document.getElementById('import-file');
            var label = document.getElementById('import-file-name');
            if (!input || !label) return;

            input.addEventListener('change', function () {
                var fileName = input.files && input.files.length > 0 ? input.files[0].name : 'Belum ada file dipilih.';
                label.textContent = fileName;
            });
        });
    </script>
</x-app-layout>
