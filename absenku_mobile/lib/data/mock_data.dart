// lib/data/mock_data.dart

import '../models/models.dart';

class MockData {
  MockData._();

  static final List<ClassRoomModel> classrooms = [
    const ClassRoomModel(id: 1, name: 'XII RPL 1', major: 'Rekayasa Perangkat Lunak'),
    const ClassRoomModel(id: 2, name: 'XII RPL 2', major: 'Rekayasa Perangkat Lunak'),
    const ClassRoomModel(id: 3, name: 'XI TKJ 1', major: 'Teknik Komputer Jaringan'),
  ];

  static final List<UserModel> users = [
    const UserModel(id: 1, name: 'Administrator', email: 'admin@absenku.com', role: 'admin'),
    const UserModel(id: 2, name: 'Budi Santoso', email: 'budi@absenku.com', role: 'guru_walikelas', whatsappNumber: '081234567890'),
    const UserModel(id: 3, name: 'Siti Aminah', email: 'siti@absenku.com', role: 'petugas_piket', whatsappNumber: '081234567891'),
    const UserModel(id: 4, name: 'Ahmad Fauzan', email: 'fauzan@absenku.com', role: 'siswa', whatsappNumber: '081234567892'),
  ];

  static final List<TeacherModel> teachers = [
    const TeacherModel(id: 1, name: 'Budi Santoso, M.Pd.', email: 'budi@absenku.com', nip: '198501012010011001', whatsappNumber: '081234567890', subject: 'Pemrograman Mobile'),
    const TeacherModel(id: 2, name: 'Sri Wahyuni, S.Kom.', email: 'sri@absenku.com', nip: '198803152015042002', whatsappNumber: '085712345678', subject: 'Basis Data'),
  ];

  static final List<StudentModel> students = [
    StudentModel(id: 1, name: 'Ahmad Fauzan', email: 'fauzan@absenku.com', nisn: '0054321098', classRoom: classrooms[0], whatsappNumber: '081234567892'),
    StudentModel(id: 2, name: 'Rian Hidayat', email: 'rian@absenku.com', nisn: '0054321099', classRoom: classrooms[0], whatsappNumber: '081234567893'),
    StudentModel(id: 3, name: 'Lani Lestari', email: 'lani@absenku.com', nisn: '0054321100', classRoom: classrooms[1], whatsappNumber: '081234567894'),
  ];

  static List<AttendanceModel> attendanceHistory() {
    return [
      AttendanceModel(
        id: 1,
        student: students[0],
        date: '2026-06-12',
        timeIn: '06:45:12',
        timeOut: '15:30:00',
        status: 'present',
        latitude: -6.2088,
        longitude: 106.8456,
      ),
      AttendanceModel(
        id: 2,
        student: students[1],
        date: '2026-06-12',
        timeIn: '07:15:34',
        status: 'late',
        latitude: -6.2090,
        longitude: 106.8460,
      ),
      AttendanceModel(
        id: 3,
        student: students[2],
        date: '2026-06-12',
        status: 'absent',
      ),
    ];
  }

  static final List<LeaveRequestModel> leaveRequests = [
    LeaveRequestModel(
      id: 1,
      student: students[0],
      date: '2026-06-11',
      reason: 'Sakit demam tinggi, surat dokter dilampirkan.',
      status: 'approved',
      processedBy: 'Siti Aminah',
      note: 'Semoga lekas sembuh.',
    ),
    LeaveRequestModel(
      id: 2,
      student: students[1],
      date: '2026-06-13',
      reason: 'Ada acara keluarga di luar kota.',
      status: 'pending',
    ),
  ];

  static const SchoolSettingModel schoolSetting = SchoolSettingModel(
    schoolName: 'SMK Negeri 1 Jakarta',
    latitude: -6.2088,
    longitude: 106.8456,
    radius: 100.0,
    checkInStart: '06:00',
    checkInEnd: '07:00',
    checkOutStart: '15:00',
    checkOutEnd: '17:00',
    lateTolerance: 15,
  );
}
