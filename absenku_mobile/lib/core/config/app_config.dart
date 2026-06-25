import 'package:flutter/foundation.dart';

class AppConfig {
  static const String appName = 'Absenku';
  static const String version = '1.0.0';

  // Base API URL default (Localhost via ADB reverse)
  static const String defaultApiUrl = 'http://127.0.0.1:8000/api';
  
  // Fallback local API URL (Android Emulator uses 10.0.2.2 for localhost)
  static const String fallbackApiUrl = kIsWeb ? 'http://localhost:8000/api' : 'http://10.0.2.2:8000/api';
}
