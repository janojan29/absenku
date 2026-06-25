// lib/services/profile_service.dart
import '../data/mock_data.dart';
import '../models/models.dart';

class ProfileService {
  static UserModel getAdmin() => MockData.users.firstWhere((u) => u.role == 'admin');
}
