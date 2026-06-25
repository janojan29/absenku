import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../core/config/app_config.dart';

class ApiService {
  static late SharedPreferences _prefs;
  static String _baseUrl = AppConfig.defaultApiUrl;
  static String? _token;

  // Initialize API service loading settings
  static Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
    _baseUrl = _prefs.getString('api_base_url') ?? AppConfig.defaultApiUrl;
    _token = _prefs.getString('api_token');
    
    if (kDebugMode) {
      print('ApiService Initialized: BaseUrl=$_baseUrl, Token=${_token != null ? "Present" : "None"}');
    }
  }

  static String get baseUrl => _baseUrl;
  static String? get token => _token;

  static Future<void> setBaseUrl(String url) async {
    _baseUrl = url;
    await _prefs.setString('api_base_url', url);
  }

  static Future<void> setToken(String? token) async {
    _token = token;
    if (token == null) {
      await _prefs.remove('api_token');
    } else {
      await _prefs.setString('api_token', token);
    }
  }

  // General request headers
  static Map<String, String> _headers() {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (_token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    return headers;
  }

  // Generic request methods
  static Future<http.Response> get(String path) async {
    final url = Uri.parse('$_baseUrl$path');
    final response = await http.get(url, headers: _headers());
    _checkUnauthorized(response);
    return response;
  }

  static Future<http.Response> post(String path, Map<String, dynamic> body) async {
    final url = Uri.parse('$_baseUrl$path');
    final response = await http.post(url, headers: _headers(), body: jsonEncode(body));
    _checkUnauthorized(response);
    return response;
  }

  static Future<http.Response> patch(String path, Map<String, dynamic> body) async {
    final url = Uri.parse('$_baseUrl$path');
    final response = await http.patch(url, headers: _headers(), body: jsonEncode(body));
    _checkUnauthorized(response);
    return response;
  }

  static Future<http.Response> delete(String path) async {
    final url = Uri.parse('$_baseUrl$path');
    final response = await http.delete(url, headers: _headers());
    _checkUnauthorized(response);
    return response;
  }

  // Handle auto logout on 401
  static void _checkUnauthorized(http.Response response) {
    if (response.statusCode == 401) {
      setToken(null);
    }
  }

  // Auth Endpoints
  static Future<Map<String, dynamic>> login(String identifier, String password) async {
    final response = await post('/login', {
      'login_identifier': identifier,
      'password': password,
      'device_name': kIsWeb ? 'web-browser' : 'mobile-app',
    });

    final body = jsonDecode(response.body);
    if (response.statusCode == 200) {
      final token = body['token'] as String;
      final userData = body['user'] as Map<String, dynamic>;
      await setToken(token);
      return {
        'success': true,
        'user': userData,
        'token': token,
      };
    } else {
      throw Exception(body['message'] ?? 'Login gagal. Silakan periksa kembali kredensial Anda.');
    }
  }

  static Future<void> logout() async {
    try {
      await post('/logout', {});
    } catch (_) {
      // Ignore network errors on logout
    } finally {
      await setToken(null);
    }
  }

  static Future<Map<String, dynamic>> getProfile() async {
    final response = await get('/user');
    final body = jsonDecode(response.body);
    if (response.statusCode == 200) {
      return body['data']['user'] as Map<String, dynamic>;
    } else {
      throw Exception(body['message'] ?? 'Gagal mengambil profil.');
    }
  }

  // Student Attendance
  static Future<Map<String, dynamic>> getAttendanceStatus() async {
    final response = await get('/attendance');
    final body = jsonDecode(response.body);
    if (response.statusCode == 200) {
      return body['data'] as Map<String, dynamic>;
    } else {
      throw Exception(body['message'] ?? 'Gagal mengambil status presensi.');
    }
  }

  static Future<String> checkIn(double lat, double lng, double accuracy) async {
    final response = await post('/attendance/check-in', {
      'latitude': lat,
      'longitude': lng,
      'accuracy': accuracy,
    });
    final body = jsonDecode(response.body);
    if (response.statusCode == 200) {
      return body['message'] as String;
    } else {
      throw Exception(body['message'] ?? 'Gagal melakukan check-in.');
    }
  }

  static Future<String> checkOut(double lat, double lng, double accuracy) async {
    final response = await post('/attendance/check-out', {
      'latitude': lat,
      'longitude': lng,
      'accuracy': accuracy,
    });
    final body = jsonDecode(response.body);
    if (response.statusCode == 200) {
      return body['message'] as String;
    } else {
      throw Exception(body['message'] ?? 'Gagal melakukan check-out.');
    }
  }

  static Future<String> submitLeaveRequest(String type, String reason, String keterangan, String date) async {
    final response = await post('/leave-requests', {
      'type': type,
      'reason': reason,
      'keterangan': keterangan,
      'leave_date': date,
    });
    final body = jsonDecode(response.body);
    if (response.statusCode == 200) {
      return body['message'] as String;
    } else {
      throw Exception(body['message'] ?? 'Gagal mengajukan izin.');
    }
  }

  // Picket Leave Requests Approval
  static Future<List<dynamic>> getPicketLeaveRequests() async {
    final response = await get('/picket/leave-requests');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? [];
    } else {
      throw Exception('Gagal memuat antrean izin petugas piket.');
    }
  }

  static Future<void> approveLeaveRequest(int id) async {
    final response = await post('/picket/leave-requests/$id/approve', {});
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal menyetujui pengajuan.');
    }
  }

  static Future<void> rejectLeaveRequest(int id) async {
    final response = await post('/picket/leave-requests/$id/reject', {});
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal menolak pengajuan.');
    }
  }

  // Teacher Endpoints
  static Future<Map<String, dynamic>> getTeacherDashboard({int? classRoomId}) async {
    final query = classRoomId != null ? '?class_room_id=$classRoomId' : '';
    final response = await get('/teacher/dashboard$query');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? {};
    } else {
      throw Exception('Gagal memuat dashboard guru.');
    }
  }

  static Future<Map<String, dynamic>> getTeacherAttendanceReport({
    String? startDate,
    String? endDate,
    int? classRoomId,
    String? status,
  }) async {
    final params = <String>[];
    if (startDate != null) params.add('detail_start_date=$startDate');
    if (endDate != null) params.add('detail_end_date=$endDate');
    if (classRoomId != null) params.add('class_room_id=$classRoomId');
    if (status != null) params.add('status=$status');

    final queryString = params.isNotEmpty ? '?${params.join('&')}' : '';
    final response = await get('/teacher/reports/attendance$queryString');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? {};
    } else {
      throw Exception('Gagal memuat rekap absensi guru.');
    }
  }

  // Admin Settings
  static Future<Map<String, dynamic>> getAdminSettings() async {
    final response = await get('/admin/settings');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? {};
    } else {
      throw Exception('Gagal memuat pengaturan sekolah.');
    }
  }

  static Future<void> updateAdminSettings(Map<String, dynamic> data) async {
    final response = await patch('/admin/settings', data);
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal memperbarui pengaturan.');
    }
  }

  // Admin Users
  static Future<List<dynamic>> getAdminUsers() async {
    final response = await get('/admin/users');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? [];
    } else {
      throw Exception('Gagal memuat data user.');
    }
  }

  static Future<void> deleteAdminUser(int id) async {
    final response = await delete('/admin/users/$id');
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal menghapus user.');
    }
  }

  // Admin Classrooms
  static Future<List<dynamic>> getAdminClassrooms() async {
    final response = await get('/admin/class-rooms');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? [];
    } else {
      throw Exception('Gagal memuat data kelas.');
    }
  }

  static Future<void> createAdminClassroom(String name, String major) async {
    final response = await post('/admin/class-rooms', {
      'name': name,
      'jurusan': major,
    });
    if (response.statusCode != 200 && response.statusCode != 201) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal menambahkan kelas.');
    }
  }

  static Future<void> deleteAdminClassroom(int id) async {
    final response = await delete('/admin/class-rooms/$id');
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal menghapus kelas.');
    }
  }

  // Admin Students
  static Future<List<dynamic>> getAdminStudents() async {
    final response = await get('/admin/students');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? [];
    } else {
      throw Exception('Gagal memuat data siswa.');
    }
  }

  static Future<void> createAdminStudent(Map<String, dynamic> data) async {
    final response = await post('/admin/students', data);
    if (response.statusCode != 200 && response.statusCode != 201) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal menambahkan siswa.');
    }
  }

  static Future<void> updateAdminStudent(int id, Map<String, dynamic> data) async {
    final response = await patch('/admin/students/$id', data);
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal memperbarui siswa.');
    }
  }

  // Admin Teachers
  static Future<List<dynamic>> getAdminTeachers() async {
    final response = await get('/admin/teachers');
    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      return body['data'] ?? [];
    } else {
      throw Exception('Gagal memuat data guru.');
    }
  }

  static Future<void> createAdminTeacher(Map<String, dynamic> data) async {
    final response = await post('/admin/teachers', data);
    if (response.statusCode != 200 && response.statusCode != 201) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal menambahkan guru.');
    }
  }

  static Future<void> updateAdminTeacher(int id, Map<String, dynamic> data) async {
    final response = await patch('/admin/teachers/$id', data);
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw Exception(body['message'] ?? 'Gagal memperbarui guru.');
    }
  }
}
