import 'package:device_preview/device_preview.dart';
import 'package:flutter/material.dart';
import 'core/config/theme.dart';
import 'services/mock_database.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/student/screens/attendance_screen.dart';
import 'features/teacher/screens/dashboard_screen.dart';
import 'features/admin/screens/admin_main_screen.dart';
import 'features/profile/screens/profile_screen.dart';

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final user = db.currentUser;

        Widget homeScreen;
        if (user == null) {
          homeScreen = const LoginScreen();
        } else if (db.mustChangePassword) {
          homeScreen = const ProfileScreen(forceChangePassword: true);
        } else if (user.role == 'siswa') {
          homeScreen = const AttendanceScreen();
        } else if (user.role == 'guru' || user.role == 'guru_walikelas' || user.role == 'petugas_piket') {
          homeScreen = const TeacherDashboardScreen();
        } else if (user.role == 'admin') {
          homeScreen = const AdminMainScreen();
        } else {
          homeScreen = const LoginScreen();
        }

        return MaterialApp(
          locale: DevicePreview.locale(context),
          builder: DevicePreview.appBuilder,
          title: 'Absenku Mobile',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.lightTheme,
          home: homeScreen,
        );
      },
    );
  }
}


