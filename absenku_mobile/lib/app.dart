import 'package:device_preview/device_preview.dart';
import 'package:flutter/material.dart';
import 'core/config/theme.dart';
import 'services/mock_database.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/student/screens/attendance_screen.dart';
import 'features/teacher/screens/dashboard_screen.dart';
import 'features/admin/screens/admin_main_screen.dart';
import 'features/profile/screens/profile_screen.dart';
import 'features/splash/screens/splash_screen.dart';

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  bool _showSplash = true;

  @override
  void initState() {
    super.initState();
    Future.delayed(const Duration(seconds: 10), () {
      if (mounted) {
        setState(() {
          _showSplash = false;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final user = db.currentUser;

        Widget homeScreen;
        if (_showSplash) {
          homeScreen = const SplashScreen();
        } else if (user == null) {
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
          key: ValueKey(user?.id ?? 'guest'),
          locale: DevicePreview.locale(context),
          builder: DevicePreview.appBuilder,
          title: 'Absenku Mobile',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.lightTheme,
          home: AnimatedSwitcher(
            duration: const Duration(milliseconds: 2500),
            switchInCurve: Curves.easeIn,
            switchOutCurve: Curves.easeOut,
            transitionBuilder: (Widget child, Animation<double> animation) {
              return FadeTransition(
                opacity: animation,
                child: child,
              );
            },
            child: KeyedSubtree(
              key: ValueKey<Type>(homeScreen.runtimeType),
              child: homeScreen,
            ),
          ),
        );
      },
    );
  }
}


