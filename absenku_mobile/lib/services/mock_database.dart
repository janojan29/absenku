import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';
import '../models/attendance.dart';
import '../core/config/app_config.dart';

class MockDatabase extends ChangeNotifier {
  static final MockDatabase _instance = MockDatabase._internal();
  factory MockDatabase() => _instance;
  MockDatabase._internal();

  SharedPreferences? _prefs;
  bool _initialized = false;

  // State Lists
  List<ClassRoom> _classrooms = [];
  List<User> _users = [];
  List<Attendance> _attendance = [];
  List<LeaveRequest> _leaveRequests = [];
  User? _currentUser;
  
  // Settings
  double _latitude = AppConfig.defaultLatitude;
  double _longitude = AppConfig.defaultLongitude;
  int _radiusMeters = AppConfig.defaultRadiusMeters;
  String _checkInStart = AppConfig.defaultCheckInStartTime;
  String _checkInEnd = AppConfig.defaultCheckInEndTime;
  String _checkOutStart = AppConfig.defaultCheckOutStartTime;
  String _checkOutEnd = AppConfig.defaultCheckOutEndTime;

  // Simulated Device Coordinates
  double _deviceLatitude = AppConfig.defaultLatitude;
  double _deviceLongitude = AppConfig.defaultLongitude;

  // Getters
  List<ClassRoom> get classrooms => _classrooms;
  List<User> get users => _users;
  List<Attendance> get attendance => _attendance;
  List<LeaveRequest> get leaveRequests => _leaveRequests;
  User? get currentUser => _currentUser;

  double get latitude => _latitude;
  double get longitude => _longitude;
  int get radiusMeters => _radiusMeters;
  String get checkInStart => _checkInStart;
  String get checkInEnd => _checkInEnd;
  String get checkOutStart => _checkOutStart;
  String get checkOutEnd => _checkOutEnd;

  double get deviceLatitude => _deviceLatitude;
  double get deviceLongitude => _deviceLongitude;

  void setDeviceLocation(double lat, double lng) {
    _deviceLatitude = lat;
    _deviceLongitude = lng;
    notifyListeners();
  }

