// lib/widgets/status_badge.dart
import 'package:flutter/material.dart';
import '../core/app_colors.dart';

class StatusBadge extends StatelessWidget {
  final String status;
  final String? label;

  const StatusBadge({
    super.key,
    required this.status,
    this.label,
  });

  @override
  Widget build(BuildContext context) {
    Color color;
    String text = label ?? status.toUpperCase();

    switch (status.toLowerCase()) {
      case 'present':
      case 'hadir':
        color = AppColors.present;
        text = label ?? 'Hadir';
        break;
      case 'late':
      case 'terlambat':
        color = AppColors.lateStatus;
        text = label ?? 'Terlambat';
        break;
      case 'absent':
      case 'alpha':
        color = AppColors.absent;
        text = label ?? 'Alpa';
        break;
      case 'leave':
      case 'izin':
      case 'sakit':
        color = AppColors.leave;
        text = label ?? 'Izin';
        break;
      case 'approved':
      case 'disetujui':
        color = AppColors.present;
        text = label ?? 'Disetujui';
        break;
      case 'rejected':
      case 'ditolak':
        color = AppColors.absent;
        text = label ?? 'Ditolak';
        break;
      default:
        color = AppColors.space500;
        text = label ?? 'Pending';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withAlpha(26),
        borderRadius: BorderRadius.circular(100),
        border: Border.all(color: color.withAlpha(77)),
      ),
      child: Text(
        text,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
