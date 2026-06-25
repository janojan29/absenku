// lib/services/attendance_service.dart
import '../data/mock_data.dart';
import '../models/models.dart';

class AttendanceService {
  static List<AttendanceModel> getHistory() => MockData.attendanceHistory();
}
