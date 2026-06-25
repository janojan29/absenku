// lib/services/teacher_service.dart
import '../data/mock_data.dart';
import '../models/models.dart';

class TeacherService {
  static List<TeacherModel> getTeachers() => MockData.teachers;
}
