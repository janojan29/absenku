// File ini berisi klien HTTP pusat untuk komunikasi ke API Laravel.
// Fungsinya mengelola koneksi Dio, menambahkan token autentikasi, dan menangani respons error seperti 401.

import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
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
        // Membaca token dari penyimpanan lokal agar request bisa terotentikasi.
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token');
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        // Jika server mengembalikan respon 401, token dianggap kadaluarsa dan dihapus.
        if (error.response?.statusCode == 401) {
          SharedPreferences.getInstance().then((prefs) {
            prefs.remove('auth_token');
          });
        }
        return handler.next(error);
      },
    ));

    // Menandai bahwa inisialisasi selesai dan client siap dipakai.
    _initialized = true;
  }

  // Menyimpan token autentikasi ke local storage setelah login berhasil.
  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  // Menghapus token saat pengguna logout atau sesi berakhir.
  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  // Mengecek apakah token autentikasi sudah tersimpan.
  Future<bool> hasToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token') != null;
  }

  // Mengambil token yang sedang tersimpan untuk dipakai ke kebutuhan lain.
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }
}
