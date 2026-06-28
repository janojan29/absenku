import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'core/config/theme.dart';
import 'services/mock_database.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/student/screens/attendance_screen.dart';
import 'features/teacher/screens/dashboard_screen.dart';
import 'features/admin/screens/admin_main_screen.dart';

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
          title: 'Absenku Mobile',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.lightTheme,
          home: WebDeviceFrame(child: homeScreen),
        );
      },
    );
  }
}

class WebDeviceFrame extends StatelessWidget {
  final Widget child;
  const WebDeviceFrame({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    // If not running on web, or the screen width is narrow (e.g. running on a real phone's browser),
    // we don't display the phone frame - we just render the child natively.
    if (!kIsWeb) return child;

    return LayoutBuilder(
      builder: (context, constraints) {
        final double screenWidth = constraints.maxWidth;
        final double screenHeight = constraints.maxHeight;

        // If screen is narrow (less than 600px wide), treat it as a direct mobile phone view
        if (screenWidth < 600) {
          return child;
        }

        // Otherwise, show a beautiful centered smartphone frame on a nice background
        return Scaffold(
          body: Container(
            width: double.infinity,
            height: double.infinity,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF0F172A), Color(0xFF1E293B)], // Dark slate theme background
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Center(
              child: SingleChildScrollView(
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 24.0),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      // Sleek phone frame
                      Container(
                        width: 380,
                        height: 780,
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B),
                          borderRadius: BorderRadius.circular(44),
                          border: Border.all(
                            color: const Color(0xFF475569), // Bezel color
                            width: 12,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.5),
                              blurRadius: 30,
                              offset: const Offset(0, 15),
                            ),
                          ],
                        ),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(32),
                          child: Stack(
                            children: [
                              // The actual app screens
                              Positioned.fill(child: child),

                              // Simulated phone notch (front camera speaker)
                              Positioned(
                                top: 0,
                                left: 0,
                                right: 0,
                                child: Center(
                                  child: Container(
                                    width: 140,
                                    height: 24,
                                    decoration: const BoxDecoration(
                                      color: Color(0xFF475569),
                                      borderRadius: BorderRadius.only(
                                        bottomLeft: Radius.circular(16),
                                        bottomRight: Radius.circular(16),
                                      ),
                                    ),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        // Speaker line
                                        Container(
                                          width: 40,
                                          height: 4,
                                          decoration: BoxDecoration(
                                            color: const Color(0xFF1E293B),
                                            borderRadius: BorderRadius.circular(2),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        // Camera lens circle
                                        Container(
                                          width: 8,
                                          height: 8,
                                          decoration: const BoxDecoration(
                                            color: Color(0xFF1E293B),
                                            shape: BoxShape.circle,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                              
                              // Bottom simulated home indicator bar
                              Positioned(
                                bottom: 6,
                                left: 0,
                                right: 0,
                                child: Center(
                                  child: Container(
                                    width: 120,
                                    height: 5,
                                    decoration: BoxDecoration(
                                      color: Colors.grey.withValues(alpha: 0.5),
                                      borderRadius: BorderRadius.circular(2.5),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      // Text tip below the phone
                      const Text(
                        'Absenku Mobile Web Simulator',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.5,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Menggunakan resolusi layar ${screenWidth.toInt()}x${screenHeight.toInt()}',
                        style: TextStyle(
                          color: Colors.grey.shade400,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
