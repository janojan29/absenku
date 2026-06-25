#!/bin/bash

# run_app.sh - Membantu setup dan menjalankan aplikasi Absenku Mobile dengan mudah

# Pindah ke direktori absenku_mobile jika dipanggil dari luar
cd "$(dirname "$0")"

echo "============================================="
echo "       Absenku Mobile - Setup & Run          "
echo "============================================="

# 1. Cek ketersediaan perintah flutter
if ! command -v flutter &> /dev/null
then
    echo "❌ Error: Flutter SDK tidak terdeteksi di system PATH Anda."
    echo "Silakan instal Flutter SDK terlebih dahulu atau pastikan letak instalasinya"
    echo "telah ditambahkan ke variabel environment PATH Anda."
    exit 1
fi

echo "✅ Flutter SDK ditemukan!"
flutter --version
echo "---------------------------------------------"

# 2. Ambil dependencies (flutter pub get)
echo "📦 Mengunduh dependencies package..."
flutter pub get
if [ $? -ne 0 ]; then
    echo "❌ Gagal mengunduh dependencies."
    exit 1
fi
echo "✅ Dependencies berhasil diunduh!"
echo "---------------------------------------------"

# 3. Generate platform folder jika belum ada (misal folder android)
if [ ! -d "android" ]; then
    echo "📁 Folder platform (android/ios/web) belum dibuat."
    echo "Generating folder platform dengan flutter create..."
    flutter create --org com.absenku --project-name absenku_mobile .
    if [ $? -ne 0 ]; then
        echo "❌ Gagal men-generate folder platform."
        exit 1
    fi
    echo "✅ Folder platform berhasil dibuat!"
    echo "---------------------------------------------"
fi

# 4. Pilih perangkat untuk menjalankan aplikasi
echo "📱 Perangkat yang tersedia:"
flutter devices
echo "---------------------------------------------"

echo "Pilih opsi menjalankan aplikasi:"
echo "1) Jalankan pada perangkat default / emulator"
echo "2) Jalankan pada Google Chrome (Web)"
echo "3) Hanya lakukan build/setup tanpa menjalankan"
read -p "Masukkan pilihan Anda (1/2/3): " pilihan

case $pilihan in
    1)
        echo "🚀 Menjalankan aplikasi..."
        flutter run
        ;;
    2)
        echo "🚀 Menjalankan aplikasi di Google Chrome..."
        flutter run -d chrome
        ;;
    3)
        echo "✨ Setup selesai! Anda bisa menjalankan aplikasi kapan saja dengan perintah 'flutter run'"
        ;;
    *)
        echo "Opsi tidak valid. Hanya melakukan setup."
        ;;
esac
