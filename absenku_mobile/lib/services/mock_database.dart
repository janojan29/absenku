import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../models/user.dart';
import '../models/attendance.dart';
import '../core/config/app_config.dart';
import 'api_client.dart';

/// API-backed database that replaces the mock database.
/// Maintains the same ChangeNotifier interface so all screens keep working
/// without modifications. Data is now fetched from the Laravel backend
/// via the ngrok tunnel.
class MockDatabase extends ChangeNotifier {
  static final MockDatabase _instance = MockDatabase._internal();
  factory MockDatabase() => _instance;
  MockDatabase._internal();

  bool _initialized = false;
  bool _isLoading = false;

  // State
  List<ClassRoom> _classrooms = [];
  List<User> _users = [];
  List<User> _studentUsers = [];
  List<User> _teacherUsers = [];
  List<Attendance> _attendance = [];
  List<LeaveRequest> _leaveRequests = [];
  User? _currentUser;
  bool _mustChangePassword = false;

  // Settings (loaded from API /attendance endpoint's school setting)
  double _latitude = AppConfig.defaultLatitude;
  double _longitude = AppConfig.defaultLongitude;
  int _radiusMeters = AppConfig.defaultRadiusMeters;
  String _checkInStart = AppConfig.defaultCheckInStartTime;
  String _checkInEnd = AppConfig.defaultCheckInEndTime;
  String _checkOutStart = AppConfig.defaultCheckOutStartTime;
  String _checkOutEnd = AppConfig.defaultCheckOutEndTime;

  // Simulated Device Coordinates (still local — GPS)
  double _deviceLatitude = AppConfig.defaultLatitude;
  double _deviceLongitude = AppConfig.defaultLongitude;

  // Attendance page API data
  bool _canCheckInNow = false;
  bool _canCheckOutNow = false;
  bool _hasReachedCheckInStart = false;
  bool _isAfterCheckInEnd = false;
  bool _isAfterCheckOutEnd = false;
  bool _hasApprovedAbsentLeaveToday = false;
  bool _showLeaveForm = true;
  bool _isHolidayToday = false;
  Attendance? _todayAttendance;
  LeaveRequest? _todayLeaveSubmission;
  List<String> _absentBlockedDates = [];
  bool _earlyLeaveBlockedToday = false;

  // Teacher dashboard data
  Map<String, int> _dashboardCounts = {};
  List<Map<String, dynamic>> _dashboardStudents = [];
  String? _dashboardClassRoomId;

  // Getters
  List<ClassRoom> get classrooms => _classrooms;
  List<User> get users => _users;
  List<Attendance> get attendance => _attendance;
  List<LeaveRequest> get leaveRequests => _leaveRequests;
  User? get currentUser => _currentUser;
  bool get isLoading => _isLoading;

  double get latitude => _latitude;
  double get longitude => _longitude;
  int get radiusMeters => _radiusMeters;
  String get checkInStart => _checkInStart;
  String get checkInEnd => _checkInEnd;
  String get checkOutStart => _checkOutStart;
  String get checkOutEnd => _checkOutEnd;

  double get deviceLatitude => _deviceLatitude;
  double get deviceLongitude => _deviceLongitude;

  bool get canCheckInNow => _canCheckInNow;
  bool get canCheckOutNow => _canCheckOutNow;
  bool get hasReachedCheckInStart => _hasReachedCheckInStart;
  bool get isAfterCheckInEnd => _isAfterCheckInEnd;
  bool get isAfterCheckOutEnd => _isAfterCheckOutEnd;
  bool get hasApprovedAbsentLeaveToday => _hasApprovedAbsentLeaveToday;
  bool get showLeaveForm => _showLeaveForm;
  bool get isHolidayToday => _isHolidayToday;
  Attendance? get todayAttendance => _todayAttendance;
  LeaveRequest? get todayLeaveSubmission => _todayLeaveSubmission;
  List<String> get absentBlockedDates => _absentBlockedDates;
  bool get earlyLeaveBlockedToday => _earlyLeaveBlockedToday;
  bool get mustChangePassword => _mustChangePassword;
  void clearMustChangePassword() {
    _mustChangePassword = false;
    notifyListeners();
  }

