import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  // Custom Colors matching the Web Dashboard
  static const Color primaryNavy = Color(0xFF0D1B2A);
  static const Color primaryBlue = Color(0xFF1E4D8C);
  static const Color accentBlue = Color(0xFF2563B8);
  
  static const Color statusPresent = Color(0xFF10B981); // Emerald
  static const Color statusLate = Color(0xFFF59E0B);    // Amber
  static const Color statusLeave = Color(0xFF06B6D4);   // Cyan
  static const Color statusAbsent = Color(0xFFE11D48);  // Rose
  
  static const Color backgroundLight = Color(0xFFF8FAFC);
  static const Color cardBackground = Colors.white;
  static const Color textDark = Color(0xFF1E293B);
  static const Color textMuted = Color(0xFF64748B);
  static const Color borderLight = Color(0xFFE2E8F0);

  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: accentBlue,
        primary: primaryBlue,
        secondary: accentBlue,
        surface: backgroundLight,
      ),
      textTheme: GoogleFonts.interTextTheme().copyWith(
        titleLarge: GoogleFonts.inter(
          fontWeight: FontWeight.bold,
          color: primaryNavy,
        ),
        titleMedium: GoogleFonts.inter(
          fontWeight: FontWeight.w600,
          color: primaryNavy,
        ),
        bodyLarge: GoogleFonts.inter(
          color: textDark,
        ),
        bodyMedium: GoogleFonts.inter(
          color: textMuted,
        ),
      ),
      cardTheme: CardThemeData(
        color: cardBackground,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: borderLight, width: 1),
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: primaryNavy,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.inter(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: Colors.white,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: accentBlue,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: GoogleFonts.inter(
            fontWeight: FontWeight.bold,
            fontSize: 15,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: borderLight, width: 1.5),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: borderLight, width: 1.5),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: accentBlue, width: 2),
        ),
        labelStyle: GoogleFonts.inter(color: textMuted),
        hintStyle: GoogleFonts.inter(color: textMuted.withValues(alpha: 0.6)),
      ),
    );
  }
}
