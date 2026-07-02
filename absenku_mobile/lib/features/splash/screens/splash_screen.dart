// File ini berisi layar splash saat aplikasi dimulai.
// Layar ini memberikan kesan pembuka aplikasi sebelum pengguna diarahkan ke halaman yang sesuai.

import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/config/theme.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  
  // Background animations
  late Animation<Offset> _blob1Position;
  late Animation<Offset> _blob2Position;
  late Animation<double> _blobOpacity;

  // Foreground animations
  late Animation<double> _logoScale;
  late Animation<double> _logoOpacity;
  
  late Animation<Offset> _textSlide;
  late Animation<double> _textOpacity;
  late Animation<double> _textSpacing;
  
  late Animation<Offset> _taglineSlide;
  late Animation<double> _taglineOpacity;
  
  late Animation<double> _logoGlow;

  @override
  void initState() {
    super.initState();
    
    // Total 10 seconds duration for a highly elegant, slow-paced cinematic entry
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 10000),
    );

    // Blobs slowly drift towards the center over the entire 7 seconds
    _blob1Position = Tween<Offset>(
      begin: const Offset(-0.4, -0.2), 
      end: const Offset(0.1, 0.3)
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOutSine));

    _blob2Position = Tween<Offset>(
      begin: const Offset(1.2, 1.0), 
      end: const Offset(0.6, 0.4)
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOutSine));

    _blobOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: const Interval(0.0, 0.2, curve: Curves.easeIn)),
    );

    // Logo appears incredibly smoothly
    _logoOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.1, 0.3, curve: Curves.easeOut), // 0.7s to 2.1s
    ));

    _logoScale = TweenSequence([
      TweenSequenceItem(
        tween: Tween<double>(begin: 0.8, end: 1.1).chain(CurveTween(curve: Curves.easeOutCubic)), 
        weight: 30, // 0.7s to 2.8s
      ),
      TweenSequenceItem(
        tween: Tween<double>(begin: 1.1, end: 1.0).chain(CurveTween(curve: Curves.easeInOutSine)), 
        weight: 70, // 2.8s to 7.0s
      ),
    ]).animate(CurvedAnimation(parent: _controller, curve: const Interval(0.1, 1.0)));

    // Logo gets a majestic glow pulse
    _logoGlow = TweenSequence([
      TweenSequenceItem(tween: Tween<double>(begin: 0.0, end: 30.0).chain(CurveTween(curve: Curves.easeOutSine)), weight: 40),
      TweenSequenceItem(tween: Tween<double>(begin: 30.0, end: 15.0).chain(CurveTween(curve: Curves.easeInOutSine)), weight: 60),
    ]).animate(CurvedAnimation(parent: _controller, curve: const Interval(0.2, 1.0)));

    // Text slides up very slowly and elegantly
    _textOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.25, 0.45, curve: Curves.easeIn), // 1.75s to 3.15s
    ));

    _textSlide = Tween<Offset>(begin: const Offset(0, 0.4), end: Offset.zero).animate(CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.25, 0.6, curve: Curves.easeOutCubic),
    ));

    // Subtle letter spacing expansion for premium cinematic feel
    _textSpacing = Tween<double>(begin: 0.0, end: 2.5).animate(CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.25, 1.0, curve: Curves.easeOut),
    ));

    // Tagline appears after the text
    _taglineOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.4, 0.6, curve: Curves.easeIn), // 2.8s to 4.2s
    ));

    _taglineSlide = Tween<Offset>(begin: const Offset(0, 0.4), end: Offset.zero).animate(CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.4, 0.7, curve: Curves.easeOutCubic),
    ));

    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Widget _buildBlurredBlob({required Color color, required double size}) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: color,
            blurRadius: 120,
            spreadRadius: 60,
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC), // Very light clean background
      body: AnimatedBuilder(
        animation: _controller,
        builder: (context, child) {
          return Stack(
            children: [
              // Background Layer
              Opacity(
                opacity: _blobOpacity.value,
                child: Stack(
                  children: [
                    // Top-left blob (drifting to center-right)
                    Positioned(
                      left: size.width * _blob1Position.value.dx,
                      top: size.height * _blob1Position.value.dy,
                      child: _buildBlurredBlob(
                        color: AppTheme.primaryBlue.withOpacity(0.15),
                        size: 300,
                      ),
                    ),
                    // Bottom-right blob (drifting to center-left)
                    Positioned(
                      left: size.width * _blob2Position.value.dx,
                      top: size.height * _blob2Position.value.dy,
                      child: _buildBlurredBlob(
                        color: const Color(0xFF64B5F6).withOpacity(0.12),
                        size: 350,
                      ),
                    ),
                  ],
                ),
              ),
              
              // Foreground Layer
              Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    // Logo
                    Opacity(
                      opacity: _logoOpacity.value,
                      child: Transform.scale(
                        scale: _logoScale.value,
                        child: Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: Colors.white,
                            boxShadow: [
                              // Fixed soft shadow
                              BoxShadow(
                                color: AppTheme.primaryBlue.withOpacity(0.08),
                                blurRadius: 20,
                                spreadRadius: 5,
                                offset: const Offset(0, 10),
                              ),
                              // Animated majestic glow
                              BoxShadow(
                                color: AppTheme.primaryBlue.withOpacity(0.15),
                                blurRadius: _logoGlow.value,
                                spreadRadius: _logoGlow.value / 2,
                              ),
                            ],
                          ),
                          child: Image.asset(
                            'assets/images/logo_transparent.webp',
                            width: 140,
                            height: 140,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 50),
                    
                    // App Name
                    Opacity(
                      opacity: _textOpacity.value,
                      child: FractionalTranslation(
                        translation: _textSlide.value,
                        child: Text(
                          'Absenku',
                          style: GoogleFonts.outfit(
                            fontSize: 44,
                            fontWeight: FontWeight.w800,
                            color: AppTheme.primaryBlue,
                            letterSpacing: _textSpacing.value,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    
                    // Tagline
                    Opacity(
                      opacity: _taglineOpacity.value,
                      child: FractionalTranslation(
                        translation: _taglineSlide.value,
                        child: Text(
                          'Sistem Kehadiran Digital',
                          style: GoogleFonts.inter(
                            fontSize: 16,
                            fontWeight: FontWeight.w500,
                            color: Colors.grey.shade600,
                            letterSpacing: 1.0,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