  Map<String, int> get dashboardCounts => _dashboardCounts;
  List<Map<String, dynamic>> get dashboardStudents => _dashboardStudents;
  String? get dashboardClassRoomId => _dashboardClassRoomId;

  Dio get _dio => ApiClient().dio;

  void setDeviceLocation(double lat, double lng) {
    _deviceLatitude = lat;
    _deviceLongitude = lng;
    notifyListeners();
  }

  Future<void> init() async {
    if (_initialized) return;
    await ApiClient().init();

    // Try to restore session if token exists
    final hasToken = await ApiClient().hasToken();
    if (hasToken) {
      try {
        final response = await _dio.get('/user');
        if (response.statusCode == 200) {
          final userData = response.data['data']['user'] as Map<String, dynamic>;
          _currentUser = User.fromApiJson(userData);
          _mustChangePassword = _currentUser?.hasDefaultPassword ?? false;
        }
      } catch (e) {
        // Token expired or invalid, clear it
        await ApiClient().clearToken();
        _currentUser = null;
      }
    }

    _initialized = true;
    notifyListeners();
  }

  // ──────────────────────────────────────────────
  // Auth Operations
  // ──────────────────────────────────────────────

  Future<User?> login(String loginIdentifier, String password) async {
    try {
      final response = await _dio.post('/login', data: {
        'login_identifier': loginIdentifier,
        'password': password,
        'device_name': 'flutter_mobile',
      });

      if (response.statusCode == 200) {
        final token = response.data['token'] as String;
        await ApiClient().saveToken(token);

        final userData = response.data['user'] as Map<String, dynamic>;
        _currentUser = User.fromApiJson(userData);
        
        _mustChangePassword = _currentUser?.hasDefaultPassword ?? false;
        
        notifyListeners();
        return _currentUser;
      }
      throw Exception('Login gagal. Periksa kembali identitas dan password.');
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        // Validation error from Laravel
        final errors = e.response?.data;
        String message = 'Login gagal.';
        if (errors is Map && errors['errors'] != null) {
          final errorMap = errors['errors'] as Map<String, dynamic>;
          final firstError = errorMap.values.first;
          if (firstError is List && firstError.isNotEmpty) {
            message = firstError.first.toString();
          }
        } else if (errors is Map && errors['message'] != null) {
          message = errors['message'].toString();
        }
        throw Exception(message);
      }
      throw Exception('Tidak dapat terhubung ke server. Periksa koneksi internet.');
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post('/logout');
    } catch (_) {
      // Even if logout API fails, clear local state
    }
    await ApiClient().clearToken();
    _currentUser = null;
    _attendance = [];
    _leaveRequests = [];
    _todayAttendance = null;
    _todayLeaveSubmission = null;
    _absentBlockedDates = [];
    _earlyLeaveBlockedToday = false;
    notifyListeners();
  }

  Future<void> changePassword(String oldPassword, String newPassword, String newPasswordConfirmation) async {
    try {
      final response = await _dio.post('/user/change-password', data: {
        'old_password': oldPassword,
        'password': newPassword,
        'password_confirmation': newPasswordConfirmation,
      });
      if (response.statusCode == 200) {
        clearMustChangePassword();
        try {
          final userRes = await _dio.get('/user');
          if (userRes.statusCode == 200) {
            final userData = userRes.data['data']['user'] as Map<String, dynamic>;
            _currentUser = User.fromApiJson(userData);
            notifyListeners();
          }
        } catch (_) {}
      }
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['errors'] != null) {
        final errors = e.response!.data['errors'] as Map<String, dynamic>;
        throw Exception(errors.values.expand((v) => v as List).join('\n'));
      }
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal mengubah password.');
    }
  }

  // ──────────────────────────────────────────────
  // Student Attendance Operations
  // ──────────────────────────────────────────────

  /// Fetch attendance data for current student from API
  Future<void> fetchAttendanceData() async {
    if (_currentUser == null) return;
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _dio.get('/attendance');
      if (response.statusCode == 200) {
        final data = response.data['data'] as Map<String, dynamic>;

        // Parse today's attendance
        if (data['attendance'] != null) {
          _todayAttendance = Attendance.fromApiJson(data['attendance'] as Map<String, dynamic>);
        } else {
          _todayAttendance = null;
        }

        // Parse recent history
        final recentList = data['recent'] as List? ?? [];
        _attendance = recentList
            .map((item) => Attendance.fromApiJson(item as Map<String, dynamic>))
            .toList();

        // Parse school setting
        final setting = data['setting'] as Map<String, dynamic>?;
        if (setting != null) {
          _latitude = (setting['latitude'] as num?)?.toDouble() ?? _latitude;
          _longitude = (setting['longitude'] as num?)?.toDouble() ?? _longitude;
          _radiusMeters = (setting['radius_meters'] as num?)?.toInt() ?? _radiusMeters;
          _checkInStart = setting['check_in_start_time'] as String? ?? _checkInStart;
          _checkInEnd = setting['check_in_end_time'] as String? ?? _checkInEnd;
          _checkOutStart = setting['check_out_start_time'] as String? ?? _checkOutStart;
          _checkOutEnd = setting['check_out_end_time'] as String? ?? _checkOutEnd;
        }

        // Parse boolean flags from server
        _canCheckInNow = data['can_check_in_now'] as bool? ?? false;
        _canCheckOutNow = data['can_check_out_now'] as bool? ?? false;
        _hasReachedCheckInStart = data['has_reached_check_in_start'] as bool? ?? false;
        _isAfterCheckInEnd = data['is_after_check_in_end'] as bool? ?? false;
        _isAfterCheckOutEnd = data['is_after_check_out_end'] as bool? ?? false;
        _hasApprovedAbsentLeaveToday = data['has_approved_absent_leave_today'] as bool? ?? false;
        _showLeaveForm = data['show_leave_form'] as bool? ?? true;
        _isHolidayToday = data['is_holiday_today'] as bool? ?? false;
        _absentBlockedDates = (data['absent_blocked_dates'] as List?)?.map((e) => e.toString()).toList() ?? [];
        _earlyLeaveBlockedToday = data['early_leave_blocked_today'] as bool? ?? false;

        // Parse today's leave submission
        if (data['today_leave_submission'] != null) {
          _todayLeaveSubmission = LeaveRequest.fromApiJson(
            data['today_leave_submission'] as Map<String, dynamic>,
          );
        } else {
          _todayLeaveSubmission = null;
        }
      }
    } catch (e) {
      debugPrint('Error fetching attendance data: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<String> checkIn(double lat, double lng) async {
    try {
      final response = await _dio.post('/attendance/check-in', data: {
        'latitude': lat,
        'longitude': lng,
        'accuracy': 10.0,
      });
      await fetchAttendanceData(); // Refresh data
      return response.data['message'] as String? ?? 'Berhasil Absen Masuk!';
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal melakukan check-in. Periksa koneksi.');
    }
  }

  Future<String> checkOut(double lat, double lng) async {
    try {
      final response = await _dio.post('/attendance/check-out', data: {
        'latitude': lat,
        'longitude': lng,
        'accuracy': 10.0,
      });
      await fetchAttendanceData(); // Refresh data
      return response.data['message'] as String? ?? 'Berhasil Absen Pulang!';
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal melakukan check-out. Periksa koneksi.');
    }
  }

  // ──────────────────────────────────────────────
  // Leave Requests Operations
  // ──────────────────────────────────────────────

  Future<void> submitLeaveRequest(String type, DateTime date, String reason, String keterangan) async {
    try {
      await _dio.post('/leave-requests', data: {
        'type': type,
        'reason': reason,
        'keterangan': keterangan,
        'leave_date': '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}',
      });
      // Refresh attendance data to update leave status
      await fetchAttendanceData();
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        final errors = e.response?.data;
        String message = 'Gagal mengajukan izin.';
        if (errors is Map && errors['errors'] != null) {
          final errorMap = errors['errors'] as Map<String, dynamic>;
          final firstError = errorMap.values.first;
          if (firstError is List && firstError.isNotEmpty) {
            message = firstError.first.toString();
          }
        } else if (errors is Map && errors['message'] != null) {
          message = errors['message'].toString();
        }
        throw Exception(message);
      }
      throw Exception('Gagal mengajukan izin. Periksa koneksi.');
    }
  }

  // ──────────────────────────────────────────────
  // Picket Officer — Leave Queue Operations
  // ──────────────────────────────────────────────

  Future<void> fetchLeaveQueue() async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _dio.get('/picket/leave-requests');
      if (response.statusCode == 200) {
        final pendingData = response.data['pending']['data'] as List? ?? [];
        final historyData = response.data['history']['data'] as List? ?? [];

        _leaveRequests = [
          ...pendingData.map((item) => LeaveRequest.fromApiJson(item as Map<String, dynamic>)),
          ...historyData.map((item) => LeaveRequest.fromApiJson(item as Map<String, dynamic>)),
        ];
      }
    } catch (e) {
      debugPrint('Error fetching leave queue: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> approveLeaveRequest(String id, String decidedById, String note) async {
    try {
      await _dio.post('/picket/leave-requests/$id/approve', data: {
        'decision_note': note,
      });
      await fetchLeaveQueue(); // Refresh
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menyetujui izin.');
    }
  }

  Future<void> rejectLeaveRequest(String id, String decidedById, String note) async {
    try {
      await _dio.post('/picket/leave-requests/$id/reject', data: {
        'decision_note': note,
      });
      await fetchLeaveQueue(); // Refresh
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menolak izin.');
    }
  }

  // ──────────────────────────────────────────────
  // Teacher Dashboard
  // ──────────────────────────────────────────────

  Future<void> fetchTeacherDashboard({String? classRoomId}) async {
    _isLoading = true;
    notifyListeners();

    try {
      final queryParams = <String, dynamic>{};
      if (classRoomId != null) {
        queryParams['class_room_id'] = classRoomId;
      }

      final response = await _dio.get('/teacher/dashboard', queryParameters: queryParams);
      if (response.statusCode == 200) {
        final data = response.data['data'] as Map<String, dynamic>;

        // Parse counts
        final counts = data['counts'] as Map<String, dynamic>?;
        if (counts != null) {
          _dashboardCounts = counts.map((k, v) => MapEntry(k, (v as num).toInt()));
        }

        // Parse students
        _dashboardStudents = (data['students'] as List? ?? [])
            .map((item) => Map<String, dynamic>.from(item as Map))
            .toList();

        _dashboardClassRoomId = data['class_room_id']?.toString();

        // Parse classrooms list
        final classroomsList = data['classrooms'] as List? ?? [];
        _classrooms = classroomsList
            .map((item) => ClassRoom.fromJson(item as Map<String, dynamic>))
            .toList();
      }
    } catch (e) {
      debugPrint('Error fetching teacher dashboard: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  // ──────────────────────────────────────────────
  // Teacher Reports
  // ──────────────────────────────────────────────

  Future<List<dynamic>> fetchTeacherReport({
    String? classRoomId,
    String? startDate,
    String? endDate,
    String? status,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (classRoomId != null && classRoomId.isNotEmpty) {
        queryParams['class_room_id'] = classRoomId;
      }
      if (startDate != null && startDate.isNotEmpty) {
        queryParams['detail_start_date'] = startDate;
      }
      if (endDate != null && endDate.isNotEmpty) {
        queryParams['detail_end_date'] = endDate;
      }
      if (status != null && status.isNotEmpty) {
        queryParams['status'] = status;
      }

      final response = await _dio.get('/teacher/reports/attendance', queryParameters: queryParams);
      if (response.statusCode == 200 && response.data != null) {
        final dataMap = response.data as Map<String, dynamic>;
        final data = dataMap['data'] as Map<String, dynamic>?;
        if (data != null && data['rows'] != null) {
          return data['rows'] as List<dynamic>;
        }
      }
    } catch (e) {
      debugPrint('Error fetching teacher report: $e');
    }
    return [];
  }

  // ──────────────────────────────────────────────
  // Admin Operations
  // ──────────────────────────────────────────────

  Future<void> fetchAdminSettings() async {
    try {
      final response = await _dio.get('/admin/settings');
      if (response.statusCode == 200) {
        final data = response.data['data'] as Map<String, dynamic>?;
        final setting = data != null ? data['setting'] as Map<String, dynamic>? : null;
        if (setting != null) {
          _latitude = (setting['latitude'] as num?)?.toDouble() ?? _latitude;
          _longitude = (setting['longitude'] as num?)?.toDouble() ?? _longitude;
          _radiusMeters = (setting['radius_meters'] as num?)?.toInt() ?? _radiusMeters;
          _checkInStart = setting['check_in_start_time'] as String? ?? _checkInStart;
          _checkInEnd = setting['check_in_end_time'] as String? ?? _checkInEnd;
          _checkOutStart = setting['check_out_start_time'] as String? ?? _checkOutStart;
          _checkOutEnd = setting['check_out_end_time'] as String? ?? _checkOutEnd;
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('Error fetching admin settings: $e');
    }
  }

  Future<void> updateSettings({
    required double latitude,
    required double longitude,
    required int radiusMeters,
    required String checkInStart,
    required String checkInEnd,
    required String checkOutStart,
    required String checkOutEnd,
  }) async {
    try {
      await _dio.patch('/admin/settings', data: {
        'latitude': latitude,
        'longitude': longitude,
        'radius_meters': radiusMeters,
        'check_in_start_time': checkInStart,
        'check_in_end_time': checkInEnd,
        'check_out_start_time': checkOutStart,
        'check_out_end_time': checkOutEnd,
      });

      _latitude = latitude;
      _longitude = longitude;
      _radiusMeters = radiusMeters;
      _checkInStart = checkInStart;
      _checkInEnd = checkInEnd;
      _checkOutStart = checkOutStart;
      _checkOutEnd = checkOutEnd;
      notifyListeners();
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menyimpan pengaturan.');
    }
  }

  // Helper to merge student + teacher user lists
  void _mergeUserLists() {
    _users = [..._studentUsers, ..._teacherUsers];
    notifyListeners();
  }

  // Admin Classroom Operations
  Future<void> fetchClassrooms() async {
    try {
      final List<ClassRoom> allClassrooms = [];
      int currentPage = 1;
      int lastPage = 1;

      do {
        final response = await _dio.get('/admin/class-rooms', queryParameters: {'page': currentPage});
        if (response.statusCode == 200) {
          final data = response.data['data'] as List? ?? response.data as List? ?? [];
          allClassrooms.addAll(
            data.map((item) => ClassRoom.fromJson(item as Map<String, dynamic>)),
          );

          // Check for pagination metadata
          final meta = response.data['meta'] as Map<String, dynamic>?;
          final pagination = meta?['pagination'] as Map<String, dynamic>?;
          if (pagination != null) {
            lastPage = (pagination['last_page'] as num?)?.toInt() ?? 1;
          } else {
            lastPage = 1;
          }
        } else {
          break;
        }
        currentPage++;
      } while (currentPage <= lastPage);

      _classrooms = allClassrooms;
      notifyListeners();
    } catch (e) {
      debugPrint('Error fetching classrooms: $e');
    }
  }

  Future<void> addClassRoom(String name, String jurusan) async {
    try {
      await _dio.post('/admin/class-rooms', data: {
        'name': name,
        'jurusan': jurusan,
      });
      await fetchClassrooms();
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menambahkan kelas.');
    }
  }

  Future<void> deleteClassRoom(String id) async {
    try {
      await _dio.delete('/admin/class-rooms/$id');
      _classrooms.removeWhere((c) => c.id == id);
      notifyListeners();
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menghapus kelas.');
    }
  }

  // Admin Student Operations
  Future<void> fetchStudents() async {
    try {
      final List<User> allStudents = [];
      int currentPage = 1;
      int lastPage = 1;

      do {
        final response = await _dio.get('/admin/students', queryParameters: {'page': currentPage});
        if (response.statusCode == 200) {
          final data = response.data['data'] as List? ?? [];
          allStudents.addAll(
            data.map((item) => User.fromApiJson(item as Map<String, dynamic>)),
          );

          final meta = response.data['meta'] as Map<String, dynamic>?;
          final pagination = meta?['pagination'] as Map<String, dynamic>?;
          if (pagination != null) {
            lastPage = (pagination['last_page'] as num?)?.toInt() ?? 1;
          } else {
            lastPage = 1;
          }
        } else {
          break;
        }
        currentPage++;
      } while (currentPage <= lastPage);

      _studentUsers = allStudents;
      _mergeUserLists();
    } catch (e) {
      debugPrint('Error fetching students: $e');
    }
  }

  Future<void> addStudent({
    required String name,
    required String nis,
    required String classRoomId,
    required String password,
    required String passwordConfirmation,
    String? whatsappNumber,
    String? parentPhoneWa,
  }) async {
    try {
      final classroom = _classrooms.firstWhere((c) => c.id == classRoomId);
      final jurusan = classroom.jurusan;
      await _dio.post('/admin/students', data: {
        'name': name,
        'nis': nis,
        'class_room_id': classRoomId,
        'jurusan': jurusan,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'whatsapp_number': whatsappNumber,
        'parent_phone_wa': parentPhoneWa,
      });
      await fetchStudents();
    } catch (e) {
      if (e is DioException && e.response?.data != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menambahkan siswa.');
    }
  }

  Future<void> updateStudent({
    required String id,
    required String name,
    required String nis,
    required String classRoomId,
    String? password,
    String? passwordConfirmation,
    String? whatsappNumber,
    String? parentPhoneWa,
  }) async {
    try {
      final classroom = _classrooms.firstWhere((c) => c.id == classRoomId);
      final jurusan = classroom.jurusan;
      final data = <String, dynamic>{
        'name': name,
        'nis': nis,
        'class_room_id': classRoomId,
        'jurusan': jurusan,
        if (password != null && password.isNotEmpty) 'password': password,
        if (passwordConfirmation != null && passwordConfirmation.isNotEmpty)
          'password_confirmation': passwordConfirmation,
        'whatsapp_number': whatsappNumber,
        'parent_phone_wa': parentPhoneWa,
      };

      await _dio.patch('/admin/students/$id', data: data);
      await fetchStudents();
    } catch (e) {
      if (e is DioException && e.response?.data != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal memperbarui siswa.');
    }
  }

  Future<void> deleteStudent(String id) async {
    try {
      await _dio.delete('/admin/users/$id');
      await fetchStudents();
    } catch (e) {
      if (e is DioException && e.response?.data != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menghapus siswa.');
    }
  }

  // Admin Teacher Operations
  Future<void> fetchTeachers() async {
    try {
      final List<User> allTeachers = [];
      int currentPage = 1;
      int lastPage = 1;

      do {
        final response = await _dio.get('/admin/teachers', queryParameters: {'page': currentPage});
        if (response.statusCode == 200) {
          final data = response.data['data'] as List? ?? [];
          allTeachers.addAll(
            data.map((item) => User.fromApiJson(item as Map<String, dynamic>)),
          );

          final meta = response.data['meta'] as Map<String, dynamic>?;
          final pagination = meta?['pagination'] as Map<String, dynamic>?;
          if (pagination != null) {
            lastPage = (pagination['last_page'] as num?)?.toInt() ?? 1;
          } else {
            lastPage = 1;
          }
        } else {
          break;
        }
        currentPage++;
      } while (currentPage <= lastPage);

      _teacherUsers = allTeachers;
      _mergeUserLists();
    } catch (e) {
      debugPrint('Error fetching teachers: $e');
    }
  }

  Future<void> addTeacher({
    required String name,
    required String teacherRole,
    required String password,
    required String passwordConfirmation,
    required String nip,
    String? subject,
    String? waliKelas,
    String? whatsappNumber,
  }) async {
    try {
      await _dio.post('/admin/teachers', data: {
        'name': name,
        'teacher_role': teacherRole,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'nip': nip,
        'subject': subject,
        'wali_kelas': waliKelas,
        'whatsapp_number': whatsappNumber,
      });
      await fetchTeachers();
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      if (e.response?.data != null && e.response?.data['errors'] != null) {
        final errors = e.response!.data['errors'] as Map<String, dynamic>;
        throw Exception(errors.values.expand((v) => v as List).join('\n'));
      }
      throw Exception('Gagal menambahkan guru.');
    }
  }

  Future<void> updateTeacher({
    required String id,
    required String name,
    required String teacherRole,
    required String nip,
    String? subject,
    String? waliKelas,
    String? whatsappNumber,
    String? password,
    String? passwordConfirmation,
  }) async {
    try {
      await _dio.patch('/admin/teachers/$id', data: {
        'name': name,
        'teacher_role': teacherRole,
        'nip': nip,
        'subject': subject,
        'wali_kelas': waliKelas,
        'whatsapp_number': whatsappNumber,
        if (password != null && password.isNotEmpty) 'password': password,
        if (passwordConfirmation != null && passwordConfirmation.isNotEmpty)
          'password_confirmation': passwordConfirmation,
      });
      await fetchTeachers();
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      if (e.response?.data != null && e.response?.data['errors'] != null) {
        final errors = e.response!.data['errors'] as Map<String, dynamic>;
        throw Exception(errors.values.expand((v) => v as List).join('\n'));
      }
      throw Exception('Gagal mengubah data guru.');
    }
  }

  Future<void> deleteTeacher(String id) async {
    try {
      await _dio.delete('/admin/users/$id');
      _teacherUsers.removeWhere((u) => u.id == id);
      _mergeUserLists();
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal menghapus guru.');
    }
  }

  Future<String> importStudents(List<int> bytes, String filename) async {
    try {
      final formData = FormData.fromMap({
        'file': MultipartFile.fromBytes(bytes, filename: filename),
      });
      final response = await _dio.post('/admin/students/import', data: formData);
      await fetchStudents();
      return response.data['message'] ?? 'Import berhasil.';
    } on DioException catch (e) {
      if (e.response?.data != null && e.response?.data['message'] != null) {
        throw Exception(e.response!.data['message'].toString());
      }
      throw Exception('Gagal mengimport data.');
    }
  }

  // Bulk Operations — delegated to admin endpoints
  Future<void> bulkUpdateClass(List<String> studentIds, String newClassId) async {
    // The admin API may not have a bulk endpoint, update one by one
    for (var id in studentIds) {
      final student = _studentUsers.firstWhere((u) => u.id == id, orElse: () => User(id: '', name: '', email: '', role: 'siswa'));
      if (student.id.isNotEmpty) {
        try {
          await _dio.patch('/admin/students/$id', data: {
            'class_room_id': newClassId,
          });
        } catch (_) {}
      }
    }
    await fetchStudents();
  }

  Future<void> bulkDeleteByClass(String classId) async {
    final studentsInClass = _studentUsers.where((u) => u.classRoomId == classId).toList();
    for (var student in studentsInClass) {
      try {
        await _dio.delete('/admin/users/${student.id}');
      } catch (_) {}
    }
    _studentUsers.removeWhere((u) => u.classRoomId == classId);
    _mergeUserLists();
  }
}
