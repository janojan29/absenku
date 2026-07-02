// File ini berisi layanan data guru.
// Digunakan untuk mengolah informasi guru, data kelas, dan data pendukung yang muncul di layar guru.

import 'mock_database.dart';
import '../models/attendance.dart';

class TeacherService {
  final MockDatabase _db = MockDatabase();

  List<LeaveRequest> getPendingRequests() {
    return _db.leaveRequests.where((l) => l.status == 'pending').toList();
  }

  List<LeaveRequest> getProcessedRequests() {
    return _db.leaveRequests.where((l) => l.status != 'pending').toList();
  }

  Future<void> approveLeave(String id, String decidedById, String note) async {
    await _db.approveLeaveRequest(id, decidedById, note);
  }

  Future<void> rejectLeave(String id, String decidedById, String note) async {
    await _db.rejectLeaveRequest(id, decidedById, note);
  }
}
