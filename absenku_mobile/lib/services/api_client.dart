// File ini berisi klien HTTP pusat untuk komunikasi ke API Laravel.
// Fungsinya mengelola koneksi Dio, menambahkan token autentikasi, dan menangani respons error seperti 401.

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/config/app_config.dart';

// Kelas ini berfungsi sebagai pusat request API untuk seluruh aplikasi.
// Dengan pola singleton, hanya ada satu instance klien yang dipakai secara konsisten.
class ApiClient {
  // Singleton agar semua bagian aplikasi memakai instance yang sama.
  static final ApiClient _instance = ApiClient._internal();
  factory ApiClient() => _instance;
  ApiClient._internal();

  // Objek Dio yang digunakan untuk mengirim request ke server.
  late final Dio _dio;
  
  // Storage terenkripsi untuk menyimpan token (Keystore di Android, Keychain di iOS).
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();

  // Penanda apakah klien sudah selesai diinisialisasi.
  bool _initialized = false;

  // Getter untuk mengambil objek Dio dari luar kelas.
  Dio get dio => _dio;

  // Menginisialisasi konfigurasi dasar client seperti base URL, timeout, dan header default.
  Future<void> init() async {
    // Jika sudah pernah diinisialisasi, lewati proses agar tidak membuat duplikasi interceptor.
    if (_initialized) return;

    // Membuat instance Dio dengan konfigurasi umum untuk semua request API.
    _dio = Dio(BaseOptions(
      baseUrl: AppConfig.apiBaseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        // Menyisipkan header khusus agar browser warning dari ngrok tidak mengganggu request.
        'ngrok-skip-browser-warning': 'true',
      },
    ));

    // Menambahkan interceptor agar token autentikasi otomatis disisipkan sebelum setiap request dikirim.
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        // Membaca token dari penyimpanan terenkripsi agar request bisa terotentikasi.
        final token = await _secureStorage.read(key: 'auth_token');
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        // Jika server mengembalikan respon 401, token dianggap kadaluarsa dan dihapus.
        if (error.response?.statusCode == 401) {
          _secureStorage.delete(key: 'auth_token');
        }
        return handler.next(error);
      },
    ));

    // Menandai bahwa inisialisasi selesai dan client siap dipakai.
    _initialized = true;
  }

  // Menyimpan token autentikasi ke secure storage setelah login berhasil.
  Future<void> saveToken(String token) async {
    await _secureStorage.write(key: 'auth_token', value: token);
  }

  // Menghapus token saat pengguna logout atau sesi berakhir.
  Future<void> clearToken() async {
    await _secureStorage.delete(key: 'auth_token');
  }

  // Mengecek apakah token autentikasi sudah tersimpan.
  Future<bool> hasToken() async {
    final token = await _secureStorage.read(key: 'auth_token');
    return token != null && token.isNotEmpty;
  }

  // Mengambil token yang sedang tersimpan untuk dipakai ke kebutuhan lain.
  Future<String?> getToken() async {
    return await _secureStorage.read(key: 'auth_token');
  }
}
