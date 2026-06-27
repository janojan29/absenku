import 'dart:math';
import 'mock_database.dart';

class AttendanceService {
  final MockDatabase _db = MockDatabase();

  double calculateDistance(double lat1, double lon1, double lat2, double lon2) {
    const double r = 6371000; // Earth's radius in meters
    final double dLat = _degToRad(lat2 - lat1);
    final double dLon = _degToRad(lon2 - lon1);
    final double a = sin(dLat / 2) * sin(dLat / 2) +
        cos(_degToRad(lat1)) *
            cos(_degToRad(lat2)) *
            sin(dLon / 2) *
            sin(dLon / 2);
    final double c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return r * c;
  }

  double _degToRad(double deg) {
    return deg * (pi / 180);
  }

  bool isInRange(double deviceLat, double deviceLng) {
    final distance = calculateDistance(
      _db.latitude,
      _db.longitude,
      deviceLat,
      deviceLng,
    );
    return distance <= _db.radiusMeters;
  }

  Future<void> checkIn(double lat, double lng) async {
    await _db.checkIn(lat, lng);
  }

  Future<void> checkOut(double lat, double lng) async {
    await _db.checkOut(lat, lng);
  }
}
