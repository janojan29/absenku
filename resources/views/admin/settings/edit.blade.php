<x-app-layout>
    <x-slot name="title">Pengaturan Sekolah</x-slot>
    <x-slot name="header">
        <h1 class="text-display-sm text-surface-50">Pengaturan Sekolah</h1>
        <p class="text-sm text-electric-200/80 mt-1">Konfigurasi lokasi, waktu absensi, dan profil sekolah</p>
    </x-slot>

    <div class="space-y-6">
        <div class="card animate-fade-slide-up max-w-4xl">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
                @csrf
                @method('PATCH')

                {{-- Profil Sekolah --}}
                <div class="space-y-4">
                    <h3 class="font-bold text-navy-800 border-b border-bw-200 pb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-navy-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.315 48.315 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" /></svg>
                        Profil Sekolah
                    </h3>
                    <div>
                        <label for="name" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Nama Sekolah <span class="text-red-500">*</span></label>
                        <input id="name" name="name" type="text" class="form-input-clean w-full" value="{{ old('name', $setting->name) }}" required />
                    </div>
                </div>

                {{-- Pengaturan Geolocation --}}
                <div class="space-y-4">
                    <h3 class="font-bold text-navy-800 border-b border-bw-200 pb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-navy-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                        Koordinat & Radius Lokasi
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="latitude" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Latitude <span class="text-red-500">*</span></label>
                            <input id="latitude" name="latitude" type="text" class="form-input-clean w-full" value="{{ old('latitude', $setting->latitude) }}" required />
                        </div>
                        <div>
                            <label for="longitude" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Longitude <span class="text-red-500">*</span></label>
                            <input id="longitude" name="longitude" type="text" class="form-input-clean w-full" value="{{ old('longitude', $setting->longitude) }}" required />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="radius_meters" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Radius Toleransi (Meter) <span class="text-red-500">*</span></label>
                            <input id="radius_meters" name="radius_meters" type="number" class="form-input-clean w-full" value="{{ old('radius_meters', $setting->radius_meters) }}" required min="10" max="5000" />
                            <p class="text-xs text-bw-400 mt-1">Batas maksimal jarak siswa dari koordinat sekolah untuk bisa absen.</p>
                        </div>
                    </div>
                </div>

                {{-- Pengaturan Waktu --}}
                <div class="space-y-4">
                    <h3 class="font-bold text-navy-800 border-b border-bw-200 pb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-navy-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Jadwal & Waktu Absensi
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div>
                            <label for="check_in_start_time" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Buka Absen Masuk</label>
                            <input id="check_in_start_time" name="check_in_start_time" type="time" class="form-input-clean w-full" value="{{ old('check_in_start_time', substr($setting->check_in_start_time, 0, 5)) }}" required />
                        </div>
                        <div>
                            <label for="check_in_end_time" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Tutup Absen Masuk</label>
                            <input id="check_in_end_time" name="check_in_end_time" type="time" class="form-input-clean w-full" value="{{ old('check_in_end_time', substr($setting->check_in_end_time, 0, 5)) }}" required />
                        </div>
                        <div>
                            <label for="check_out_start_time" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Buka Absen Pulang</label>
                            <input id="check_out_start_time" name="check_out_start_time" type="time" class="form-input-clean w-full" value="{{ old('check_out_start_time', substr($setting->check_out_start_time, 0, 5)) }}" required />
                        </div>
                        <div>
                            <label for="check_out_end_time" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Tutup Absen Pulang</label>
                            <input id="check_out_end_time" name="check_out_end_time" type="time" class="form-input-clean w-full" value="{{ old('check_out_end_time', substr($setting->check_out_end_time, 0, 5)) }}" required />
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="late_tolerance_minutes" class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Toleransi Terlambat (Menit)</label>
                        <input id="late_tolerance_minutes" name="late_tolerance_minutes" type="number" class="form-input-clean w-full sm:w-1/2" value="{{ old('late_tolerance_minutes', $setting->late_tolerance_minutes) }}" required min="0" max="180" />
                        <p class="text-xs text-bw-400 mt-1">Siswa dianggap terlambat jika absen lebih dari waktu toleransi (dihitung setelah jam Buka Absen Masuk).</p>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="pt-6 border-t border-bw-200 flex items-center justify-end gap-3">
                    <a href="{{ route('dashboard') }}" class="btn-secondary h-10 px-6">Kembali</a>
                    <button type="submit" class="btn-primary btn-ripple h-10 px-6">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
