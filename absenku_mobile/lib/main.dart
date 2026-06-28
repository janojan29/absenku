import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'services/api_client.dart';
import 'services/mock_database.dart';
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

  runApp(const MyApp());
}
