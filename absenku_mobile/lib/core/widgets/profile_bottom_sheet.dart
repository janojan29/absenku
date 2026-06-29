import 'package:flutter/material.dart';
import '../../services/mock_database.dart';
import '../config/theme.dart';
import '../../features/profile/screens/profile_screen.dart';

class ProfileBottomSheet {
  static void show(BuildContext context, MockDatabase db) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(24),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Handle bar
                Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 24),
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                
                // Avatar
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    color: AppTheme.primaryBlue.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                    border: Border.all(color: AppTheme.primaryBlue.withValues(alpha: 0.2), width: 2),
                  ),
                  child: Center(
                    child: Text(
                      (db.currentUser?.name ?? 'U')[0].toUpperCase(),
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.primaryNavy,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                
                // User Info
                Text(
                  db.currentUser?.name ?? 'Pengguna',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.primaryNavy,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  db.currentUser?.email ?? '',
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppTheme.textMuted,
                  ),
                ),
                
                const SizedBox(height: 32),
                
                // Profile Option
                ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  tileColor: Colors.grey[50],
                  leading: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryBlue.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.person_outline, color: AppTheme.primaryBlue, size: 22),
                  ),
                  title: const Text(
                    'Profil Saya',
                    style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16, color: AppTheme.textDark),
                  ),
                  trailing: const Icon(Icons.chevron_right, color: AppTheme.textMuted),
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
                  },
                ),
                
                const SizedBox(height: 12),
                
                // Logout Option
                ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  tileColor: AppTheme.statusAbsent.withValues(alpha: 0.04),
                  leading: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppTheme.statusAbsent.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.logout, color: AppTheme.statusAbsent, size: 22),
                  ),
                  title: const Text(
                    'Keluar',
                    style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16, color: AppTheme.statusAbsent),
                  ),
                  onTap: () {
                    Navigator.pop(context);
                    db.logout();
                  },
                ),
                const SizedBox(height: 16),
              ],
            ),
          ),
        );
      },
    );
  }
}
