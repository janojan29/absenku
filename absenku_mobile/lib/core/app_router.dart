// lib/core/app_router.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../screens/login_screen.dart';
import '../screens/profile_screen.dart';
import '../screens/student/attendance_screen.dart';
import '../screens/teacher/teacher_dashboard_screen.dart';
import '../screens/teacher/teacher_report_screen.dart';
import '../screens/picket/leave_approval_screen.dart';
import '../screens/admin/settings_screen.dart';
import '../screens/admin/users_screen.dart';
import '../screens/admin/classrooms_screen.dart';
import '../screens/admin/students_screen.dart';
import '../screens/admin/student_form_screen.dart';
import '../screens/admin/teachers_screen.dart';
import '../screens/admin/teacher_form_screen.dart';

class AppRouter {
  AppRouter._();

  static final GoRouter router = GoRouter(
    initialLocation: '/login',
    redirect: (BuildContext context, GoRouterState state) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final loggingIn = state.matchedLocation == '/login';

      if (!auth.isAuthenticated) {
        return loggingIn ? null : '/login';
      }

      if (loggingIn) {
        final role = auth.currentUser!.role;
        if (role == 'admin') return '/admin/settings';
        if (role == 'guru_walikelas') return '/teacher/dashboard';
        if (role == 'petugas_piket') return '/picket/leave';
        return '/student/attendance';
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/profile',
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: '/student/attendance',
        builder: (context, state) => const AttendanceScreen(),
      ),
      GoRoute(
        path: '/teacher/dashboard',
        builder: (context, state) => const TeacherDashboardScreen(),
      ),
      GoRoute(
        path: '/teacher/report',
        builder: (context, state) => const TeacherReportScreen(),
      ),
      GoRoute(
        path: '/picket/leave',
        builder: (context, state) => const LeaveApprovalScreen(),
      ),
      GoRoute(
        path: '/admin/settings',
        builder: (context, state) => const SettingsScreen(),
      ),
      GoRoute(
        path: '/admin/users',
        builder: (context, state) => const UsersScreen(),
      ),
      GoRoute(
        path: '/admin/classrooms',
        builder: (context, state) => const ClassroomsScreen(),
      ),
      GoRoute(
        path: '/admin/students',
        builder: (context, state) => const StudentsScreen(),
      ),
      GoRoute(
        path: '/admin/student/form',
        builder: (context, state) {
          final student = state.extra;
          return StudentFormScreen(student: student);
        },
      ),
      GoRoute(
        path: '/admin/teachers',
        builder: (context, state) => const TeachersScreen(),
      ),
      GoRoute(
        path: '/admin/teacher/form',
        builder: (context, state) {
          final teacher = state.extra;
          return TeacherFormScreen(teacher: teacher);
        },
      ),
    ],
  );
}
