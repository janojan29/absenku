import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme.dart';
import '../../../services/mock_database.dart';
import '../../../services/attendance_service.dart';
import '../../../models/attendance.dart';
import '../../profile/screens/profile_screen.dart';
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
  bool _initialLoading = true;

  @override
  void initState() {
    super.initState();
    _updateTime();
    _clockTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      _updateTime();
    });
    _loadData();
  }

  Future<void> _loadData() async {
    await MockDatabase().fetchAttendanceData();
    if (mounted) {
      setState(() {
        _initialLoading = false;
      });
    }
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

        if (_initialLoading) {
          return Scaffold(
            backgroundColor: AppTheme.backgroundLight,
            appBar: AppBar(
              title: const Text('Absensi Harian', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              backgroundColor: AppTheme.primaryNavy,
            ),
            body: const Center(child: CircularProgressIndicator()),
          );
        }

        final classRoomName = user.classRoomName ?? '-';

        // Geolocation
        final distance = _attendanceService.calculateDistance(
          db.latitude, db.longitude, db.deviceLatitude, db.deviceLongitude,
        );
        final isInRange = distance <= db.radiusMeters;

        // Today's attendance
        final today = DateTime.now();
        final todayStart = DateTime(today.year, today.month, today.day);
        final todayAttendance = db.todayAttendance ??
            Attendance(id: '', userId: '', date: todayStart, status: 'unknown');

        // Today's leave
        final todayLeave = db.todayLeaveSubmission ??
            LeaveRequest(id: '', userId: '', type: '', date: todayStart, reason: '', keterangan: '', status: 'none');

        final canCheckInNow = db.canCheckInNow;
        final canCheckOutNow = db.canCheckOutNow;
        final isAfterCheckInEnd = db.isAfterCheckInEnd;
        final isAfterCheckOutEnd = db.isAfterCheckOutEnd;
        final recentHistory = db.attendance;

        return Scaffold(
          backgroundColor: AppTheme.backgroundLight,
          body: RefreshIndicator(
            onRefresh: _loadData,
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverAppBar(
                  toolbarHeight: 65,
                  floating: false,
                  pinned: true,
                  backgroundColor: AppTheme.primaryNavy,
                  titleSpacing: 16,
                  title: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Absensi Harian', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
                      Text('Lakukan absensi masuk dan pulang',
                          style: TextStyle(fontSize: 11, color: Colors.white.withValues(alpha: 0.7))),
                    ],
                  ),
                  actions: [
                    IconButton(
                      icon: const Icon(Icons.person),
                      tooltip: 'Profil',
                      onPressed: () {
                        Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
                      },
                    ),
                    IconButton(icon: const Icon(Icons.refresh), tooltip: 'Refresh', onPressed: () => _loadData()),
                    IconButton(icon: const Icon(Icons.logout), tooltip: 'Logout', onPressed: () => db.logout()),
                  ],
                  flexibleSpace: FlexibleSpaceBar(
                    background: Container(
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [AppTheme.primaryNavy, AppTheme.primaryBlue, AppTheme.accentBlue],
                          begin: Alignment.topLeft, end: Alignment.bottomRight,
                        ),
                      ),
                    ),
                  ),
                ),

                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        // User info
                        Text(user.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.textDark)),
                        Text('NIS: ${user.nis ?? "-"} · $classRoomName',
                            style: const TextStyle(fontSize: 14, color: AppTheme.textMuted)),
                        const SizedBox(height: 16),

                        // Leave Status Banner
                        if (todayLeave.status != 'none') ...[
                          _buildLeaveStatusBanner(todayLeave),
                          const SizedBox(height: 16),
                        ],

                        // Clock Card
                        Container(
                          clipBehavior: Clip.antiAlias,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(color: AppTheme.primaryNavy.withValues(alpha: 0.2), blurRadius: 12, offset: const Offset(0, 4)),
                            ],
                          ),
                          child: Column(
                            children: [
                              // Clock header
                              Container(
                                width: double.infinity,
                                padding: const EdgeInsets.symmetric(vertical: 28),
                                decoration: const BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [AppTheme.primaryNavy, AppTheme.primaryBlue, AppTheme.accentBlue],
                                    begin: Alignment.topLeft, end: Alignment.bottomRight,
                                  ),
                                ),
                                child: Column(
                                  children: [
                                    Text(_currentTime,
                                        style: const TextStyle(fontFamily: 'monospace', fontSize: 44, fontWeight: FontWeight.bold, color: Colors.white, letterSpacing: 2)),
                                    const SizedBox(height: 6),
                                    Text(_currentDate, style: TextStyle(fontSize: 14, color: Colors.white.withValues(alpha: 0.7))),
                                  ],
                                ),
                              ),
                              // Card body
                              Container(
                                color: Colors.white,
                                padding: const EdgeInsets.all(16),
                                child: Column(
                                  children: [
                                    // Location status
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                      decoration: BoxDecoration(
                                        color: isInRange ? const Color(0xFFECFDF5) : const Color(0xFFFEF2F2),
                                        border: Border.all(color: isInRange ? const Color(0xFFA7F3D0) : const Color(0xFFFECACA)),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Row(
                                        children: [
                                          Container(
                                            width: 40, height: 40,
                                            decoration: BoxDecoration(
                                              color: isInRange ? const Color(0xFFD1FAE5) : const Color(0xFFFEE2E2),
                                              shape: BoxShape.circle,
                                            ),
                                            child: Icon(Icons.location_on, color: isInRange ? AppTheme.statusPresent : AppTheme.statusAbsent, size: 20),
                                          ),
                                          const SizedBox(width: 12),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  isInRange ? 'Anda berada di Area Sekolah' : 'Anda di luar Area Sekolah',
                                                  style: TextStyle(color: isInRange ? const Color(0xFF065F46) : const Color(0xFF991B1B), fontWeight: FontWeight.w600, fontSize: 13),
                                                ),
                                                Text('Jarak: ${distance.toStringAsFixed(1)}m · Radius: ${db.radiusMeters}m',
                                                    style: TextStyle(color: isInRange ? const Color(0xFF047857) : const Color(0xFFDC2626), fontSize: 11)),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(height: 16),

                                    // Action Button
                                    _buildActionButton(db, todayAttendance, isInRange, canCheckInNow, canCheckOutNow, isAfterCheckInEnd, isAfterCheckOutEnd),
                                    const SizedBox(height: 16),

                                    // Time display
                                    Container(
                                      padding: const EdgeInsets.all(14),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFF8FAFC),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Row(
                                        children: [
                                          Expanded(child: Column(children: [
                                            const Text('Masuk', style: TextStyle(color: AppTheme.textMuted, fontSize: 12)),
                                            const SizedBox(height: 4),
                                            Text(todayAttendance.checkInAt != null ? DateFormat('HH:mm').format(todayAttendance.checkInAt!) : '—',
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark)),
                                          ])),
                                          Container(width: 1, height: 30, color: AppTheme.borderLight),
                                          Expanded(child: Column(children: [
                                            const Text('Pulang', style: TextStyle(color: AppTheme.textMuted, fontSize: 12)),
                                            const SizedBox(height: 4),
                                            Text(todayAttendance.checkOutAt != null ? DateFormat('HH:mm').format(todayAttendance.checkOutAt!) : '—',
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textDark)),
                                          ])),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 20),

                        // Leave button
                        if (db.showLeaveForm && todayLeave.status == 'none') ...[
                          OutlinedButton.icon(
                            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (context) => const LeaveRequestScreen())),
                            icon: const Icon(Icons.note_alt_outlined),
                            label: const Text('AJUKAN IZIN / PERIZINAN'),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              side: const BorderSide(color: AppTheme.accentBlue, width: 1.5),
                            ),
                          ),
                          const SizedBox(height: 20),
                        ],

                        // History
                        _buildHistorySection(recentHistory),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildActionButton(MockDatabase db, Attendance todayAttendance, bool isInRange, bool canCheckInNow, bool canCheckOutNow, bool isAfterCheckInEnd, bool isAfterCheckOutEnd) {
    if (db.isHolidayToday) {
      return _buildInfoBox(Icons.calendar_today_outlined, 'Hari Libur', 'Absensi masuk & pulang dinonaktifkan hari ini.', AppTheme.textMuted, const Color(0xFFF1F5F9));
    }
    if (db.hasApprovedAbsentLeaveToday) {
      return _buildInfoBox(Icons.check_circle_outline, 'Izin Tidak Masuk Disetujui', 'Absensi hari ini dikunci otomatis sebagai izin.', AppTheme.statusLeave, const Color(0xFFE0F2FE));
    }
    if (todayAttendance.checkInAt == null) {
      return SizedBox(
        height: 52,
        child: ElevatedButton.icon(
          onPressed: (isInRange && canCheckInNow) ? () => _doCheckIn(db) : null,
          icon: const Icon(Icons.login),
          label: Text(!db.hasReachedCheckInStart ? 'Absen Masuk (buka ${db.checkInStart})' : isAfterCheckInEnd ? 'Absen Masuk Sudah Ditutup' : 'ABSEN MASUK'),
          style: ElevatedButton.styleFrom(
            backgroundColor: AppTheme.accentBlue, disabledBackgroundColor: Colors.grey[300], disabledForegroundColor: Colors.grey[600],
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
      );
    }
    if (todayAttendance.checkOutAt == null) {
      return SizedBox(
        height: 52,
        child: ElevatedButton.icon(
          onPressed: (isInRange && canCheckOutNow) ? () => _doCheckOut(db) : null,
          icon: const Icon(Icons.logout),
          label: Text(!canCheckOutNow ? 'Absen Pulang (buka ${db.checkOutStart})' : isAfterCheckOutEnd ? 'Absen Pulang Sudah Ditutup' : 'ABSEN PULANG'),
          style: ElevatedButton.styleFrom(
            backgroundColor: AppTheme.primaryNavy, disabledBackgroundColor: Colors.grey[300], disabledForegroundColor: Colors.grey[600],
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
      );
    }
    return _buildInfoBox(Icons.verified, 'Absensi Selesai', 'Sudah absen masuk & absen pulang hari ini', AppTheme.statusPresent, const Color(0xFFECFDF5));
  }

  Widget _buildInfoBox(IconData icon, String title, String subtitle, Color color, Color bg) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
      child: Column(children: [
        Icon(icon, color: color, size: 40),
        const SizedBox(height: 8),
        Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: color)),
        const SizedBox(height: 4),
        Text(subtitle, style: const TextStyle(fontSize: 12, color: AppTheme.textMuted), textAlign: TextAlign.center),
      ]),
    );
  }

  Future<void> _doCheckIn(MockDatabase db) async {
    try {
      final msg = await _attendanceService.checkIn(db.deviceLatitude, db.deviceLongitude);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))));
    }
  }

  Future<void> _doCheckOut(MockDatabase db) async {
    try {
      final msg = await _attendanceService.checkOut(db.deviceLatitude, db.deviceLongitude);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))));
    }
  }

  Widget _buildHistorySection(List<Attendance> recentHistory) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.borderLight)),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(children: [
            Icon(Icons.history, color: AppTheme.textMuted, size: 20),
            SizedBox(width: 8),
            Text('Riwayat 7 Hari', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 15, color: AppTheme.textDark)),
          ]),
          const SizedBox(height: 12),
          if (recentHistory.isEmpty)
            const Padding(padding: EdgeInsets.all(24), child: Center(child: Text('Belum ada data absensi.', style: TextStyle(color: AppTheme.textMuted))))
          else
            ...recentHistory.map((row) => _buildHistoryItem(row)),
        ],
      ),
    );
  }

  Widget _buildHistoryItem(Attendance row) {
    final dateStr = DateFormat('dd/MM/yyyy').format(row.date);
    final checkInStr = row.checkInAt != null ? DateFormat('HH:mm').format(row.checkInAt!) : '—';
    final checkOutStr = row.checkOutAt != null ? DateFormat('HH:mm').format(row.checkOutAt!) : '—';

    Color badgeColor;
    String statusLabel;
    switch (row.status) {
      case 'present': badgeColor = AppTheme.statusPresent; statusLabel = 'Hadir'; break;
      case 'late': badgeColor = AppTheme.statusLate; statusLabel = 'Terlambat'; break;
      case 'leave': badgeColor = AppTheme.statusLeave; statusLabel = 'Izin'; break;
      case 'sick': badgeColor = AppTheme.statusLeave; statusLabel = 'Sakit'; break;
      case 'absent': badgeColor = AppTheme.statusAbsent; statusLabel = 'Alfa'; break;
      default: badgeColor = Colors.grey; statusLabel = row.status.toUpperCase();
    }

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: AppTheme.borderLight.withValues(alpha: 0.5)))),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Expanded(child: Text(dateStr, style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 13, color: AppTheme.textDark))),
            Text('$checkInStr / $checkOutStr', style: const TextStyle(fontSize: 12, color: AppTheme.textMuted, fontFamily: 'monospace')),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
              decoration: BoxDecoration(color: badgeColor.withValues(alpha: 0.1), border: Border.all(color: badgeColor.withValues(alpha: 0.3)), borderRadius: BorderRadius.circular(20)),
              child: Text(statusLabel, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: badgeColor)),
            ),
          ]),
          if (row.leaveRequest != null) ...[
            const SizedBox(height: 6),
            Container(
              width: double.infinity, padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: const Color(0xFFF1F5F9), border: Border.all(color: const Color(0xFFE2E8F0)), borderRadius: BorderRadius.circular(8)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(row.leaveRequest!.type == 'early_leave' ? 'Izin Pulang Mendahului' : 'Izin Tidak Masuk',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: AppTheme.textDark)),
                if (row.leaveRequest!.keterangan.isNotEmpty)
                  Text('"${row.leaveRequest!.keterangan}"', style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: AppTheme.textMuted)),
              ]),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildLeaveStatusBanner(LeaveRequest leave) {
    Color color;
    String statusLabel;
    IconData icon;
    if (leave.status == 'approved') {
      color = AppTheme.statusPresent; statusLabel = 'Pengajuan Izin Disetujui'; icon = Icons.check_circle;
    } else if (leave.status == 'rejected') {
      color = AppTheme.statusAbsent; statusLabel = 'Pengajuan Izin Ditolak'; icon = Icons.cancel;
    } else {
      color = AppTheme.statusLate; statusLabel = 'Izin Menunggu Persetujuan'; icon = Icons.hourglass_top;
    }
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.08), border: Border.all(color: color.withValues(alpha: 0.3)), borderRadius: BorderRadius.circular(12)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Icon(icon, color: color, size: 20), const SizedBox(width: 8),
          Text(statusLabel, style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 13)),
        ]),
        const SizedBox(height: 6),
        Text('Tipe: ${leave.type == "absent" ? "Tidak Masuk" : "Pulang Awal"} (${leave.reason == "sick" ? "Sakit" : "Urusan Penting"})',
            style: const TextStyle(fontSize: 12, color: AppTheme.textDark)),
        if (leave.keterangan.isNotEmpty)
          Padding(padding: const EdgeInsets.only(top: 4), child: Text('Keterangan: "${leave.keterangan}"',
              style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: AppTheme.textMuted))),
        if (leave.decisionNote != null && leave.decisionNote!.isNotEmpty)
          Padding(padding: const EdgeInsets.only(top: 4), child: Text('Catatan Guru: ${leave.decisionNote}',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: color))),
        if (leave.status == 'pending')
          Container(
            margin: const EdgeInsets.only(top: 8), padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(color: Colors.amber[50], borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.amber[200]!)),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Icon(Icons.info_outline, color: Colors.amber[800], size: 14), const SizedBox(width: 6),
              Expanded(child: Text('Segera hubungi Wali Kelas via WhatsApp untuk konfirmasi.',
                  style: TextStyle(fontSize: 10, color: Colors.amber[900], fontWeight: FontWeight.w500))),
            ]),
          ),
      ]),
    );
  }
}
