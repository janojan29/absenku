class AppConfig {
  static const String appName = 'Absenku Mobile';

  // API Base URL — ngrok tunnel to Laravel backend
  static const String apiBaseUrl = 'https://convene-radiantly-numeric.ngrok-free.dev/api';

  // Default School Location & Settings (fallback values before API data loads)
  static const double defaultLatitude = -6.2088;
  static const double defaultLongitude = 106.8456;
  static const int defaultRadiusMeters = 100;

  static const String defaultCheckInStartTime = '06:00';
  static const String defaultCheckInEndTime = '08:00';
  static const String defaultCheckOutStartTime = '15:00';
  static const String defaultCheckOutEndTime = '17:00';
}