  Future<void> init() async {
    if (_initialized) return;
    _prefs = await SharedPreferences.getInstance();
    
    // Load Classrooms
    final classroomsJson = _prefs?.getString('classrooms');
    if (classroomsJson != null) {
      final List decoded = json.decode(classroomsJson);
      _classrooms = decoded.map((item) => ClassRoom.fromJson(item)).toList();
    } else {
      _classrooms = [
        ClassRoom(id: 'c1', name: 'XI RPL 1', jurusan: 'Rekayasa Perangkat Lunak'),
        ClassRoom(id: 'c2', name: 'XI TSM 1', jurusan: 'Teknik Sepeda Motor'),
        ClassRoom(id: 'c3', name: 'X RPL 1', jurusan: 'Rekayasa Perangkat Lunak'),
        ClassRoom(id: 'c4', name: 'XII RPL 1', jurusan: 'Rekayasa Perangkat Lunak'),
      ];
      await _saveClassrooms();
    }

    // Load Users
    final usersJson = _prefs?.getString('users');
    if (usersJson != null) {
      final List decoded = json.decode(usersJson);
      _users = decoded.map((item) => User.fromJson(item)).toList();
    } else {
      _users = [
        User(id: 'u1', name: 'Admin Absenku', email: 'admin@gmail.com', role: 'admin'),
        User(id: 'u2', name: 'Budi Santoso, S.Pd.', email: 'budi@gmail.com', role: 'guru_piket', nip: '198205122010011003'),
        User(id: 'u3', name: 'Rian Hidayat', email: 'rian@gmail.com', role: 'siswa', nis: '12345', classRoomId: 'c1'),
        User(id: 'u4', name: 'Aisyah Putri', email: 'aisyah@gmail.com', role: 'siswa', nis: '12346', classRoomId: 'c1'),
        User(id: 'u5', name: 'Dedi Wijaya', email: 'dedi@gmail.com', role: 'siswa', nis: '12347', classRoomId: 'c2'),
        User(id: 'u6', name: 'Siti Aminah', email: 'siti@gmail.com', role: 'siswa', nis: '12348', classRoomId: 'c2'),
      ];
      await _saveUsers();
    }

    // Load Settings
    _latitude = _prefs?.getDouble('latitude') ?? AppConfig.defaultLatitude;
    _longitude = _prefs?.getDouble('longitude') ?? AppConfig.defaultLongitude;
    _radiusMeters = _prefs?.getInt('radiusMeters') ?? AppConfig.defaultRadiusMeters;
    _checkInStart = _prefs?.getString('checkInStart') ?? AppConfig.defaultCheckInStartTime;
    _checkInEnd = _prefs?.getString('checkInEnd') ?? AppConfig.defaultCheckInEndTime;
    _checkOutStart = _prefs?.getString('checkOutStart') ?? AppConfig.defaultCheckOutStartTime;
    _checkOutEnd = _prefs?.getString('checkOutEnd') ?? AppConfig.defaultCheckOutEndTime;

    // Load Attendance
    final attendanceJson = _prefs?.getString('attendance');
    if (attendanceJson != null) {
      final List decoded = json.decode(attendanceJson);
      _attendance = decoded.map((item) => Attendance.fromJson(item)).toList();
    } else {
      // Generate some historical logs for the past 7 days (excluding today) for our students
      _attendance = [];
      final now = DateTime.now();
      for (int i = 1; i <= 7; i++) {
        final date = DateTime(now.year, now.month, now.day - i);
        // Exclude Sunday (day 7 of week in Dart is Sunday)
        if (date.weekday == DateTime.sunday) continue;
        
        // Rian (u3): Present every day, some late
        _attendance.add(Attendance(
          id: 'a_u3_$i',
          userId: 'u3',
          checkInAt: DateTime(date.year, date.month, date.day, 6, 45 + (i % 3 == 0 ? 30 : 0)), // i % 3 == 0 is late (07:15)
          checkOutAt: DateTime(date.year, date.month, date.day, 15, 5 + (i * 2)),
          date: date,
          status: i % 3 == 0 ? 'late' : 'present',
          latitude: _latitude,
          longitude: _longitude,
        ));

        // Aisyah (u4): Had a leave on day 2
        if (i == 2) {
          _attendance.add(Attendance(
            id: 'a_u4_$i',
            userId: 'u4',
            date: date,
            status: 'leave',
          ));
        } else {
          _attendance.add(Attendance(
            id: 'a_u4_$i',
            userId: 'u4',
            checkInAt: DateTime(date.year, date.month, date.day, 6, 50),
            checkOutAt: DateTime(date.year, date.month, date.day, 15, 10),
            date: date,
            status: 'present',
            latitude: _latitude,
            longitude: _longitude,
          ));
        }

        // Dedi (u5): Absent (Alfa) on day 4
        if (i == 4) {
          _attendance.add(Attendance(
            id: 'a_u5_$i',
            userId: 'u5',
            date: date,
            status: 'absent',
          ));
        } else {
          _attendance.add(Attendance(
            id: 'a_u5_$i',
            userId: 'u5',
            checkInAt: DateTime(date.year, date.month, date.day, 7, 10), // Late
            checkOutAt: DateTime(date.year, date.month, date.day, 15, 2),
            date: date,
            status: 'late',
            latitude: _latitude,
            longitude: _longitude,
          ));
        }
      }
      await _saveAttendance();
    }

    // Load Leave Requests
    final leavesJson = _prefs?.getString('leaveRequests');
    if (leavesJson != null) {
      final List decoded = json.decode(leavesJson);
      _leaveRequests = decoded.map((item) => LeaveRequest.fromJson(item)).toList();
    } else {
      final now = DateTime.now();
      _leaveRequests = [
        // u4 has a leave request for yesterday (approved)
        LeaveRequest(
          id: 'l1',
          userId: 'u4',
          type: 'absent',
          date: DateTime(now.year, now.month, now.day - 2),
          reason: 'sick',
          keterangan: 'Sakit demam, disarankan dokter istirahat.',
          status: 'approved',
          decidedAt: DateTime(now.year, now.month, now.day - 2, 8, 30),
          decidedById: 'u2',
          decisionNote: 'ACC. Semoga lekas sembuh.',
        ),
        // u5 has a pending leave request for Today
        LeaveRequest(
          id: 'l2',
          userId: 'u5',
          type: 'absent',
          date: DateTime(now.year, now.month, now.day),
          reason: 'urgent',
          keterangan: 'Ada urusan keluarga penting ke luar kota.',
          status: 'pending',
        ),
      ];
      await _saveLeaveRequests();
    }

    // Restore login session if saved
    final savedUserId = _prefs?.getString('currentUserId');
    if (savedUserId != null) {
      _currentUser = _users.firstWhere((u) => u.id == savedUserId, orElse: () => _users[2]); // Default to Rian if not found
    }

    _initialized = true;
    notifyListeners();
  }

