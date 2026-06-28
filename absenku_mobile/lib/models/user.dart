class ClassRoom {
  final String id;
  final String name;
  final String jurusan;

  ClassRoom({
    required this.id,
    required this.name,
    required this.jurusan,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'jurusan': jurusan,
      };

  factory ClassRoom.fromJson(Map<String, dynamic> json) => ClassRoom(
        id: json['id'].toString(),
        name: json['name'] as String,
        jurusan: json['jurusan'] as String? ?? '-',
      );
}

class User {
  final String id;
  final String name;
  final String email;
  final String role; // 'admin', 'petugas_piket', 'siswa', 'guru', 'guru_walikelas'
  final String? nip; // For teachers
  final String? nis; // For students
  final String? classRoomId; // For students
  final String? classRoomName; // For students (from nested class_room)

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.nip,
    this.nis,
    this.classRoomId,
    this.classRoomName,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role,
        'nip': nip,
        'nis': nis,
        'classRoomId': classRoomId,
      };

  /// Parse User from mock-style flat JSON (backwards compatible)
  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json['id'].toString(),
        name: json['name'] as String,
        email: json['email'] as String,
        role: json['role'] as String? ?? json['role_name'] as String? ?? 'siswa',
        nip: json['nip'] as String?,
        nis: json['nis'] as String?,
        classRoomId: json['classRoomId']?.toString() ?? json['class_room_id']?.toString(),
      );

  /// Parse User from Laravel API response (nested structure with student_profile / teacher)
  factory User.fromApiJson(Map<String, dynamic> json) {
    final studentProfile = json['student_profile'] as Map<String, dynamic>?;
    final teacher = json['teacher'] as Map<String, dynamic>?;

    // Determine role — API returns role_name as a string
    String role = 'siswa';
    if (json['role_name'] != null) {
      role = json['role_name'] as String;
    } else if (json['roles'] != null && (json['roles'] as List).isNotEmpty) {
      role = (json['roles'] as List).first.toString();
    }

    // Map 'guru_walikelas' and 'guru' roles to the display-compatible 'guru_piket' for teacher screens
    // (The app's routing uses 'guru_piket' for teacher dashboard access)
    final normalizedRole = _normalizeRole(role);

    String? classRoomName;
    String? classRoomId;
    if (studentProfile != null) {
      classRoomId = studentProfile['class_room_id']?.toString();
      final classRoom = studentProfile['class_room'] as Map<String, dynamic>?;
      if (classRoom != null) {
        classRoomName = classRoom['name'] as String?;
      }
    }

    return User(
      id: json['id'].toString(),
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      role: normalizedRole,
      nip: teacher?['nip'] as String?,
      nis: studentProfile?['nis'] as String?,
      classRoomId: classRoomId,
      classRoomName: classRoomName,
    );
  }

  static String _normalizeRole(String role) {
    // The Flutter app uses 'guru_piket' for all teacher/picket roles
    if (role == 'petugas_piket' || role == 'guru' || role == 'guru_walikelas') {
      return 'guru_piket';
    }
    return role;
  }
}
