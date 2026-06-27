import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../services/attendance_service.dart';
import '../../../models/user.dart';
import '../../../models/attendance.dart';
import 'leave_request_screen.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  final AttendanceService _attendanceService = AttendanceService();
  late Timer _clockTimer;
  String _currentTime = '';
  String _currentDate = '';

  @override
  void initState() {
    super.initState();
    _updateTime();
    _clockTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      _updateTime();
    });
  }

  @override
  void dispose() {
    _clockTimer.cancel();
    super.dispose();
  }

  void _updateTime() {
    if (!mounted) return;
    final now = DateTime.now();
    setState(() {
      _currentTime = DateFormat('HH:mm:ss').format(now);
      _currentDate = DateFormat('EEEE, d MMMM yyyy', 'id_ID').format(now);
    });
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: MockDatabase(),
      builder: (context, _) {
        final db = MockDatabase();
        final user = db.currentUser;
        if (user == null) return const SizedBox();

        // Get class name
        final classRoom = db.classrooms.firstWhere(
          (c) => c.id == user.classRoomId,
          orElse: () => ClassRoom(id: '', name: '-', jurusan: '-'),
        );

        // Geolocation info
        final distance = _attendanceService.calculateDistance(
          db.latitude,
          db.longitude,
          db.deviceLatitude,
          db.deviceLongitude,
        );
        final isInRange = distance <= db.radiusMeters;

        // Today's attendance
        final today = DateTime.now();
        final todayStart = DateTime(today.year, today.month, today.day);
        final todayAttendance = db.attendance.firstWhere(
          (a) => a.userId == user.id &&
              a.date.year == todayStart.year &&
              a.date.month == todayStart.month &&
              a.date.day == todayStart.day,
          orElse: () => Attendance(id: '', userId: '', date: todayStart, status: 'unknown'),
        );

        // Today's leave request
        final todayLeave = db.leaveRequests.firstWhere(
          (l) => l.userId == user.id &&
              l.date.year == todayStart.year &&
              l.date.month == todayStart.month &&
              l.date.day == todayStart.day,
          orElse: () => LeaveRequest(
            id: '',
            userId: '',
            type: '',
            date: todayStart,
            reason: '',
            keterangan: '',
            status: 'none',
          ),
        );

        // Get recent 7 days history
        final recentHistory = db.attendance
            .where((a) => a.userId == user.id && a.date.isBefore(todayStart))
            .toList();
        recentHistory.sort((a, b) => b.date.compareTo(a.date)); // descending date

        // Check scheduling limits (simulated)
        final nowStr = DateFormat('HH:mm').format(today);
        final isAfterCheckInEnd = nowStr.compareTo(db.checkInEnd) > 0;
        final isAfterCheckOutEnd = nowStr.compareTo(db.checkOutEnd) > 0;
        final canCheckInNow = nowStr.compareTo(db.checkInStart) >= 0;
        final canCheckOutNow = nowStr.compareTo(db.checkOutStart) >= 0;

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          appBar: AppBar(
            title: const Text('Presensi Absenku'),
            actions: [
              IconButton(
                icon: const Icon(Icons.logout),
                tooltip: 'Logout',
                onPressed: () {
                  db.logout();
                },
              ),
            ],
          ),
          body: SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Welcome Profile Details
                Row(
                  children: [
                    CircleAvatar(
                      radius: 26,
                      backgroundColor: AppTheme.primaryNavy.withValues(alpha: 0.1),
                      child: Text(
                        user.name.substring(0, 1).toUpperCase(),
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primaryNavy,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            user.name,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textDark,
                            ),
                          ),
                          Text(
                            'NIS: ${user.nis ?? "-"} · Kelas: ${classRoom.name}',
                            style: const TextStyle(
                              fontSize: 13,
                              color: AppTheme.textMuted,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Location Simulator Widget
                Card(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
                    child: Row(
                      children: [
                        const Icon(Icons.pin_drop_outlined, color: AppTheme.primaryBlue),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Simulasi Lokasi HP',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                              ),
                              Text(
                                'Ganti lokasi untuk menguji radius GPS',
                                style: TextStyle(fontSize: 11, color: AppTheme.textMuted),
                              ),
                            ],
                          ),
                        ),
                        // Toggle buttons
                        SegmentedButton<bool>(
                          segments: const [
                            ButtonSegment<bool>(
                              value: true,
                              label: Text('Sekolah', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                            ),
                            ButtonSegment<bool>(
                              value: false,
                              label: Text('Rumah', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                            ),
                          ],
                          selected: {isInRange},
                          onSelectionChanged: (Set<bool> newSelection) {
                            final inSchool = newSelection.first;
                            if (inSchool) {
                              db.setDeviceLocation(db.latitude, db.longitude);
                            } else {
                              // Offset coordinates (about 500 meters away)
                              db.setDeviceLocation(db.latitude + 0.005, db.longitude + 0.005);
                            }
                          },
                          showSelectedIcon: false,
                          style: SegmentedButton.styleFrom(
                            padding: EdgeInsets.zero,
                            visualDensity: VisualDensity.compact,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Leave Request Status Banner (if submitted today)
                if (todayLeave.status != 'none') ...[
                  _buildLeaveStatusBanner(todayLeave),
                  const SizedBox(height: 16),
                ],

                // Digital Clock Card
                Container(
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [AppTheme.primaryNavy, AppTheme.primaryBlue],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 24),
                  child: Column(
                    children: [
                      Text(
                        _currentTime,
                        style: const TextStyle(
                          fontFamily: 'monospace',
                          fontSize: 44,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                          letterSpacing: 2,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        _currentDate,
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.white.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Distance indicator status
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: isInRange ? const Color(0xFFE6F4EA) : const Color(0xFFFCE8E6),
                    border: Border.all(
                      color: isInRange ? const Color(0xFFCEEAD6) : const Color(0xFFFAD2CF),
                    ),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        isInRange ? Icons.location_on : Icons.location_off,
                        color: isInRange ? AppTheme.statusPresent : AppTheme.statusAbsent,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isInRange ? 'Anda berada di Area Sekolah' : 'Anda berada di luar Area Sekolah',
                              style: TextStyle(
                                color: isInRange ? AppTheme.statusPresent : AppTheme.statusAbsent,
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                              ),
                            ),
                            Text(
                              'Jarak: ${distance.toStringAsFixed(1)}m · Radius Sekolah: ${db.radiusMeters}m',
                              style: TextStyle(
                                color: isInRange ? const Color(0xFF137333) : const Color(0xFFC5221F),
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // Main Action Button
                if (todayLeave.status == 'approved' && todayLeave.type == 'absent')
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                    color: const Color(0xFFE8F0FE),
                    border: Border.all(color: const Color(0xFFADC6FA)),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Column(
                      children: [
                        Icon(Icons.check_circle_outline, color: AppTheme.statusLeave, size: 40),
                        SizedBox(height: 8),
                        Text(
                          'Izin Tidak Masuk Disetujui',
                          style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.statusLeave),
                        ),
                        SizedBox(height: 4),
                        Text(
                          'Absensi hari ini dikunci otomatis sebagai izin.',
                          style: TextStyle(fontSize: 12, color: Colors.blueGrey),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  )
                else if (todayAttendance.checkInAt == null)
                  ElevatedButton.icon(
                    onPressed: (isInRange && canCheckInNow)
                        ? () {
                            _attendanceService.checkIn(db.deviceLatitude, db.deviceLongitude);
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Berhasil Absen Masuk!')),
                            );
                          }
                        : null,
                    icon: const Icon(Icons.login),
                    label: Text(
                      !canCheckInNow 
                          ? 'Absen Masuk Belum Dibuka (${db.checkInStart})' 
                          : isAfterCheckInEnd 
                              ? 'Absen Masuk Sudah Ditutup' 
                              : 'ABSEN MASUK'
                    ),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      backgroundColor: AppTheme.statusPresent,
                      disabledBackgroundColor: Colors.grey[300],
                      disabledForegroundColor: Colors.grey[600],
                    ),
                  )
                else if (todayAttendance.checkOutAt == null)
                  ElevatedButton.icon(
                    onPressed: (isInRange && canCheckOutNow)
                        ? () {
                            _attendanceService.checkOut(db.deviceLatitude, db.deviceLongitude);
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Berhasil Absen Pulang!')),
                            );
                          }
                        : null,
                    icon: const Icon(Icons.logout),
                    label: Text(
                      !canCheckOutNow 
                          ? 'Absen Pulang Belum Dibuka (${db.checkOutStart})' 
                          : isAfterCheckOutEnd 
                              ? 'Absen Pulang Sudah Ditutup' 
                              : 'ABSEN PULANG'
                    ),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      backgroundColor: AppTheme.primaryBlue,
                      disabledBackgroundColor: Colors.grey[300],
                      disabledForegroundColor: Colors.grey[600],
                    ),
                  )
                else
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                    color: const Color(0xFFE6F4EA),
                    border: Border.all(color: const Color(0xFFCEEAD6)),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Column(
                      children: [
                        Icon(Icons.verified, color: AppTheme.statusPresent, size: 40),
                        SizedBox(height: 8),
                        Text(
                          'Absensi Selesai',
                          style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.statusPresent),
                        ),
                        SizedBox(height: 4),
                        Text(
                          'Anda sudah melakukan absen masuk dan pulang hari ini.',
                          style: TextStyle(fontSize: 12, color: Colors.blueGrey),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),

                const SizedBox(height: 16),

                // Check-in and Check-out Time Details Box
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    border: Border.all(color: AppTheme.borderLight),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          children: [
                            const Text('Masuk', style: TextStyle(color: AppTheme.textMuted, fontSize: 12)),
                            const SizedBox(height: 4),
                            Text(
                              todayAttendance.checkInAt != null
                                  ? DateFormat('HH:mm').format(todayAttendance.checkInAt!)
                                  : '—',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark),
                            ),
                          ],
                        ),
                      ),
                      Container(width: 1, height: 30, color: AppTheme.borderLight),
                      Expanded(
                        child: Column(
                          children: [
                            const Text('Pulang', style: TextStyle(color: AppTheme.textMuted, fontSize: 12)),
                            const SizedBox(height: 4),
                            Text(
                              todayAttendance.checkOutAt != null
                                  ? DateFormat('HH:mm').format(todayAttendance.checkOutAt!)
                                  : '—',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Floating Action or Quick Button for Leave Application
                if (todayLeave.status == 'none') ...[
                  OutlinedButton.icon(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => const LeaveRequestScreen()),
                      );
                    },
                    icon: const Icon(Icons.note_alt_outlined),
                    label: const Text('AJUKAN IZIN / PERIZINAN'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      side: const BorderSide(color: AppTheme.accentBlue, width: 1.5),
                    ),
                  ),
                  const SizedBox(height: 24),
                ],

                // 7 Days History Header
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Riwayat Kehadiran (7 Hari)',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(fontSize: 15),
                    ),
                    const Icon(Icons.history, color: AppTheme.textMuted, size: 20),
                  ],
                ),
                const SizedBox(height: 8),

                // History List
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: recentHistory.isEmpty ? 1 : recentHistory.length,
                  itemBuilder: (context, index) {
                    if (recentHistory.isEmpty) {
                      return const Card(
                        child: Padding(
                          padding: EdgeInsets.all(24.0),
                          child: Center(
                            child: Text(
                              'Belum ada data absensi.',
                              style: TextStyle(color: AppTheme.textMuted, fontSize: 13),
                            ),
                          ),
                        ),
                      );
                    }

                    final row = recentHistory[index];
                    final dateStr = DateFormat('dd/MM/yyyy').format(row.date);
                    final dayStr = DateFormat('EEEE', 'id_ID').format(row.date);
                    final checkInStr = row.checkInAt != null ? DateFormat('HH:mm').format(row.checkInAt!) : '—';
                    final checkOutStr = row.checkOutAt != null ? DateFormat('HH:mm').format(row.checkOutAt!) : '—';

                    Color badgeColor;
                    String statusLabel;
                    switch (row.status) {
                      case 'present':
                        badgeColor = AppTheme.statusPresent;
                        statusLabel = 'Hadir';
                        break;
                      case 'late':
                        badgeColor = AppTheme.statusLate;
                        statusLabel = 'Terlambat';
                        break;
                      case 'leave':
                        badgeColor = AppTheme.statusLeave;
                        statusLabel = 'Izin';
                        break;
                      case 'sick':
                        badgeColor = AppTheme.statusLeave;
                        statusLabel = 'Sakit';
                        break;
                      case 'absent':
                        badgeColor = AppTheme.statusAbsent;
                        statusLabel = 'Alfa';
                        break;
                      default:
                        badgeColor = Colors.grey;
                        statusLabel = row.status.toUpperCase();
                    }

                    return Card(
                      margin: const EdgeInsets.only(bottom: 10),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
                        child: Row(
                          children: [
                            Expanded(
                              flex: 3,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    dayStr,
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                  ),
                                  Text(
                                    dateStr,
                                    style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                                  ),
                                ],
                              ),
                            ),
                            Expanded(
                              flex: 4,
                              child: Column(
                                children: [
                                  Text(
                                    'In: $checkInStr · Out: $checkOutStr',
                                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: AppTheme.textDark),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: badgeColor.withValues(alpha: 0.1),
                                border: Border.all(color: badgeColor.withValues(alpha: 0.4)),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                statusLabel,
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: badgeColor,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildLeaveStatusBanner(LeaveRequest leave) {
    Color color;
    String statusLabel;
    IconData icon;
    
    if (leave.status == 'approved') {
      color = AppTheme.statusPresent;
      statusLabel = 'Pengajuan Izin Disetujui';
      icon = Icons.check_circle;
    } else if (leave.status == 'rejected') {
      color = AppTheme.statusAbsent;
      statusLabel = 'Pengajuan Izin Ditolak';
      icon = Icons.cancel;
    } else {
      color = AppTheme.statusLate;
      statusLabel = 'Izin Menunggu Persetujuan';
      icon = Icons.hourglass_top;
    }

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        border: Border.all(color: color.withValues(alpha: 0.3)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: color, size: 20),
              const SizedBox(width: 8),
              Text(
                statusLabel,
                style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 13),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            'Tipe: ${leave.type == "absent" ? "Tidak Masuk" : "Pulang Awal"} (${leave.reason == "sick" ? "Sakit" : "Urusan Penting"})',
            style: const TextStyle(fontSize: 12, color: AppTheme.textDark),
          ),
          if (leave.keterangan.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 4.0),
              child: Text(
                'Keterangan: "${leave.keterangan}"',
                style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: AppTheme.textMuted),
              ),
            ),
          if (leave.decisionNote != null && leave.decisionNote!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 4.0),
              child: Text(
                'Catatan Guru: ${leave.decisionNote}',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: color),
              ),
            ),
          if (leave.status == 'pending')
            Container(
              margin: const EdgeInsets.only(top: 8),
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.amber[100],
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.info_outline, color: Colors.amber[800], size: 14),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      'Segera konfirmasi ke Wali Kelas via WhatsApp agar pengajuan segera diproses.',
                      style: TextStyle(fontSize: 10, color: Colors.amber[900], fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