  // Auth Operations
  Future<User?> login(String email, String password) async {
    // Basic password matching, for testing email is user email, password is "password"
    final matchedUser = _users.firstWhere(
      (u) => u.email.toLowerCase().trim() == email.toLowerCase().trim() && password == 'password',
      orElse: () => throw Exception('Email atau password salah. (Gunakan password "password")'),
    );
    _currentUser = matchedUser;
    await _prefs?.setString('currentUserId', matchedUser.id);
    notifyListeners();
    return matchedUser;
  }

  Future<void> logout() async {
    _currentUser = null;
    await _prefs?.remove('currentUserId');
    notifyListeners();
  }

  // Attendance Operations
  Future<void> checkIn(double lat, double lng) async {
    if (_currentUser == null || _currentUser!.role != 'siswa') return;
    
    final today = DateTime.now();
    final todayStart = DateTime(today.year, today.month, today.day);
    
    // Check if check-in already exists
    final idx = _attendance.indexWhere(
        (a) => a.userId == _currentUser!.id && 
               a.date.year == today.year && 
               a.date.month == today.month && 
               a.date.day == today.day);

    // Determine status (present or late)
    final checkInLimit = _timeToDateTime(today, _checkInEnd);
    final status = today.isAfter(checkInLimit) ? 'late' : 'present';

    if (idx != -1) {
      // Update check in time
      if (_attendance[idx].checkInAt == null) {
        _attendance[idx] = _attendance[idx].copyWith(
          checkInAt: today,
          status: status,
          latitude: lat,
          longitude: lng,
        );
      }
    } else {
      // Create new record
      _attendance.add(Attendance(
        id: 'att_${_currentUser!.id}_${today.millisecondsSinceEpoch}',
        userId: _currentUser!.id,
        checkInAt: today,
        date: todayStart,
        status: status,
        latitude: lat,
        longitude: lng,
      ));
    }
    
    await _saveAttendance();
    notifyListeners();
  }

  Future<void> checkOut(double lat, double lng) async {
    if (_currentUser == null || _currentUser!.role != 'siswa') return;
    
    final today = DateTime.now();
    final idx = _attendance.indexWhere(
        (a) => a.userId == _currentUser!.id && 
               a.date.year == today.year && 
               a.date.month == today.month && 
               a.date.day == today.day);

    if (idx != -1) {
      _attendance[idx] = _attendance[idx].copyWith(
        checkOutAt: today,
      );
    } else {
      // checkout without checkin? (should not happen normally but let's handle it)
      _attendance.add(Attendance(
        id: 'att_${_currentUser!.id}_${today.millisecondsSinceEpoch}',
        userId: _currentUser!.id,
        checkOutAt: today,
        date: DateTime(today.year, today.month, today.day),
        status: 'present',
        latitude: lat,
        longitude: lng,
      ));
    }

    await _saveAttendance();
    notifyListeners();
  }

