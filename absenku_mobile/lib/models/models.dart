// lib/models/models.dart

class UserModel {
  final int id;
  final String name;
  final String email;
  final String role;
  final String? whatsappNumber;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.whatsappNumber,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      role: json['role'] as String,
      whatsappNumber: json['whatsapp_number'] as String?,
    );
  }
}

class ClassRoomModel {
  final int id;
  final String name;
  final String major;

  const ClassRoomModel({
    required this.id,
    required this.name,
    required this.major,
  });

  factory ClassRoomModel.fromJson(Map<String, dynamic> json) {
    return ClassRoomModel(
      id: json['id'] as int,
      name: json['name'] as String,
      major: (json['jurusan'] ?? json['major'] ?? '') as String,
    );
  }
}

class StudentModel {
  final int id;
  final String name;
  final String email;
  final String nisn;
  final ClassRoomModel classRoom;
  final String? whatsappNumber;

  const StudentModel({
    required this.id,
    required this.name,
    required this.email,
    required this.nisn,
    required this.classRoom,
    this.whatsappNumber,
  });

  factory StudentModel.fromJson(Map<String, dynamic> json) {
    final profile = json['student_profile'] as Map<String, dynamic>?;
    final classroomJson = profile?['class_room'] as Map<String, dynamic>?;
    return StudentModel(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      nisn: (profile?['nis'] ?? json['nisn'] ?? '') as String,
      classRoom: classroomJson != null
          ? ClassRoomModel.fromJson(classroomJson)
          : const ClassRoomModel(id: 0, name: 'Tidak ada kelas', major: ''),
      whatsappNumber: json['whatsapp_number'] as String?,
    );
  }
}

class TeacherModel {
  final int id;
  final String name;
  final String email;
  final String nip;
  final String? whatsappNumber;
  final String subject;

  const TeacherModel({
    required this.id,
    required this.name,
    required this.email,
    required this.nip,
    this.whatsappNumber,
    required this.subject,
  });

  factory TeacherModel.fromJson(Map<String, dynamic> json) {
    final teacher = json['teacher'] as Map<String, dynamic>?;
    return TeacherModel(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      nip: (teacher?['nip'] ?? json['nip'] ?? '') as String,
      whatsappNumber: json['whatsapp_number'] as String?,
      subject: (teacher?['subject'] ?? json['subject'] ?? '') as String,
    );
  }
}

class AttendanceModel {
  final int id;
  final StudentModel student;
  final String date;
  final String? timeIn;
  final String? timeOut;
  final String status; // 'present', 'late', 'absent', 'leave'
  final double? latitude;
  final double? longitude;

  const AttendanceModel({
    required this.id,
    required this.student,
    required this.date,
    this.timeIn,
    this.timeOut,
    required this.status,
    this.latitude,
    this.longitude,
  });

  factory AttendanceModel.fromJson(Map<String, dynamic> json) {
    final studentJson = json['user'] as Map<String, dynamic>?;
    return AttendanceModel(
      id: json['id'] as int,
      student: studentJson != null
          ? StudentModel.fromJson(studentJson)
          : const StudentModel(id: 0, name: 'Siswa', email: '', nisn: '', classRoom: ClassRoomModel(id: 0, name: '', major: '')),
      date: json['date'] as String,
      timeIn: json['check_in_at'] as String?,
      timeOut: json['check_out_at'] as String?,
      status: json['status'] as String,
      latitude: json['latitude'] != null ? double.tryParse(json['latitude'].toString()) : null,
      longitude: json['longitude'] != null ? double.tryParse(json['longitude'].toString()) : null,
    );
  }
}

class LeaveRequestModel {
  final int id;
  final StudentModel student;
  final String date;
  final String reason;
  final String status; // 'pending', 'approved', 'rejected'
  final String? note;
  final String? processedBy;

  const LeaveRequestModel({
    required this.id,
    required this.student,
    required this.date,
    required this.reason,
    required this.status,
    this.note,
    this.processedBy,
  });

  factory LeaveRequestModel.fromJson(Map<String, dynamic> json) {
    final studentJson = json['user'] as Map<String, dynamic>?;
    return LeaveRequestModel(
      id: json['id'] as int,
      student: studentJson != null
          ? StudentModel.fromJson(studentJson)
          : const StudentModel(id: 0, name: 'Siswa', email: '', nisn: '', classRoom: ClassRoomModel(id: 0, name: '', major: '')),
      date: json['date'] as String,
      reason: json['reason'] as String,
      status: json['status'] as String,
      note: json['decision_note'] as String?,
      processedBy: json['decided_by_user']?['name'] as String?,
    );
  }
}

class SchoolSettingModel {
  final String schoolName;
  final double latitude;
  final double longitude;
  final double radius;
  final String checkInStart;
  final String checkInEnd;
  final String checkOutStart;
  final String checkOutEnd;
  final int lateTolerance;

  const SchoolSettingModel({
    required this.schoolName,
    required this.latitude,
    required this.longitude,
    required this.radius,
    required this.checkInStart,
    required this.checkInEnd,
    required this.checkOutStart,
    required this.checkOutEnd,
    required this.lateTolerance,
  });

  factory SchoolSettingModel.fromJson(Map<String, dynamic> json) {
    return SchoolSettingModel(
      schoolName: (json['name'] ?? '') as String,
      latitude: double.parse((json['latitude'] ?? '0').toString()),
      longitude: double.parse((json['longitude'] ?? '0').toString()),
      radius: double.parse((json['radius_meters'] ?? '0').toString()),
      checkInStart: (json['check_in_start_time'] ?? '') as String,
      checkInEnd: (json['check_in_end_time'] ?? '') as String,
      checkOutStart: (json['check_out_start_time'] ?? '') as String,
      checkOutEnd: (json['check_out_end_time'] ?? '') as String,
      lateTolerance: json['late_tolerance_minutes'] as int? ?? 0,
    );
  }
}
