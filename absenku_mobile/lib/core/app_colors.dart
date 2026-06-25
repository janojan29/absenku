// lib/core/app_colors.dart
import 'package:flutter/material.dart';

class AppColors {
  AppColors._();

  static const Color space950 = Color(0xFF030712);
  static const Color space900 = Color(0xFF111827);
  static const Color space800 = Color(0xFF1F2937);
  static const Color space700 = Color(0xFF374151);
  static const Color space600 = Color(0xFF4B5563);
  static const Color space500 = Color(0xFF6B7280);
  static const Color space400 = Color(0xFF9CA3AF);
  static const Color space300 = Color(0xFFD1D5DB);
  static const Color space200 = Color(0xFFE5E7EB);
  static const Color space100 = Color(0xFFF3F4F6);
  static const Color space50  = Color(0xFFF9FAFB);

  static const Color electric500 = Color(0xFF3B82F6);
  static const Color electric600 = Color(0xFF2563EB);
  static const Color electric700 = Color(0xFF1D4ED8);

  static const Color present = Color(0xFF10B981);
  static const Color lateStatus = Color(0xFFF59E0B);
  static const Color absent = Color(0xFFEF4444);
  static const Color leave = Color(0xFF8B5CF6);

  static const LinearGradient gradHero = LinearGradient(
    colors: [Color(0xFF1E3A8A), Color(0xFF0F172A)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient gradPrimary = LinearGradient(
    colors: [Color(0xFF2563EB), Color(0xFF1D4ED8)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
