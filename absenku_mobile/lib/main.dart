import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'services/mock_database.dart';
import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize indonesian locale for date time formatting
  await initializeDateFormatting('id_ID', null);
  
  // Initialize mock database (shared preferences load)
  final db = MockDatabase();
  await db.init();

  runApp(const MyApp());
}
