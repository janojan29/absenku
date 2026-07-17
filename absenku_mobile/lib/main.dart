// File ini adalah titik masuk aplikasi mobile Absenku.
// Saat aplikasi dimulai, file ini menginisialisasi binding Flutter, menyiapkan locale Indonesia,
// mengaktifkan klien API, memuat data simulasi, lalu menjalankan aplikasi dengan layar utama yang sesuai.

import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'services/api_client.dart';
import 'services/mock_database.dart';
import 'package:device_preview/device_preview.dart';
import 'package:flutter/foundation.dart';
import 'services/notification_service.dart';
import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize indonesian locale for date time formatting
  await initializeDateFormatting('id_ID', null);
  
  // Initialize API client (Dio + interceptors)
  await ApiClient().init();
  
  // Initialize database (restore session if token exists)
  final db = MockDatabase();
  await db.init();
  
  // Initialize notification service
  await NotificationService().init();

  runApp(
    DevicePreview(
      enabled: !kReleaseMode,
      builder: (context) => const MyApp(),
    ),
  );
}
