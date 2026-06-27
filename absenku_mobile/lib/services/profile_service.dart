import 'mock_database.dart';
import '../models/user.dart';

class ProfileService {
  final MockDatabase _db = MockDatabase();

  User? getCurrentUser() => _db.currentUser;

  Future<void> logout() async {
    await _db.logout();
  }
}
