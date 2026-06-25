// lib/widgets/clock_widget.dart
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class ClockWidget extends StatefulWidget {
  final TextStyle? timeStyle;
  final TextStyle? dateStyle;

  const ClockWidget({
    super.key,
    this.timeStyle,
    this.dateStyle,
  });

  @override
  State<ClockWidget> createState() => _ClockWidgetState();
}

class _ClockWidgetState extends State<ClockWidget> {
  late Timer _timer;
  late DateTime _currentTime;

  @override
  void initState() {
    super.initState();
    _currentTime = DateTime.now();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          _currentTime = DateTime.now();
        });
      }
    });
  }

  @override
  void dispose() {
    _timer.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final timeStr = DateFormat('HH:mm:ss').format(_currentTime);
    final dateStr = DateFormat('EEEE, d MMMM yyyy', 'id').format(_currentTime);

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          timeStr,
          style: widget.timeStyle ??
              const TextStyle(
                fontSize: 36,
                fontWeight: FontWeight.bold,
                letterSpacing: 1.2,
              ),
        ),
        const SizedBox(height: 4),
        Text(
          dateStr,
          style: widget.dateStyle ??
              const TextStyle(
                fontSize: 14,
                color: Colors.white70,
              ),
        ),
      ],
    );
  }
}