  // Leave Requests Operations
  Future<void> submitLeaveRequest(String type, DateTime date, String reason, String keterangan) async {
    if (_currentUser == null || _currentUser!.role != 'siswa') return;

    // Check if already submitted for this date
    final dateOnly = DateTime(date.year, date.month, date.day);
    final exists = _leaveRequests.any(
      (l) => l.userId == _currentUser!.id && 
             l.date.year == dateOnly.year && 
             l.date.month == dateOnly.month && 
             l.date.day == dateOnly.day
    );

    if (exists) {
      throw Exception('Anda sudah mengajukan izin untuk tanggal ini.');
    }

    final newReq = LeaveRequest(
      id: 'leave_${_currentUser!.id}_${DateTime.now().millisecondsSinceEpoch}',
      userId: _currentUser!.id,
      type: type,
      date: dateOnly,
      reason: reason,
      keterangan: keterangan,
      status: 'pending',
    );

    _leaveRequests.insert(0, newReq); // Add to beginning of list
    await _saveLeaveRequests();
    notifyListeners();
  }

  Future<void> approveLeaveRequest(String id, String decidedById, String note) async {
    final idx = _leaveRequests.indexWhere((l) => l.id == id);
    if (idx == -1) return;

    final updated = _leaveRequests[idx].copyWith(
      status: 'approved',
      decidedAt: DateTime.now(),
      decidedById: decidedById,
      decisionNote: note,
    );
    _leaveRequests[idx] = updated;
    await _saveLeaveRequests();

    // Lock attendance for this date as 'leave'
    final leaveDate = updated.date;
    final attIdx = _attendance.indexWhere(
      (a) => a.userId == updated.userId &&
             a.date.year == leaveDate.year &&
             a.date.month == leaveDate.month &&
             a.date.day == leaveDate.day
    );

    if (attIdx != -1) {
      _attendance[attIdx] = _attendance[attIdx].copyWith(status: 'leave');
    } else {
      _attendance.add(Attendance(
        id: 'att_${updated.userId}_${leaveDate.millisecondsSinceEpoch}',
        userId: updated.userId,
        date: leaveDate,
        status: 'leave',
      ));
    }
    await _saveAttendance();
    notifyListeners();
  }

  Future<void> rejectLeaveRequest(String id, String decidedById, String note) async {
    final idx = _leaveRequests.indexWhere((l) => l.id == id);
    if (idx == -1) return;

    _leaveRequests[idx] = _leaveRequests[idx].copyWith(
      status: 'rejected',
      decidedAt: DateTime.now(),
      decidedById: decidedById,
      decisionNote: note,
    );
    await _saveLeaveRequests();
    notifyListeners();
  }

  // Admin Classroom Operations
  Future<void> addClassRoom(String name, String jurusan) async {
    final newClass = ClassRoom(
      id: 'c_${DateTime.now().millisecondsSinceEpoch}',
      name: name,
      jurusan: jurusan,
    );
    _classrooms.add(newClass);
    await _saveClassrooms();
    notifyListeners();
  }

  Future<void> deleteClassRoom(String id) async {
    _classrooms.removeWhere((c) => c.id == id);
    await _saveClassrooms();
    notifyListeners();
  }

  // Admin Student Operations
  Future<void> addStudent(String name, String nis, String classRoomId, String email) async {
    final newStudent = User(
      id: 'u_${DateTime.now().millisecondsSinceEpoch}',
      name: name,
      email: email,
      role: 'siswa',
      nis: nis,
      classRoomId: classRoomId,
    );
    _users.add(newStudent);
    await _saveUsers();
    notifyListeners();
  }

  Future<void> updateStudent(String id, String name, String nis, String classRoomId, String email) async {
    final idx = _users.indexWhere((u) => u.id == id);
    if (idx == -1) return;

    _users[idx] = User(
      id: id,
      name: name,
      email: email,
      role: 'siswa',
      nis: nis,
      classRoomId: classRoomId,
    );
    await _saveUsers();
    notifyListeners();
  }

  Future<void> deleteStudent(String id) async {
    _users.removeWhere((u) => u.id == id);
    _attendance.removeWhere((a) => a.userId == id);
    _leaveRequests.removeWhere((l) => l.userId == id);
    await _saveUsers();
    await _saveAttendance();
    await _saveLeaveRequests();
    notifyListeners();
  }

