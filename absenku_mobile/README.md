# Absenku Mobile 📱

Aplikasi mobile presensi digital siswa berbasis Flutter untuk platform Android, iOS, dan Web. Aplikasi ini terintegrasi dengan backend Absenku.

---

## 🚀 Cara Menjalankan Aplikasi

Karena folder spesifik platform (`android/`, `ios/`, `web/`, dsb.) tidak dimasukkan ke dalam repositori untuk menghemat ukuran penyimpanan, Anda perlu men-generate-nya terlebih dahulu sebelum menjalankan aplikasi. Ikuti langkah-langkah di bawah ini:

### 1. Masuk ke Direktori Project
Buka terminal dan masuk ke folder `absenku_mobile`:
```bash
cd absenku_mobile
```

### 2. Unduh Dependencies (Package)
Jalankan perintah berikut untuk mengunduh package/library Flutter yang dibutuhkan (seperti `go_router`, `provider`, `google_fonts`, dsb.):
```bash
flutter pub get
```

### 3. Generate Folder Platform (`android`, `ios`, `web`)
Gunakan perintah `flutter create` untuk membuat struktur folder platform agar aplikasi bisa dijalankan di perangkat/emulator tujuan:
```bash
flutter create --org com.absenku --project-name absenku_mobile .
```

### 4. Jalankan Aplikasi
Setelah folder platform ter-generate, pastikan emulator Anda sudah aktif atau perangkat fisik sudah terhubung. Cek perangkat yang tersedia dengan:
```bash
flutter devices
```

Lalu jalankan aplikasi:
* **Mode Default (Perangkat Utama/Emulator):**
  ```bash
  flutter run
  ```
* **Mode Web (menggunakan Google Chrome):**
  ```bash
  flutter run -d chrome
  ```

---

## 🛠️ Pembaruan & Perbaikan Terbaru

1. **Perbaikan Parameter Routing di GoRouter (`lib/core/app_router.dart`):**
   * **Masalah Sebelumnya:** Ketika mengklik tombol edit siswa atau edit guru di halaman kelola admin, halaman form edit terbuka namun seluruh input field-nya kosong.
   * **Penyebab:** GoRouter tidak melewatkan objek model (`extra`) ke dalam constructor widget form (`StudentFormScreen` dan `TeacherFormScreen`), sehingga `initState()` pada form menginisialisasi controller dengan nilai kosong/null.
   * **Solusi:** Rute `/admin/student/form` dan `/admin/teacher/form` telah disesuaikan agar mengekstrak parameter `state.extra` secara tepat dan mengumpankannya ke constructor widget masing-masing, sehingga data siswa/guru langsung terisi di form edit secara instan.

2. **Struktur Core & Fitur Bersih:**
   * File router, tema, konfigurasi warna, widget kustom (`ClockWidget`, `StatusBadge`, `StatCard`), serta data tiruan (`MockData`) sudah lengkap dan siap digunakan.
