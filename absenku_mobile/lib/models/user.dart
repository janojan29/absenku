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
  final String? whatsappNumber;
  final String? parentPhoneWa;
  final String? jurusan;
  final String? subject; // For teachers
  final String? waliKelas; // For teachers

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.nip,
    this.nis,
    this.classRoomId,
    this.classRoomName,
    this.whatsappNumber,
    this.parentPhoneWa,
    this.jurusan,
    this.subject,
    this.waliKelas,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role,
        'nip': nip,
        'nis': nis,
        'classRoomId': classRoomId,
        'whatsappNumber': whatsappNumber,
        'parentPhoneWa': parentPhoneWa,
        'jurusan': jurusan,
        'subject': subject,
        'waliKelas': waliKelas,
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
        whatsappNumber: json['whatsappNumber'] as String? ?? json['whatsapp_number'] as String?,
        parentPhoneWa: json['parentPhoneWa'] as String? ?? json['parent_phone_wa'] as String?,
        jurusan: json['jurusan'] as String?,
        subject: json['subject'] as String?,
        waliKelas: json['waliKelas'] as String? ?? json['wali_kelas'] as String?,
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

    String? classRoomName;
    String? classRoomId;
    String? jurusan;
    String? parentPhoneWa;
    if (studentProfile != null) {
      classRoomId = studentProfile['class_room_id']?.toString();
      jurusan = studentProfile['jurusan'] as String?;
      parentPhoneWa = studentProfile['parent_phone_wa'] as String?;
      final classRoom = studentProfile['class_room'] as Map<String, dynamic>?;
      if (classRoom != null) {
        classRoomName = classRoom['name'] as String?;
      }
    }

    return User(
      id: json['id'].toString(),
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      role: role,
      nip: teacher?['nip'] as String?,
      nis: studentProfile?['nis'] as String?,
      classRoomId: classRoomId,
      classRoomName: classRoomName,
      whatsappNumber: json['whatsapp_number'] as String?,
      parentPhoneWa: parentPhoneWa,
      jurusan: jurusan,
      subject: teacher?['subject'] as String?,
      waliKelas: teacher?['wali_kelas'] as String?,
    );
  }
}