  // Admin Teacher Operations
  Future<void> addTeacher(String name, String nip, String email) async {
    final newTeacher = User(
      id: 'u_${DateTime.now().millisecondsSinceEpoch}',
      name: name,
      email: email,
      role: 'guru_piket',
      nip: nip,
    );
    _users.add(newTeacher);
    await _saveUsers();
    notifyListeners();
  }

  Future<void> updateTeacher(String id, String name, String nip, String email) async {
    final idx = _users.indexWhere((u) => u.id == id);
    if (idx == -1) return;

    _users[idx] = User(
      id: id,
      name: name,
      email: email,
      role: 'guru_piket',
      nip: nip,
    );
    await _saveUsers();
    notifyListeners();
  }

  Future<void> deleteTeacher(String id) async {
    _users.removeWhere((u) => u.id == id);
    await _saveUsers();
    notifyListeners();
  }

  // Admin Settings Operations
  Future<void> updateSettings({
    required double latitude,
    required double longitude,
    required int radiusMeters,
    required String checkInStart,
    required String checkInEnd,
    required String checkOutStart,
    required String checkOutEnd,
  }) async {
    _latitude = latitude;
    _longitude = longitude;
    _radiusMeters = radiusMeters;
    _checkInStart = checkInStart;
    _checkInEnd = checkInEnd;
    _checkOutStart = checkOutStart;
    _checkOutEnd = checkOutEnd;

    await _prefs?.setDouble('latitude', latitude);
    await _prefs?.setDouble('longitude', longitude);
    await _prefs?.setInt('radiusMeters', radiusMeters);
    await _prefs?.setString('checkInStart', checkInStart);
    await _prefs?.setString('checkInEnd', checkInEnd);
    await _prefs?.setString('checkOutStart', checkOutStart);
    await _prefs?.setString('checkOutEnd', checkOutEnd);

    notifyListeners();
  }

  // Bulk Actions
  Future<void> bulkUpdateClass(List<String> studentIds, String newClassId) async {
    for (var id in studentIds) {
      final idx = _users.indexWhere((u) => u.id == id);
      if (idx != -1) {
        _users[idx] = User(
          id: _users[idx].id,
          name: _users[idx].name,
          email: _users[idx].email,
          role: 'siswa',
          nis: _users[idx].nis,
          classRoomId: newClassId,
        );
      }
    }
    await _saveUsers();
    notifyListeners();
  }

  Future<void> bulkDeleteByClass(String classId) async {
    final studentIds = _users.where((u) => u.classRoomId == classId).map((u) => u.id).toList();
    _users.removeWhere((u) => u.classRoomId == classId);
    _attendance.removeWhere((a) => studentIds.contains(a.userId));
    _leaveRequests.removeWhere((l) => studentIds.contains(l.userId));
    await _saveUsers();
    await _saveAttendance();
    await _saveLeaveRequests();
    notifyListeners();
  }

  // Helper Serialization Methods
  Future<void> _saveClassrooms() async {
    final list = _classrooms.map((c) => c.toJson()).toList();
    await _prefs?.setString('classrooms', json.encode(list));
  }

  Future<void> _saveUsers() async {
    final list = _users.map((u) => u.toJson()).toList();
    await _prefs?.setString('users', json.encode(list));
  }

  Future<void> _saveAttendance() async {
    final list = _attendance.map((a) => a.toJson()).toList();
    await _prefs?.setString('attendance', json.encode(list));
  }

  Future<void> _saveLeaveRequests() async {
    final list = _leaveRequests.map((l) => l.toJson()).toList();
    await _prefs?.setString('leaveRequests', json.encode(list));
  }

  DateTime _timeToDateTime(DateTime date, String timeStr) {
    final parts = timeStr.split(':');
    final hour = int.parse(parts[0]);
    final minute = int.parse(parts[1]);
    return DateTime(date.year, date.month, date.day, hour, minute);
  }
}
