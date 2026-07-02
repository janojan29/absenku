// File ini berisi layanan absensi.
// Digunakan untuk mengambil data kehadiran, memproses status hadir, dan mempermudah pengelolaan log absensi.

import 'dart:math';
import 'mock_database.dart';
import 'package:geolocator/geolocator.dart';

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

  Future<Position> getCurrentLocation() async {
    bool serviceEnabled;
    LocationPermission permission;

    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return Future.error('Layanan lokasi (GPS) tidak aktif. Mohon aktifkan GPS Anda.');
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return Future.error('Izin lokasi ditolak.');
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return Future.error('Izin lokasi ditolak secara permanen. Mohon izinkan melalui pengaturan HP Anda.');
    }

    return await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.best,
      ),
    );
  }

  /// Anti fake GPS check using geolocator
  Future<bool> isUsingFakeGps(Position position, List<Position> samples) async {
    if (position.isMocked) return true;

    if (samples.length >= 2) {
      bool identical = true;
      for (int i = 1; i < samples.length; i++) {
        if (samples[i].latitude != samples[0].latitude || 
            samples[i].longitude != samples[0].longitude) {
          identical = false;
          break;
        }
      }
      if (identical) return true;
    }

    return false;
  }

  Future<List<Position>> _collectGpsSamples() async {
    final List<Position> samples = [];
    final subscription = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.best,
        distanceFilter: 0,
      ),
    ).listen((pos) {
      samples.add(pos);
    });

    await Future.delayed(const Duration(seconds: 3));
    await subscription.cancel();
    return samples;
  }

  /// Check in via API — sends coordinates to Laravel
  Future<String> checkIn(double? lat, double? lng) async {
    final position = await getCurrentLocation();
    final samples = await _collectGpsSamples();

    if (await isUsingFakeGps(position, samples)) {
      throw Exception('Sistem mendeteksi penggunaan Fake GPS (Lokasi tidak natural/statis). Silakan matikan Fake GPS Anda.');
    }
    _db.setDeviceLocation(position.latitude, position.longitude);
    
    final samplesJson = samples.map((p) => {'lat': p.latitude, 'lng': p.longitude}).toList();
    return await _db.checkIn(position.latitude, position.longitude, accuracy: position.accuracy, samples: samplesJson);
  }

  /// Check out via API — sends coordinates to Laravel
  Future<String> checkOut(double? lat, double? lng) async {
    final position = await getCurrentLocation();
    final samples = await _collectGpsSamples();

    if (await isUsingFakeGps(position, samples)) {
      throw Exception('Sistem mendeteksi penggunaan Fake GPS (Lokasi tidak natural/statis). Silakan matikan Fake GPS Anda.');
    }
    _db.setDeviceLocation(position.latitude, position.longitude);
    
    final samplesJson = samples.map((p) => {'lat': p.latitude, 'lng': p.longitude}).toList();
    return await _db.checkOut(position.latitude, position.longitude, accuracy: position.accuracy, samples: samplesJson);
  }
}
