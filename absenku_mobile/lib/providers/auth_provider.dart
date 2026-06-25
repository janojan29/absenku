// lib/providers/auth_provider.dart
import 'package:flutter/foundation.dart';
import '../models/models.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  UserModel? _currentUser;
  bool _isLoading = false;

  UserModel? get currentUser => _currentUser;
  bool get isLoading => _isLoading;
  bool get isAuthenticated => _currentUser != null;

  AuthProvider() {
    _loadPersistedUser();
  }

  Future<void> _loadPersistedUser() async {
    if (ApiService.token != null) {
      _isLoading = true;
      notifyListeners();
      try {
        final userData = await ApiService.getProfile();
        _currentUser = UserModel.fromJson(userData);
      } catch (e) {
        // Token expired or server unreachable
        await ApiService.setToken(null);
        _currentUser = null;
      } finally {
        _isLoading = false;
        notifyListeners();
      }
    }
  }

  Future<bool> login(String identifier, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final res = await ApiService.login(identifier, password);
      if (res['success'] == true) {
        _currentUser = UserModel.fromJson(res['user']);
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      if (kDebugMode) {
        print('Login API Error: $e');
      }
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  // Pre-fill demo accounts and perform actual login
  Future<bool> loginDemo(String role) async {
    String identifier = '';
    String password = 'password123';

    switch (role) {
      case 'admin':
        identifier = 'admin@sekolah.local';
        break;
      case 'petugas_piket':
        identifier = 'piket1@sekolah.local';
        break;
      case 'guru_walikelas':
        // A standard teacher username or NIP if they have one seeded.
        // We can fall back to the first seeded teacher or let user type it.
        identifier = 'piket1@sekolah.local'; // Picket also has teacher dashboard privileges
        break;
      case 'siswa':
        identifier = 'siswa@sekolah.local';
        break;
      default:
        return false;
    }

    return await login(identifier, password);
  }

  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();
    
    await ApiService.logout();
    _currentUser = null;
    
    _isLoading = false;
    notifyListeners();
  }
}
